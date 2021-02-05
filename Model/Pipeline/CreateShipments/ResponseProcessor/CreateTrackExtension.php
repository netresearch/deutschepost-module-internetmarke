<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\Pipeline\CreateShipments\ResponseProcessor;

use DeutschePost\Internetmarke\Api\Data\TrackAdditionalInterfaceFactory;
use DeutschePost\Internetmarke\Model\Pipeline\CreateShipments\ShipmentResponse\LabelResponse;
use DeutschePost\Internetmarke\Model\Shipment\TrackAdditional;
use Dhl\ShippingCore\Api\Data\Pipeline\ShipmentResponse\LabelResponseInterface;
use Dhl\ShippingCore\Api\Data\Pipeline\ShipmentResponse\ShipmentErrorResponseInterface;
use Dhl\ShippingCore\Api\Pipeline\ShipmentResponseProcessorInterface;
use Magento\Sales\Model\Order\Shipment;
use Psr\Log\LoggerInterface;

class CreateTrackExtension implements ShipmentResponseProcessorInterface
{
    public const TRACK_EXTENSION_KEY = 'deutschepost_shipment_track';

    /**
     * @var TrackAdditionalInterfaceFactory
     */
    private $factory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        TrackAdditionalInterfaceFactory $factory,
        LoggerInterface $logger
    ) {
        $this->factory = $factory;
        $this->logger = $logger;
    }

    /**
     * Initialize additional tracking data.
     *
     * The model for additional Deutsche Post tracking attributes gets created here.
     * It does not get persisted for now because the `sales_shipment_track` has
     * no ID yet. Instead, the extension attributes model gets temporarily added to
     * the shipment to be accessed and saved once the missing track ID becomes available.
     *
     * @see \DeutschePost\Internetmarke\Plugin\Sales\Order\Shipment\Track\SaveTrackExtension
     *
     * @param LabelResponseInterface[] $labelResponses
     * @param ShipmentErrorResponseInterface[] $errorResponses
     */
    public function processResponse(array $labelResponses, array $errorResponses)
    {
        /** @var LabelResponse $labelResponse */
        foreach ($labelResponses as $labelResponse) {
            $additionalData = [
                TrackAdditional::SHOP_ORDER_ID => $labelResponse->getShopOrderId(),
                TrackAdditional::VOUCHER_ID => $labelResponse->getVoucherId(),
                TrackAdditional::VOUCHER_TRACK_ID => $labelResponse->getVoucherTrackId(),
            ];

            try {
                /** @var Shipment $shipment */
                $shipment = $labelResponse->getSalesShipment();

                /** @var TrackAdditional $trackAdditional */
                $trackAdditional = $this->factory->create(['data' => $additionalData]);

                $shipmentTracks = $shipment->getData(self::TRACK_EXTENSION_KEY);
                if (!is_array($shipmentTracks)) {
                    $shipmentTracks = [$trackAdditional];
                } else {
                    $shipmentTracks[] = $trackAdditional;
                }
                $shipment->setData(self::TRACK_EXTENSION_KEY, $shipmentTracks);
            } catch (\Exception $exception) {
                $this->logger->error($exception->getMessage(), ['exception' => $exception]);
            }
        }
    }
}
