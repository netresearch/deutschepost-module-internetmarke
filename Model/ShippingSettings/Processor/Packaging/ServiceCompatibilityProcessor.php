<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\ShippingSettings\Processor\Packaging;

use DeutschePost\Internetmarke\Model\ProductList\SalesProductCollectionLoader;
use Dhl\Paket\Model\Carrier\Paket;
use Dhl\Paket\Model\ShipmentDate\ShipmentDate;
use Dhl\Paket\Model\ShippingSettings\ShippingOption\Codes;
use Dhl\ShippingCore\Api\Data\ShippingSettings\ShippingOption\CompatibilityInterface;
use Dhl\ShippingCore\Api\Data\ShippingSettings\ShippingOption\CompatibilityInterfaceFactory;
use Dhl\ShippingCore\Api\ShippingConfigInterface;
use Dhl\ShippingCore\Api\ShippingSettings\Processor\Packaging\CompatibilityProcessorInterface;
use Magento\Sales\Api\Data\ShipmentInterface;

/**
 * Disable DHL services if a Deutsche Post product was chosen as shipping product.
 */
class ServiceCompatibilityProcessor implements CompatibilityProcessorInterface
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
     * @var CompatibilityInterfaceFactory
     */
    private $compatibilityFactory;

    public function __construct(
        ShippingConfigInterface $shippingConfig,
        SalesProductCollectionLoader $productCollectionLoader,
        ShipmentDate $shipmentDate,
        CompatibilityInterfaceFactory $compatibilityFactory
    ) {
        $this->shippingConfig = $shippingConfig;
        $this->productCollectionLoader = $productCollectionLoader;
        $this->shipmentDate = $shipmentDate;
        $this->compatibilityFactory = $compatibilityFactory;
    }

    /**
     * @param CompatibilityInterface[] $compatibilityData
     * @param ShipmentInterface $shipment
     * @return CompatibilityInterface[]
     */
    public function process(array $compatibilityData, ShipmentInterface $shipment): array
    {
        $order = $shipment->getOrder();
        $carrierCode = strtok((string) $order->getShippingMethod(), '_');

        if ($carrierCode !== Paket::CARRIER_CODE) {
            return $compatibilityData;
        }

        $shipmentDate = $this->shipmentDate->getDate($shipment->getStoreId());
        $originCountry = $this->shippingConfig->getOriginCountry($shipment->getStoreId());
        $destinationCountry = $order->getShippingAddress()->getCountryId();

        $productCollection = $this->productCollectionLoader->getCollectionByDate($shipmentDate);
        $productCollection->setRouteFilter($originCountry, $destinationCountry);

        foreach ($productCollection->getItems() as $id => $salesProduct) {
            $rule = $this->compatibilityFactory->create();
            $rule->setId('disableNotSupportedInternetmarkeServices' . $id);
            $rule->setAction('disable');
            $rule->setTriggerValue((string) $salesProduct->getPPLId());
            $rule->setMasters(['packageDetails.productCode']);
            $rule->setSubjects([
                Codes::PACKAGING_SERVICE_BULKY_GOODS,
                Codes::PACKAGING_SERVICE_CHECK_OF_AGE,
                Codes::PACKAGING_SERVICE_INSURANCE,
                Codes::PACKAGING_SERVICE_PARCEL_OUTLET_ROUTING,
                Codes::PACKAGING_SERVICE_RETURN_SHIPMENT,
                Codes::PACKAGING_PRINT_ONLY_IF_CODEABLE
            ]);

            $compatibilityData[$rule->getId()] = $rule;
        }

        return $compatibilityData;
    }
}
