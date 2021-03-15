<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\ShippingSettings\TypeProcessor\ShippingOptions;

use DeutschePost\Internetmarke\Model\ProductList\SalesProductCollectionLoader;
use Dhl\Paket\Model\Carrier\Paket;
use Dhl\Paket\Model\ShipmentDate\ShipmentDate;
use Magento\Sales\Api\Data\ShipmentInterface;
use Netresearch\ShippingCore\Api\Config\ShippingConfigInterface;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOption\OptionInterfaceFactory;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOptionInterface;
use Netresearch\ShippingCore\Api\ShippingSettings\TypeProcessor\ShippingOptionsProcessorInterface;
use Netresearch\ShippingCore\Model\ShippingSettings\ShippingOption\Codes;

class ShippingProductsProcessor implements ShippingOptionsProcessorInterface
{
    /**
     * @var ShippingConfigInterface
     */
    private $shippingConfig;

    /**
     * @var SalesProductCollectionLoader
     */
    private $productCollectionLoader;

    /**
     * @var ShipmentDate
     */
    private $shipmentDate;

    /**
     * @var OptionInterfaceFactory
     */
    private $optionFactory;

    public function __construct(
        ShippingConfigInterface $shippingConfig,
        SalesProductCollectionLoader $productCollectionLoader,
        ShipmentDate $shipmentDate,
        OptionInterfaceFactory $optionFactory
    ) {
        $this->shippingConfig = $shippingConfig;
        $this->productCollectionLoader = $productCollectionLoader;
        $this->shipmentDate = $shipmentDate;
        $this->optionFactory = $optionFactory;
    }

    /**
     * @param string $carrierCode
     * @param array $shippingOptions
     * @param int $storeId
     * @param string $countryCode
     * @param string $postalCode
     * @param ShipmentInterface|null $shipment
     *
     * @return ShippingOptionInterface[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function process(
        string $carrierCode,
        array $shippingOptions,
        int $storeId,
        string $countryCode,
        string $postalCode,
        ShipmentInterface $shipment = null
    ): array {
        $order = $shipment->getOrder();
        $carrierCode = strtok((string) $order->getShippingMethod(), '_');

        if ($carrierCode !== Paket::CARRIER_CODE) {
            return $shippingOptions;
        }

        $shipmentDate = $this->shipmentDate->getDate($shipment->getStoreId());
        $originCountry = $this->shippingConfig->getOriginCountry($shipment->getStoreId());
        $destinationCountry = $order->getShippingAddress()->getCountryId();

        $productCollection = $this->productCollectionLoader->getCollectionByDate($shipmentDate);
        $productCollection->setRouteFilter($originCountry, $destinationCountry);
        $dpProducts = [];

        foreach ($productCollection->getItems() as $salesProduct) {
            $option = $this->optionFactory->create();
            $option->setValue((string) $salesProduct->getPPLId());
            $option->setLabel('Deutsche Post ' . $salesProduct->getName());

            $dpProducts[] = $option;
        }

        foreach ($shippingOptions as $optionGroup) {
            foreach ($optionGroup->getInputs() as $input) {
                if ($input->getCode() === Codes::PACKAGE_INPUT_PRODUCT_CODE) {
                    $products = array_merge($input->getOptions(), $dpProducts);
                    $input->setOptions($products);
                    break 2;
                }
            }
        }

        return $shippingOptions;
    }
}
