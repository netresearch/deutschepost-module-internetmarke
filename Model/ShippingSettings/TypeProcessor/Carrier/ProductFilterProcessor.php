<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\ShippingSettings\TypeProcessor\Carrier;

use Dhl\Paket\Model\Carrier\Paket;
use Dhl\Paket\Model\ShippingSettings\ShippingOption\Codes;
use Magento\Sales\Api\Data\ShipmentInterface;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\CarrierDataInterface;
use Netresearch\ShippingCore\Api\Data\ShippingSettings\ShippingOption\OptionInterface;
use Netresearch\ShippingCore\Api\ShippingSettings\TypeProcessor\CarrierDataProcessorInterface;
use Netresearch\ShippingCore\Model\ShippingSettings\ShippingOption\Codes as CoreCodes;

/**
 * Remove Deutsche Post shipping products if a DHL service was selected during checkout.
 */
class ProductFilterProcessor implements CarrierDataProcessorInterface
{
    public function process(
        CarrierDataInterface $shippingSettings,
        int $storeId,
        string $countryCode,
        string $postalCode,
        ShipmentInterface $shipment = null
    ): CarrierDataInterface {
        if ($shippingSettings->getCode() !== Paket::CARRIER_CODE) {
            // different carrier, nothing to modify.
            return $shippingSettings;
        }

        $selectedServices = array_intersect_key(
            [
                Codes::SERVICE_OPTION_PREFERRED_DAY => Codes::SERVICE_OPTION_PREFERRED_DAY,
                Codes::SERVICE_OPTION_DROPOFF_DELIVERY => Codes::SERVICE_OPTION_DROPOFF_DELIVERY,
                Codes::SERVICE_OPTION_NEIGHBOR_DELIVERY => Codes::SERVICE_OPTION_NEIGHBOR_DELIVERY,
                CoreCodes::SERVICE_OPTION_DELIVERY_LOCATION => CoreCodes::SERVICE_OPTION_DELIVERY_LOCATION,
                CoreCodes::SERVICE_OPTION_CASH_ON_DELIVERY => CoreCodes::SERVICE_OPTION_CASH_ON_DELIVERY,
            ],
            $shippingSettings->getServiceOptions()
        );

        if (empty($selectedServices)) {
            // no DHL services selected, proceed
            return $shippingSettings;
        }

        $productInputCode = CoreCodes::PACKAGE_INPUT_PRODUCT_CODE;
        foreach ($shippingSettings->getPackageOptions() as $packagingOption) {
            foreach ($packagingOption->getInputs() as $input) {
                if ($input->getCode() !== $productInputCode) {
                    // not the "shipping product" input, next
                    continue;
                }

                $options = array_filter($input->getOptions(), static function (OptionInterface $option) {
                    // remove inapplicable options
                    return (strpos($option->getLabel(), 'Deutsche Post') !== 0);
                });

                $input->setOptions($options);

                // all done, proceed
                return $shippingSettings;
            }
        }

        return $shippingSettings;
    }
}
