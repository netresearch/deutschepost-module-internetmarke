<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\ShippingSettings\Processor\Packaging;

use Dhl\Paket\Model\ShippingSettings\ShippingOption\Codes;
use Dhl\ShippingCore\Api\Data\ShippingSettings\CarrierDataInterface;
use Dhl\ShippingCore\Api\Data\ShippingSettings\ShippingOption\OptionInterface;
use Dhl\ShippingCore\Api\ShippingSettings\Processor\Checkout\GlobalProcessorInterface;

/**
 * Remove Deutsche Post shipping products if a DHL service was selected during checkout.
 */
class ProductFilterProcessor implements GlobalProcessorInterface
{
    public function process(CarrierDataInterface $carrierData): CarrierDataInterface
    {
        $selectedServices = array_intersect_key(
            [
                Codes::CHECKOUT_SERVICE_PREFERRED_DAY => Codes::CHECKOUT_SERVICE_PREFERRED_DAY,
                Codes::CHECKOUT_SERVICE_DROPOFF_DELIVERY => Codes::CHECKOUT_SERVICE_DROPOFF_DELIVERY,
                Codes::CHECKOUT_SERVICE_NEIGHBOR_DELIVERY => Codes::CHECKOUT_SERVICE_NEIGHBOR_DELIVERY,
                Codes::CHECKOUT_SERVICE_PARCELSHOP_FINDER => Codes::CHECKOUT_SERVICE_PARCELSHOP_FINDER,
                Codes::CHECKOUT_SERVICE_CASH_ON_DELIVERY => Codes::CHECKOUT_SERVICE_CASH_ON_DELIVERY,
            ],
            $carrierData->getServiceOptions()
        );

        if (empty($selectedServices)) {
            // no DHL services selected, proceed
            return $carrierData;
        }

        $productInputCode = \Dhl\ShippingCore\Model\ShippingSettings\ShippingOption\Codes::PACKAGING_INPUT_PRODUCT_CODE;
        foreach ($carrierData->getPackageOptions() as $packagingOption) {
            foreach ($packagingOption->getInputs() as $input) {
                if ($input->getCode() !== $productInputCode) {
                    // not the "shipping product" input, next
                    continue;
                }

                $options = array_filter($input->getOptions(), function (OptionInterface $option) {
                    // remove inapplicable options
                    return (strpos($option->getLabel(), 'Deutsche Post') !== 0);
                });

                $input->setOptions($options);

                // all done, proceed
                return $carrierData;
            }
        }

        return $carrierData;
    }
}
