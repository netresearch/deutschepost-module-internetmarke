<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Test\Integration\TestCase\Pipeline;

use DeutschePost\Internetmarke\Api\Data\PageFormatInterface;
use DeutschePost\Internetmarke\Model\Config\ModuleConfig;
use DeutschePost\Internetmarke\Model\Pipeline\CreateShipments\ArtifactsContainer;
use DeutschePost\Internetmarke\Model\Pipeline\CreateShipments\Stage\MapRequestStage;
use DeutschePost\Internetmarke\Model\ProductList\SalesProductCollectionLoader;
use DeutschePost\Internetmarke\Model\ResourceModel\ProductList\SalesProductCollection;
use Dhl\Paket\Model\ShipmentDate\ShipmentDate;
use Magento\Sales\Model\Order\Shipment;
use Magento\Shipping\Model\Shipment\Request;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentRequest\PackageInterface;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentRequest\RecipientInterface;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentRequest\ShipperInterface;
use Netresearch\ShippingCore\Api\Pipeline\ShipmentRequest\RequestExtractorInterface;
use Netresearch\ShippingCore\Api\Pipeline\ShipmentRequest\RequestExtractorInterfaceFactory;
use Netresearch\ShippingCore\Api\Util\CountryCodeConverterInterface;
use PHPUnit\Framework\TestCase;

/**
 * Exposes a bug in MapRequestStage when a shipment has multiple packages
 * and a later package has an unknown product code.
 *
 * The `break` statement in the inner package loop (instead of `continue 2`)
 * causes a partial OrderRequest to be created from the valid package(s)
 * while also recording an error. This inconsistent state leads to problems
 * in SendRequestStage, which iterates apiRequests but the request has been
 * removed from the $requests array due to the error.
 *
 * @magentoAppArea adminhtml
 */
class MapRequestStageTest extends TestCase
{
    private const VALID_PRODUCT_CODE = 10001;
    private const INVALID_PRODUCT_CODE = 99999;
    private const PRODUCT_PRICE = 85;
    private const PAGE_FORMAT_ID = 1;
    private const REQUEST_INDEX = 0;

    private function createPageFormat(): PageFormatInterface
    {
        $pageFormat = $this->createMock(PageFormatInterface::class);
        $pageFormat->method('getId')->willReturn(self::PAGE_FORMAT_ID);
        $pageFormat->method('isAddressPossible')->willReturn(true);
        $pageFormat->method('getVoucherColumns')->willReturn(1);
        $pageFormat->method('getVoucherRows')->willReturn(1);

        return $pageFormat;
    }

    private function createShipper(): ShipperInterface
    {
        $shipper = $this->createMock(ShipperInterface::class);
        $shipper->method('getContactCompanyName')->willReturn('Test GmbH');
        $shipper->method('getStreetName')->willReturn('Teststraße');
        $shipper->method('getStreetNumber')->willReturn('1');
        $shipper->method('getPostalCode')->willReturn('04229');
        $shipper->method('getCity')->willReturn('Leipzig');
        $shipper->method('getCountryCode')->willReturn('DE');

        return $shipper;
    }

    private function createRecipient(): RecipientInterface
    {
        $recipient = $this->createMock(RecipientInterface::class);
        $recipient->method('getContactPersonFirstName')->willReturn('Max');
        $recipient->method('getContactPersonLastName')->willReturn('Mustermann');
        $recipient->method('getStreetName')->willReturn('Musterweg');
        $recipient->method('getStreetNumber')->willReturn('42');
        $recipient->method('getPostalCode')->willReturn('53113');
        $recipient->method('getCity')->willReturn('Bonn');
        $recipient->method('getCountryCode')->willReturn('DE');
        $recipient->method('getContactCompanyName')->willReturn('');
        $recipient->method('getAddressAddition')->willReturn('');

        return $recipient;
    }

    /**
     * Create a package mock with the given product code.
     */
    private function createPackage(int $productCode): PackageInterface
    {
        $package = $this->createMock(PackageInterface::class);
        $package->method('getProductCode')->willReturn((string) $productCode);

        return $package;
    }

    private function createShipmentRequest(): Request
    {
        $shipment = $this->createMock(Shipment::class);

        return new Request(['order_shipment' => $shipment]);
    }

    /**
     * Build a MapRequestStage with mocked dependencies.
     *
     * @param int[] $productPrices PPL ID => price
     */
    private function createMapRequestStage(array $productPrices, array $packages): MapRequestStage
    {
        $shipper = $this->createShipper();
        $recipient = $this->createRecipient();

        $requestExtractor = $this->createMock(RequestExtractorInterface::class);
        $requestExtractor->method('getShipper')->willReturn($shipper);
        $requestExtractor->method('getRecipient')->willReturn($recipient);
        $requestExtractor->method('getPackages')->willReturn($packages);

        $requestExtractorFactory = $this->createMock(RequestExtractorInterfaceFactory::class);
        $requestExtractorFactory->method('create')->willReturn($requestExtractor);

        // Mock SalesProductCollectionLoader to return a collection with known prices.
        // SalesProductCollection extends AbstractCollection (IteratorAggregate), so we mock
        // getIterator to yield product objects with getPPLId() and getPrice() methods.
        $productItems = [];
        foreach ($productPrices as $pplId => $price) {
            $product = new class($pplId, $price) {
                private int $pplId;
                private int $price;

                public function __construct(int $pplId, int $price)
                {
                    $this->pplId = $pplId;
                    $this->price = $price;
                }

                public function getPPLId(): int
                {
                    return $this->pplId;
                }

                public function getPrice(): int
                {
                    return $this->price;
                }
            };
            $productItems[] = $product;
        }

        $productCollection = $this->createMock(SalesProductCollection::class);
        $productCollection->method('getIterator')->willReturn(new \ArrayIterator($productItems));

        $productCollectionLoader = $this->createMock(SalesProductCollectionLoader::class);
        $productCollectionLoader->method('getCollectionByDate')->willReturn($productCollection);

        $shipmentDate = $this->createMock(ShipmentDate::class);
        $shipmentDate->method('getDate')->willReturn(new \DateTime());

        $config = $this->createMock(ModuleConfig::class);
        $config->method('getPageFormat')->willReturn($this->createPageFormat());

        $country = $this->createMock(CountryCodeConverterInterface::class);
        $country->method('convert')->willReturnArgument(0);

        return new MapRequestStage(
            $shipmentDate,
            $productCollectionLoader,
            $config,
            $requestExtractorFactory,
            $country,
        );
    }

    /**
     * When a shipment has two packages and the second has an unknown product code,
     * no API request should be created. The request should be recorded as error only.
     *
     * With the current `break` bug, the first (valid) package produces a position,
     * then `break` exits the inner loop. Since `$positions` is non-empty, a partial
     * OrderRequest is created AND added to apiRequests — while an error is also recorded.
     * This inconsistency causes SendRequestStage to crash when accessing $requests[$requestIndex].
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function multiPackageWithInvalidSecondProductCreatesNoApiRequest(): void
    {
        $packages = [
            1 => $this->createPackage(self::VALID_PRODUCT_CODE),
            2 => $this->createPackage(self::INVALID_PRODUCT_CODE),
        ];

        $stage = $this->createMapRequestStage(
            [self::VALID_PRODUCT_CODE => self::PRODUCT_PRICE],
            $packages,
        );

        $container = new ArtifactsContainer();
        $container->setStoreId(1);

        $request = $this->createShipmentRequest();
        $requests = [self::REQUEST_INDEX => $request];

        $remainingRequests = $stage->execute($requests, $container);

        // The error should be recorded for the invalid product code.
        $errors = $container->getErrors();
        self::assertCount(1, $errors, 'Expected exactly one error for the invalid product code.');
        self::assertArrayHasKey(self::REQUEST_INDEX, $errors);
        self::assertStringContainsString('99999', $errors[self::REQUEST_INDEX]['message']);

        // The request with an error should be removed from remaining requests.
        self::assertEmpty($remainingRequests, 'Request with mapping error should not be passed to next stage.');

        // BUG ASSERTION: No API request should have been created for a shipment that has an error.
        // With the `break` bug, a partial OrderRequest IS created (from the first valid package),
        // leading to inconsistent state between apiRequests and the returned $requests.
        self::assertEmpty(
            $container->getApiRequests(),
            'A shipment with a package mapping error must not produce an API request. '
            . 'The partial OrderRequest from valid packages leads to inconsistent state: '
            . 'SendRequestStage will iterate apiRequests but the request was removed from $requests, '
            . 'causing a fatal error when accessing $requests[$requestIndex].'
        );
    }

    /**
     * When a shipment has two packages and the FIRST has an unknown product code,
     * the `break` prevents the second (valid) package from being processed.
     * No API request should be created either way, but this documents the
     * different behavior depending on package order.
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function multiPackageWithInvalidFirstProductCreatesNoApiRequest(): void
    {
        $packages = [
            1 => $this->createPackage(self::INVALID_PRODUCT_CODE),
            2 => $this->createPackage(self::VALID_PRODUCT_CODE),
        ];

        $stage = $this->createMapRequestStage(
            [self::VALID_PRODUCT_CODE => self::PRODUCT_PRICE],
            $packages,
        );

        $container = new ArtifactsContainer();
        $container->setStoreId(1);

        $request = $this->createShipmentRequest();
        $requests = [self::REQUEST_INDEX => $request];

        $remainingRequests = $stage->execute($requests, $container);

        $errors = $container->getErrors();
        self::assertCount(1, $errors);
        self::assertEmpty($remainingRequests);

        // When the first package is invalid, `break` exits before any positions
        // are created, so `$positions` is empty and no OrderRequest is created.
        // This test passes even with the bug — it serves as a control case.
        self::assertEmpty($container->getApiRequests());
    }
}
