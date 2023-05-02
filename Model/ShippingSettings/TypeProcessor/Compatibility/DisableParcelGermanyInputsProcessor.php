<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\ShippingSettings\TypeProcessor\Compatibility;

use DeutschePost\Internetmarke\Model\ProductList\SalesProductCollectionLoader;
use Dhl\Paket\Model\Carrier\Paket;
use Dhl\Paket\Model\ShippingSettings\ShippingOption\Codes;
use Magento\Framework\Exception\LocalizedException;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOption\CompatibilityInterface;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOption\CompatibilityInterfaceFactory;
use Netresearch\ShippingCore\Api\Config\ShippingConfigInterface;
use Netresearch\ShippingCore\Api\ShippingSettings\TypeProcessor\CompatibilityProcessorInterface;
use Dhl\Paket\Model\ShipmentDate\ShipmentDate;
use Netresearch\ShippingCore\Model\ShippingSettings\ShippingOption\Codes as CoreCodes;
use Magento\Sales\Api\Data\ShipmentInterface;

/**
 * Disable inputs defined by DHL Paket which do not apply to Deutsche Post shipping products.
 */
class DisableParcelGermanyInputsProcessor implements CompatibilityProcessorInterface
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
     * @param string $carrierCode
     * @param array $rules
     * @param int $storeId
     * @param string $countryCode
     * @param string $postalCode
     * @param ShipmentInterface|null $shipment
     * @return CompatibilityInterface[]
     * @throws LocalizedException
     */
    public function process(
        string $carrierCode,
        array $rules,
        int $storeId,
        string $countryCode,
        string $postalCode,
        ShipmentInterface $shipment = null
    ): array {
        $order = $shipment->getOrder();
        $carrierCode = strtok((string) $order->getShippingMethod(), '_');

        if ($carrierCode !== Paket::CARRIER_CODE) {
            return $rules;
        }

        $incompatibleInputs = [
            // all inputs of the given service options
            Codes::SERVICE_OPTION_BULKY_GOODS,
            Codes::SERVICE_OPTION_CHECK_OF_AGE,
            Codes::SERVICE_OPTION_ENDORSEMENT,
            Codes::SERVICE_OPTION_INSURANCE,
            Codes::SERVICE_OPTION_PARCEL_OUTLET_ROUTING,
            Codes::SERVICE_OPTION_RETURN_SHIPMENT,
            Codes::SERVICE_OPTION_PRINT_ONLY_IF_CODEABLE,
            Codes::SERVICE_OPTION_PREMIUM,
            Codes::SERVICE_OPTION_NO_NEIGHBOR_DELIVERY,
            Codes::SERVICE_OPTION_PDDP,
            // some inputs of the "package details" package option
            sprintf('%s.%s', CoreCodes::PACKAGE_OPTION_DETAILS, CoreCodes::PACKAGE_INPUT_WIDTH),
            sprintf('%s.%s', CoreCodes::PACKAGE_OPTION_DETAILS, CoreCodes::PACKAGE_INPUT_LENGTH),
            sprintf('%s.%s', CoreCodes::PACKAGE_OPTION_DETAILS, CoreCodes::PACKAGE_INPUT_HEIGHT),
        ];

        $shipmentDate = $this->shipmentDate->getDate($shipment->getStoreId());
        $originCountry = $this->shippingConfig->getOriginCountry($shipment->getStoreId());
        $destinationCountry = $order->getShippingAddress()->getCountryId();

        $productCollection = $this->productCollectionLoader->getCollectionByDate($shipmentDate);
        $productCollection->setRouteFilter($originCountry, $destinationCountry);

        foreach ($productCollection->getItems() as $id => $salesProduct) {
            $rule = $this->compatibilityFactory->create();
            $rule->setId('disableParcelGermanyInputsForProduct' . $id);
            $rule->setAction('disable');
            $rule->setTriggerValue((string) $salesProduct->getPPLId());
            $rule->setMasters(['packageDetails.productCode']);
            $rule->setSubjects($incompatibleInputs);

            $rules[$rule->getId()] = $rule;
        }

        return $rules;
    }
}
