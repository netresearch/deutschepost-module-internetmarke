<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\ShippingSettings\Processor\Packaging;

use DeutschePost\Internetmarke\Model\ProductList\SalesProductCollectionLoader;
use Dhl\Paket\Model\Carrier\Paket;
use Dhl\Paket\Model\ShipmentDate\ShipmentDate;
use Dhl\ShippingCore\Api\Data\ShippingSettings\ShippingOption\OptionInterfaceFactory;
use Dhl\ShippingCore\Api\Data\ShippingSettings\ShippingOptionInterface;
use Dhl\ShippingCore\Api\ShippingConfigInterface;
use Dhl\ShippingCore\Api\ShippingSettings\Processor\Packaging\ShippingOptionsProcessorInterface;
use Dhl\ShippingCore\Model\ShippingSettings\ShippingOption\Codes;
use Magento\Sales\Api\Data\ShipmentInterface;

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
     * @param ShippingOptionInterface[] $optionsData
     * @param ShipmentInterface $shipment
     *
     * @return ShippingOptionInterface[]
     */
    public function process(array $optionsData, ShipmentInterface $shipment): array
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $shipment->getOrder();
        $carrierCode = strtok((string) $order->getShippingMethod(), '_');

        if ($carrierCode !== Paket::CARRIER_CODE) {
            return $optionsData;
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
        };

        foreach ($optionsData as $optionGroup) {
            foreach ($optionGroup->getInputs() as $input) {
                if ($input->getCode() === Codes::PACKAGING_INPUT_PRODUCT_CODE) {
                    $products = array_merge($input->getOptions(), $dpProducts);
                    $input->setOptions($products);
                    break 2;
                }
            }
        }

        return $optionsData;
    }
}
