<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\Pipeline\Shipment\ResponseProcessor;

use DeutschePost\Internetmarke\Model\Pipeline\ApiGateway;
use Dhl\ShippingCore\Api\Data\Pipeline\ShipmentResponse\LabelResponseInterface;
use Dhl\ShippingCore\Api\Data\Pipeline\ShipmentResponse\ShipmentErrorResponseInterface;
use Dhl\ShippingCore\Api\Pipeline\ShipmentResponseProcessorInterface;
use Magento\Sales\Model\Order\Shipment;

class SetLabelApi implements ShipmentResponseProcessorInterface
{
    /**
     * Set an identifier flag for the API that was used to create the label to the shipment.
     *
     * @param LabelResponseInterface[] $labelResponses
     * @param ShipmentErrorResponseInterface[] $errorResponses
     */
    public function processResponse(array $labelResponses, array $errorResponses)
    {
        array_walk(
            $labelResponses,
            function (LabelResponseInterface $labelResponse) {
                /** @var Shipment $shipment */
                $shipment = $labelResponse->getSalesShipment();
                $shipment->setData('dpdhl_label_api', ApiGateway::API_IDENTIFIER);
            }
        );
    }
}
