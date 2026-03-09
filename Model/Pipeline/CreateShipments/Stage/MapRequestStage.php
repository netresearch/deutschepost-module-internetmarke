<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\Pipeline\CreateShipments\Stage;

use DeutschePost\Internetmarke\Model\Config\ModuleConfig;
use DeutschePost\Internetmarke\Model\Pipeline\CreateShipments\ArtifactsContainer;
use DeutschePost\Internetmarke\Model\ProductList\SalesProductCollectionLoader;
use DeutschePost\Sdk\Internetmarke\Api\ShoppingCartPositionBuilderInterface;
use DeutschePost\Sdk\Internetmarke\Api\VoucherLayout;
use DeutschePost\Sdk\Internetmarke\Model\OrderRequest;
use DeutschePost\Sdk\Internetmarke\Model\ShoppingCartPositionBuilder;
use Dhl\Paket\Model\ShipmentDate\ShipmentDate;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Shipping\Model\Shipment\Request;
use Netresearch\ShippingCore\Api\Data\Pipeline\ArtifactsContainerInterface;
use Netresearch\ShippingCore\Api\Pipeline\CreateShipmentsStageInterface;
use Netresearch\ShippingCore\Api\Pipeline\ShipmentRequest\RequestExtractorInterfaceFactory;
use Netresearch\ShippingCore\Api\Util\CountryCodeConverterInterface;

class MapRequestStage implements CreateShipmentsStageInterface
{
    /**
     * @var ShipmentDate
     */
    private $shipmentDate;

    /**
     * @var SalesProductCollectionLoader
     */
    private $productCollectionLoader;

    /**
     * @var ModuleConfig
     */
    private $config;

    /**
     * @var RequestExtractorInterfaceFactory
     */
    private $requestExtractorFactory;

    /**
     * @var CountryCodeConverterInterface
     */
    private $country;

    public function __construct(
        ShipmentDate $shipmentDate,
        SalesProductCollectionLoader $productCollectionLoader,
        ModuleConfig $config,
        RequestExtractorInterfaceFactory $requestExtractorFactory,
        CountryCodeConverterInterface $country
    ) {
        $this->shipmentDate = $shipmentDate;
        $this->productCollectionLoader = $productCollectionLoader;
        $this->config = $config;
        $this->requestExtractorFactory = $requestExtractorFactory;
        $this->country = $country;
    }

    /**
     * Retrieve price per product.
     *
     * @param int $storeId
     * @return int[] Prices, indexed by product PPL ID
     * @throws LocalizedException
     */
    private function getPrices(int $storeId): array
    {
        $prices = [];
        $shipmentDate = $this->shipmentDate->getDate($storeId);
        $productCollection = $this->productCollectionLoader->getCollectionByDate($shipmentDate);
        foreach ($productCollection as $product) {
            $prices[$product->getPPLId()] = $product->getPrice();
        }

        if (empty($prices)) {
            throw new LocalizedException(__('Please update shipping products in the module configuration.'));
        }

        return $prices;
    }

    /**
     * Transform core shipment requests into request objects suitable for the label API.
     *
     * Each shipment request is mapped to an individual OrderRequest so that each
     * shipment receives its own label from the API.
     *
     * Requests with mapping errors are removed from requests and instantly added as error responses.
     *
     * @param Request[] $requests
     * @param ArtifactsContainerInterface|ArtifactsContainer $artifactsContainer
     * @return Request[]
     */
    #[\Override]
    public function execute(array $requests, ArtifactsContainerInterface $artifactsContainer): array
    {
        $pageFormat = $this->config->getPageFormat();

        try {
            $productPrices = $this->getPrices($artifactsContainer->getStoreId());
        } catch (LocalizedException $exception) {
            // mark all requests as failed
            foreach ($requests as $requestIndex => $shipmentRequest) {
                $artifactsContainer->addError(
                    $requestIndex,
                    $shipmentRequest->getOrderShipment(),
                    $exception->getMessage()
                );
            }

            // no requests passed the stage
            return [];
        }

        if (!$pageFormat) {
            foreach ($requests as $requestIndex => $shipmentRequest) {
                $artifactsContainer->addError(
                    $requestIndex,
                    $shipmentRequest->getOrderShipment(),
                    (string) __('Please update page formats in the module configuration.')
                );
            }

            return [];
        }

        $voucherLayout = $pageFormat->isAddressPossible()
            ? VoucherLayout::AddressZone
            : VoucherLayout::FrankingZone;

        foreach ($requests as $requestIndex => $request) {
            $requestExtractor = $this->requestExtractorFactory->create(['shipmentRequest' => $request]);
            $shipper = $requestExtractor->getShipper();
            $recipient = $requestExtractor->getRecipient();

            try {
                $packages = $requestExtractor->getPackages();
            } catch (LocalizedException $exception) {
                $artifactsContainer->addError($requestIndex, $request->getOrderShipment(), 'Failed to read packages.');
                continue;
            }

            try {
                $shipperCountry = $this->country->convert($shipper->getCountryCode());
                $recipientCountry = $this->country->convert($recipient->getCountryCode());
            } catch (NoSuchEntityException $exception) {
                $artifactsContainer->addError(
                    $requestIndex,
                    $request->getOrderShipment(),
                    $exception->getMessage()
                );
                continue;
            }

            $builder = ShoppingCartPositionBuilder::forPageFormat(
                $pageFormat->getId(),
                $pageFormat->getVoucherColumns(),
                $pageFormat->getVoucherRows(),
            );
            $positions = [];

            foreach ($packages as $packageId => $package) {
                $productCode = (int) $package->getProductCode();
                if (!isset($productPrices[$productCode])) {
                    $artifactsContainer->addError(
                        $requestIndex,
                        $request->getOrderShipment(),
                        (string) __('Unknown product code: %1', $productCode)
                    );
                    continue 2;
                }

                $builder->setItemDetails($productCode, $productPrices[$productCode]);
                $builder->setVoucherLayout($voucherLayout);
                $builder->setSenderAddress(
                    $shipper->getContactCompanyName(),
                    trim($shipper->getStreetName() . ' ' . $shipper->getStreetNumber()),
                    $shipper->getPostalCode(),
                    $shipper->getCity(),
                    $shipperCountry
                );

                $builder->setRecipientAddress(
                    trim($recipient->getContactPersonFirstName() . ' ' . $recipient->getContactPersonLastName()),
                    trim($recipient->getStreetName() . ' ' . $recipient->getStreetNumber()),
                    $recipient->getPostalCode(),
                    $recipient->getCity(),
                    $recipientCountry,
                    $recipient->getContactCompanyName() ?: null,
                    $recipient->getAddressAddition() ?: null
                );

                $positions[] = $builder->create();
            }

            if (empty($positions)) {
                continue;
            }

            $orderRequest = new OrderRequest(
                $positions,
                $builder->getTotalAmount(),
                $pageFormat->getId()
            );
            $artifactsContainer->addApiRequest($requestIndex, $orderRequest);
        }

        // pass on all shipment requests with no mapping errors
        return array_diff_key($requests, $artifactsContainer->getErrors());
    }
}
