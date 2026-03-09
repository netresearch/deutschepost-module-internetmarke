<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Test\Integration\TestCase\Pipeline;

use DeutschePost\Internetmarke\Model\Pipeline\DeleteShipments\ArtifactsContainer;
use DeutschePost\Internetmarke\Model\Pipeline\DeleteShipments\ResponseDataMapper;
use DeutschePost\Internetmarke\Model\Pipeline\DeleteShipments\Stage\RequestRefundStage;
use DeutschePost\Internetmarke\Test\Integration\TestDouble\HttpMockServiceFactory;
use DeutschePost\Internetmarke\Test\Integration\TestDouble\HttpResponseFactory;
use DeutschePost\Internetmarke\Test\Integration\TestDouble\QueueHttpClient;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\Data\ShipmentTrackExtensionInterface;
use Magento\Sales\Api\Data\ShipmentTrackInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Netresearch\ShippingCore\Model\Pipeline\Track\TrackRequest\TrackRequest;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for the Internetmarke shipment deletion (refund) pipeline.
 *
 * Exercises RequestRefundStage with real SDK code,
 * mocked only at the HTTP transport level via QueueHttpClient.
 *
 * @magentoAppArea adminhtml
 */
class DeleteShipmentsPipelineTest extends TestCase
{
    private const TRACK_NUMBER = '00340434161094042557';
    private const SHOP_ORDER_ID = '98276337';
    private const VOUCHER_ID = 'A00123C0390000000138';
    private const TRACK_ID = '00340434161094042557';

    private function createTrackRequest(): TrackRequest
    {
        $extensionAttributes = $this->createMock(ShipmentTrackExtensionInterface::class);
        $extensionAttributes->method('getDpdhlOrderId')->willReturn(self::SHOP_ORDER_ID);
        $extensionAttributes->method('getDpdhlVoucherId')->willReturn(self::VOUCHER_ID);
        $extensionAttributes->method('getDpdhlTrackId')->willReturn(self::TRACK_ID);

        $salesTrack = $this->createMock(ShipmentTrackInterface::class);
        $salesTrack->method('getTrackNumber')->willReturn(self::TRACK_NUMBER);
        $salesTrack->method('getExtensionAttributes')->willReturn($extensionAttributes);

        $salesShipment = $this->createMock(ShipmentInterface::class);

        return new TrackRequest(1, self::TRACK_NUMBER, $salesShipment, $salesTrack);
    }

    private function createRequestRefundStage(QueueHttpClient $httpClient): RequestRefundStage
    {
        return new RequestRefundStage(
            new HttpMockServiceFactory($httpClient),
            Bootstrap::getObjectManager()->create(ResponseDataMapper::class),
        );
    }

    /**
     * Happy path: refund request succeeds.
     *
     * HTTP queue: auth → refund success
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function requestRefundSuccess(): void
    {
        $httpClient = new QueueHttpClient();
        $httpClient->addResponse(HttpResponseFactory::authSuccess());
        $httpClient->addResponse(HttpResponseFactory::refundSuccess());

        $container = new ArtifactsContainer();
        $request = $this->createTrackRequest();
        $requests = [self::TRACK_NUMBER => $request];

        $remainingRequests = $this->createRequestRefundStage($httpClient)->execute($requests, $container);

        self::assertCount(1, $container->getTrackResponses());
        self::assertArrayHasKey(self::TRACK_NUMBER, $container->getTrackResponses());
        self::assertEmpty($container->getErrorResponses());
        self::assertCount(1, $remainingRequests);
        self::assertSame($request, $remainingRequests[self::TRACK_NUMBER]);
    }

    /**
     * API returns 400 Bad Request — captured as error response.
     *
     * HTTP queue: auth → 400 error response
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function requestRefundCapturesApiError(): void
    {
        $httpClient = new QueueHttpClient();
        $httpClient->addResponse(HttpResponseFactory::authSuccess());
        $httpClient->addResponse(HttpResponseFactory::error(400, 'badRequest400.json'));

        $container = new ArtifactsContainer();
        $requests = [self::TRACK_NUMBER => $this->createTrackRequest()];

        $remainingRequests = $this->createRequestRefundStage($httpClient)->execute($requests, $container);

        self::assertEmpty($container->getTrackResponses());
        self::assertCount(1, $container->getErrorResponses());
        self::assertStringContainsString(
            self::TRACK_NUMBER,
            $container->getErrorResponses()[self::TRACK_NUMBER]->getErrors()[0],
        );
        self::assertEmpty($remainingRequests);
    }

    /**
     * HTTP transport fails (queue exhausted) — captured as error response.
     *
     * HTTP queue: auth success only; refund request exhausts the queue.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function requestRefundCapturesTransportError(): void
    {
        $httpClient = new QueueHttpClient();
        $httpClient->addResponse(HttpResponseFactory::authSuccess());

        $container = new ArtifactsContainer();
        $requests = [self::TRACK_NUMBER => $this->createTrackRequest()];

        $remainingRequests = $this->createRequestRefundStage($httpClient)->execute($requests, $container);

        self::assertCount(1, $container->getErrorResponses());
        self::assertStringContainsString(
            self::TRACK_NUMBER,
            $container->getErrorResponses()[self::TRACK_NUMBER]->getErrors()[0],
        );
        self::assertEmpty($remainingRequests);
    }
}
