<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\Pipeline\CreateShipments\Stage;

use DeutschePost\Internetmarke\Model\Config\ModuleConfig;
use DeutschePost\Internetmarke\Model\Pipeline\CreateShipments\ArtifactsContainer;
use DeutschePost\Internetmarke\Model\Pipeline\CreateShipments\OrderFactory;
use DeutschePost\Internetmarke\Model\ProductList\SalesProductCollectionLoader;
use DeutschePost\Sdk\OneClickForApp\Api\Data\PageFormatInterface;
use DeutschePost\Sdk\OneClickForApp\Api\Data\PageFormatInterfaceFactory;
use DeutschePost\Sdk\OneClickForApp\Model\ShoppingCartPositionBuilder;
use Dhl\Paket\Model\ShipmentDate\ShipmentDate;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Shipping\Model\Shipment\Request;
use Netresearch\ShippingCore\Api\Data\Pipeline\ArtifactsContainerInterface;
use Netresearch\ShippingCore\Api\Pipeline\CreateShipmentsStageInterface;
use Netresearch\ShippingCore\Api\Pipeline\ShipmentRequest\RequestExtractorInterfaceFactory;
use Netresearch\ShippingCore\Api\Util\CountryCodeInterface;

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
     * @var PageFormatInterfaceFactory
     */
    private $pageFormatFactory;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var RequestExtractorInterfaceFactory
     */
    private $requestExtractorFactory;

    /**
     * @var CountryCodeInterface
     */
    private $country;

    public function __construct(
        ShipmentDate $shipmentDate,
        SalesProductCollectionLoader $productCollectionLoader,
        ModuleConfig $config,
        PageFormatInterfaceFactory $pageFormatFactory,
        OrderFactory $orderFactory,
        RequestExtractorInterfaceFactory $requestExtractorFactory,
        CountryCodeInterface $country
    ) {
        $this->shipmentDate = $shipmentDate;
        $this->productCollectionLoader = $productCollectionLoader;
        $this->config = $config;
        $this->pageFormatFactory = $pageFormatFactory;
        $this->orderFactory = $orderFactory;
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
     * Create SDK page format from local data for usage in the cart position builder.
     *
     * @return PageFormatInterface
     * @throws LocalizedException
     */
    private function getPageFormat(): PageFormatInterface
    {
        $pageFormat = $this->config->getPageFormat();
        if (!$pageFormat) {
            throw new LocalizedException(__('Please update page formats in the module configuration.'));
        }

        return $this->pageFormatFactory->create([
            'id' => $pageFormat->getId(),
            'name' => $pageFormat->getName(),
            'description' => $pageFormat->getDescription(),
            'orientation' => '',
            'printMedium' => '',
            'sizeX' => 0,
            'sizeY' => 0,
            'columns' => $pageFormat->getVoucherColumns(),
            'rows' => $pageFormat->getVoucherRows(),
            'addressPossible' => $pageFormat->isAddressPossible(),
            'imagePossible' => $pageFormat->isImagePossible()
        ]);
    }

    /**
     * Transform core shipment requests into request objects suitable for the label API.
     *
     * Requests with mapping errors are removed from requests and instantly added as error responses.
     *
     * @param Request[] $requests
     * @param ArtifactsContainerInterface|ArtifactsContainer $artifactsContainer
     * @return Request[]
     */
    public function execute(array $requests, ArtifactsContainerInterface $artifactsContainer): array
    {
        try {
            $productPrices = $this->getPrices($artifactsContainer->getStoreId());
            $pageFormat = $this->getPageFormat();
        } catch (LocalizedException $exception) {
            // mark all requests as failed
            foreach ($requests as $requestIndex => $shipmentRequest) {
                $artifactsContainer->addError(
                    (string) $requestIndex,
                    $shipmentRequest->getOrderShipment(),
                    $exception->getMessage()
                );
            }

            // no requests passed the stage
            return [];
        }

        $builder = ShoppingCartPositionBuilder::forPageFormat($pageFormat);

        $positions = [];
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

            foreach ($packages as $packageId => $package) {
                try {
                    $shipperCountry = $this->country->getIso3Code($shipper->getCountryCode());
                    $recipientCountry = $this->country->getIso3Code($recipient->getCountryCode());
                } catch (NoSuchEntityException $exception) {
                    $artifactsContainer->addError(
                        $requestIndex,
                        $request->getOrderShipment(),
                        $exception->getMessage()
                    );
                    break;
                }

                $builder->setItemDetails((int) $package->getProductCode(), $productPrices[$package->getProductCode()]);
                $builder->setShipperAddress(
                    $shipper->getContactCompanyName(),
                    $shipperCountry,
                    $shipper->getPostalCode(),
                    $shipper->getCity(),
                    $shipper->getStreetName(),
                    $shipper->getStreetNumber()
                );

                $builder->setRecipientAddress(
                    $recipient->getContactPersonLastName(),
                    $recipient->getContactPersonFirstName(),
                    $recipientCountry,
                    $recipient->getPostalCode(),
                    $recipient->getCity(),
                    $recipient->getStreetName(),
                    $recipient->getStreetNumber(),
                    null,
                    null,
                    $recipient->getContactCompanyName(),
                    $recipient->getAddressAddition()
                );

                $positions[] = $builder->create();
            }
        }

        $order = $this->orderFactory->create(
            [
                'amount' => $builder->getTotalAmount(),
                'pageFormatId' => $builder->getPageFormatId(),
                'positions' => $positions
            ]
        );

        $artifactsContainer->setApiRequest($order);

        // pass on all shipment requests with no mapping errors
        return array_diff_key($requests, $artifactsContainer->getErrors());
    }
}
