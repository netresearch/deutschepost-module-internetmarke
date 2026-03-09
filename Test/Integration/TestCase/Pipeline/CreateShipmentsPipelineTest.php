<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Test\Integration\TestCase\Pipeline;

use DeutschePost\Internetmarke\Model\Pipeline\CreateShipments\ArtifactsContainer;
use DeutschePost\Internetmarke\Model\Pipeline\CreateShipments\ShipmentResponse\LabelResponse;
use DeutschePost\Internetmarke\Model\Pipeline\CreateShipments\Stage\MapResponseStage;
use DeutschePost\Internetmarke\Model\Pipeline\CreateShipments\Stage\SendRequestStage;
use DeutschePost\Internetmarke\Test\Integration\TestDouble\HttpMockServiceFactory;
use DeutschePost\Internetmarke\Test\Integration\TestDouble\HttpResponseFactory;
use DeutschePost\Internetmarke\Test\Integration\TestDouble\QueueHttpClient;
use DeutschePost\Sdk\Internetmarke\Model\Order;
use DeutschePost\Sdk\Internetmarke\Model\OrderRequest;
use DeutschePost\Sdk\Internetmarke\Model\ShoppingCartPosition;
use Magento\Sales\Model\Order\Shipment;
use Magento\Shipping\Model\Shipment\Request;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for the Internetmarke shipment creation pipeline.
 *
 * Exercises SendRequestStage and MapResponseStage with real SDK code,
 * mocked only at the HTTP transport level via QueueHttpClient.
 *
 * @magentoAppArea adminhtml
 */
class CreateShipmentsPipelineTest extends TestCase
{
    private const REQUEST_INDEX = 0;
    private const LABEL_PDF = '%PDF-1.5 test label binary';
    private const MANIFEST_PDF = '%PDF-1.5 test manifest binary';

    private function createShipmentRequest(): Request
    {
        $shipment = $this->createMock(Shipment::class);

        return new Request(['order_shipment' => $shipment]);
    }

    private function createOrderRequest(): OrderRequest
    {
        return new OrderRequest(
            [new ShoppingCartPosition(10001, 'FRANKING_ZONE')],
            85,
            1,
        );
    }

    private function createSendRequestStage(QueueHttpClient $httpClient): SendRequestStage
    {
        return new SendRequestStage(new HttpMockServiceFactory($httpClient));
    }

    private function createMapResponseStage(): MapResponseStage
    {
        return Bootstrap::getObjectManager()->create(MapResponseStage::class);
    }

    /**
     * Happy path: PDF order with 2 vouchers, first has trackId.
     *
     * HTTP queue: auth → checkout PDF → label binary → manifest binary
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function sendAndMapResponseCreatesPdfLabel(): void
    {
        $httpClient = new QueueHttpClient();
        $httpClient->addResponse(HttpResponseFactory::authSuccess());
        $httpClient->addResponse(HttpResponseFactory::checkoutPdfSuccess());
        $httpClient->addResponse(HttpResponseFactory::binaryContent(self::LABEL_PDF));
        $httpClient->addResponse(HttpResponseFactory::binaryContent(self::MANIFEST_PDF));

        $container = new ArtifactsContainer();
        $container->addApiRequest(self::REQUEST_INDEX, $this->createOrderRequest());

        $request = $this->createShipmentRequest();
        $requests = [self::REQUEST_INDEX => $request];

        $requests = $this->createSendRequestStage($httpClient)->execute($requests, $container);
        $this->createMapResponseStage()->execute($requests, $container);

        // API response preserved in container
        $apiResponses = $container->getApiResponses();
        self::assertCount(1, $apiResponses);
        self::assertArrayHasKey(self::REQUEST_INDEX, $apiResponses);

        $order = $apiResponses[self::REQUEST_INDEX];
        self::assertSame('98276337', $order->getShopOrderId());
        self::assertCount(2, $order->getVouchers());

        // Label response with correct tracking and voucher data
        $labelResponses = $container->getLabelResponses();
        self::assertCount(1, $labelResponses);

        /** @var LabelResponse $labelResponse */
        $labelResponse = $labelResponses[self::REQUEST_INDEX];
        self::assertSame('00340434161094042557', $labelResponse->getTrackingNumber());
        self::assertStringStartsWith('%PDF', $labelResponse->getShippingLabelContent());
        self::assertSame('98276337', $labelResponse->getShopOrderId());
        self::assertSame('A00123C0390000000138', $labelResponse->getVoucherId());
        self::assertSame('00340434161094042557', $labelResponse->getVoucherTrackId());

        self::assertEmpty($container->getErrorResponses());
    }

    /**
     * Voucher with empty trackId falls back to voucherId for tracking number.
     *
     * HTTP queue: auth → checkout PNG (trackId="") → label binary
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function sendAndMapResponseUsesVoucherIdAsFallbackTracking(): void
    {
        $httpClient = new QueueHttpClient();
        $httpClient->addResponse(HttpResponseFactory::authSuccess());
        $httpClient->addResponse(HttpResponseFactory::checkoutPngSuccess());
        $httpClient->addResponse(HttpResponseFactory::binaryContent('PNG binary content'));

        $container = new ArtifactsContainer();
        $container->addApiRequest(self::REQUEST_INDEX, $this->createOrderRequest());

        $request = $this->createShipmentRequest();
        $requests = [self::REQUEST_INDEX => $request];

        $requests = $this->createSendRequestStage($httpClient)->execute($requests, $container);
        $this->createMapResponseStage()->execute($requests, $container);

        $labelResponses = $container->getLabelResponses();
        self::assertCount(1, $labelResponses);

        /** @var LabelResponse $labelResponse */
        $labelResponse = $labelResponses[self::REQUEST_INDEX];
        self::assertSame('A00123C0390000000140', $labelResponse->getTrackingNumber());
        self::assertNull($labelResponse->getVoucherTrackId());

        self::assertEmpty($container->getErrorResponses());
    }

    /**
     * Empty vouchers array triggers error response instead of crash.
     *
     * Runs only MapResponseStage with a pre-built Order that has no vouchers.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function mapResponseCreatesErrorForEmptyVouchers(): void
    {
        $order = new Order('12345', [], 'fake-label', null, 1000);

        $container = new ArtifactsContainer();
        $container->addApiResponse(self::REQUEST_INDEX, $order);

        $request = $this->createShipmentRequest();
        $requests = [self::REQUEST_INDEX => $request];

        $this->createMapResponseStage()->execute($requests, $container);

        self::assertEmpty($container->getLabelResponses());

        $errorResponses = $container->getErrorResponses();
        self::assertCount(1, $errorResponses);
        self::assertStringContainsString(
            'API response contained no voucher',
            (string) $errorResponses[self::REQUEST_INDEX]->getErrors()[0],
        );
    }

    /**
     * API returns 400 Bad Request — captured as DetailedServiceException.
     *
     * HTTP queue: auth → 400 error response
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function sendRequestCapturesDetailedApiError(): void
    {
        $httpClient = new QueueHttpClient();
        $httpClient->addResponse(HttpResponseFactory::authSuccess());
        $httpClient->addResponse(HttpResponseFactory::error(400, 'badRequest400.json'));

        $container = new ArtifactsContainer();
        $container->addApiRequest(self::REQUEST_INDEX, $this->createOrderRequest());

        $request = $this->createShipmentRequest();
        $requests = [self::REQUEST_INDEX => $request];

        $remainingRequests = $this->createSendRequestStage($httpClient)->execute($requests, $container);

        self::assertEmpty($container->getApiResponses());
        self::assertEmpty($remainingRequests);

        $errors = $container->getErrors();
        self::assertCount(1, $errors);
        self::assertStringContainsString('Bad Request', $errors[self::REQUEST_INDEX]['message']);
    }

    /**
     * HTTP transport fails (queue exhausted) — captured as generic ServiceException.
     *
     * HTTP queue: auth success only; order request exhausts the queue.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function sendRequestCapturesTransportError(): void
    {
        $httpClient = new QueueHttpClient();
        $httpClient->addResponse(HttpResponseFactory::authSuccess());

        $container = new ArtifactsContainer();
        $container->addApiRequest(self::REQUEST_INDEX, $this->createOrderRequest());

        $request = $this->createShipmentRequest();
        $requests = [self::REQUEST_INDEX => $request];

        $remainingRequests = $this->createSendRequestStage($httpClient)->execute($requests, $container);

        self::assertEmpty($remainingRequests);

        $errors = $container->getErrors();
        self::assertCount(1, $errors);
        self::assertSame('Web service request failed.', $errors[self::REQUEST_INDEX]['message']);
    }
}
