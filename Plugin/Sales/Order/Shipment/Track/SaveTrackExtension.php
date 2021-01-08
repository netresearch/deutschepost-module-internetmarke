<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Plugin\Sales\Order\Shipment\Track;

use DeutschePost\Internetmarke\Model\Pipeline\CreateShipments\ResponseProcessor\CreateTrackExtension;
use DeutschePost\Internetmarke\Model\ResourceModel\Shipment\TrackAdditional as TrackAdditionalResource;
use DeutschePost\Internetmarke\Model\Shipment\TrackAdditional;
use Dhl\Paket\Model\Carrier\Paket;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Shipment\Track;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Track as TrackResource;
use Psr\Log\LoggerInterface;

class SaveTrackExtension
{
    /**
     * @var TrackAdditionalResource
     */
    private $resource;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        TrackAdditionalResource $resource,
        LoggerInterface $logger
    ) {
        $this->resource = $resource;
        $this->logger = $logger;
    }

    /**
     * Persist extension attributes,
     *
     * The additional Deutsche Post tracking attributes are prepared
     * and set to the shipment during pipeline execution. Now that the
     * `sales_shipment_track` has an entity ID, the missing foreign
     * key can be added to the extension attributes model. Finally, the
     * model gets persisted.
     *
     * @see \DeutschePost\Internetmarke\Model\Pipeline\CreateShipments\ResponseProcessor\CreateTrackExtension
     *
     * @param TrackResource $subject
     * @param TrackResource $result
     * @param Track $track
     * @return TrackResource
     */
    public function afterSave(TrackResource $subject, TrackResource $result, Track $track): TrackResource
    {
        if ($track->getCarrierCode() !== Paket::CARRIER_CODE) {
            // DP tracks are created through the Parcel Germany carrier
            return $result;
        }

        try {
            $shipment = $track->getShipment();
        } catch (LocalizedException $exception) {
            return $result;
        }

        /** @var TrackAdditional[] $shipmentTracks */
        $shipmentTracks = $shipment->getData(CreateTrackExtension::TRACK_EXTENSION_KEY);
        if (!$shipmentTracks) {
            // not a DP shipment, regular DHL shipment
            return $result;
        }

        foreach ($shipmentTracks as $trackAdditional) {
            try {
                $trackAdditional->setData(TrackAdditional::TRACK_ID, $track->getEntityId());
                $this->resource->save($trackAdditional);
            } catch (\Exception $exception) {
                $this->logger->error($exception->getMessage(), ['exception' => $exception]);
            }
        }

        return $result;
    }
}
