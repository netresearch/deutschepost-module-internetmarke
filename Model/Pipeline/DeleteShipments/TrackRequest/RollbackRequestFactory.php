<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\Pipeline\DeleteShipments\TrackRequest;

use DeutschePost\Internetmarke\Model\Pipeline\CreateShipments\ResponseProcessor\CreateTrackExtension;
use DeutschePost\Internetmarke\Model\Shipment\TrackAdditional;
use Magento\Framework\Registry;
use Magento\Sales\Api\Data\ShipmentTrackExtensionInterfaceFactory;
use Magento\Sales\Api\Data\ShipmentTrackInterface;
use Magento\Sales\Api\Data\ShipmentTrackInterfaceFactory;
use Magento\Sales\Model\Order\Shipment;
use Netresearch\ShippingCore\Api\Data\Pipeline\TrackRequest\TrackRequestInterface;
use Netresearch\ShippingCore\Api\Data\Pipeline\TrackRequest\TrackRequestInterfaceFactory;

class RollbackRequestFactory
{
    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var ShipmentTrackInterfaceFactory
     */
    private $trackFactory;

    /**
     * @var ShipmentTrackExtensionInterfaceFactory
     */
    private $trackExtensionFactory;

    /**
     * @var TrackRequestInterfaceFactory
     */
    private $trackRequestFactory;

    public function __construct(
        Registry $registry,
        ShipmentTrackInterfaceFactory $trackFactory,
        ShipmentTrackExtensionInterfaceFactory $trackExtensionFactory,
        TrackRequestInterfaceFactory $trackRequestFactory
    ) {
        $this->registry = $registry;
        $this->trackFactory = $trackFactory;
        $this->trackExtensionFactory = $trackExtensionFactory;
        $this->trackRequestFactory = $trackRequestFactory;
    }

    /**
     * Find the additional tracking information for the given tracking number.
     *
     * A shipment may have multiple successful tracks collected before rollback. Find the one
     * that is currently requested for cancellation.
     *
     * @param string $trackNumber
     * @param TrackAdditional[] $trackAdditional
     * @return TrackAdditional|null
     */
    private function getTrackAdditional(string $trackNumber, array $trackAdditional): ?TrackAdditional
    {
        foreach ($trackAdditional as $additional) {
            if ($additional->getVoucherId() === $trackNumber || $additional->getVoucherTrackId() === $trackNumber) {
                return $additional;
            }
        }

        return null;
    }

    /**
     * Create an Internetmarke cancellation request for rolling back a failed shipment request.
     *
     * Cancellation requests created during rollback miss some data that is needed
     * during pipeline execution because shipment and track were not persisted. The
     * missing data is accessed via the registry. If the rollback does not refer to
     * an Internetmarke voucher, then the factory will not return a request object.
     *
     * @param int $storeId
     * @param string $trackNumber
     * @return TrackRequestInterface|null
     */
    public function create(int $storeId, string $trackNumber): ?TrackRequestInterface
    {
        $shipment = $this->registry->registry('current_shipment');
        if (!$shipment instanceof Shipment) {
            return null;
        }

        /** @var TrackAdditional[] $shipmentTracks */
        $shipmentTracks = $shipment->getData(CreateTrackExtension::TRACK_EXTENSION_KEY);
        if (!is_array($shipmentTracks)) {
            // not a DP shipment
            return null;
        }

        $trackAdditional = $this->getTrackAdditional($trackNumber, $shipmentTracks);
        if (!$trackAdditional) {
            // no data found for the current cancellation request
            return null;
        }

        $trackExtension = $this->trackExtensionFactory->create();
        $trackExtension->setDpdhlOrderId($trackAdditional->getShopOrderId());
        $trackExtension->setDpdhlVoucherId($trackAdditional->getVoucherId());
        $trackExtension->setDpdhlTrackId($trackAdditional->getVoucherTrackId());

        $track = $this->trackFactory->create(['data' => [
            ShipmentTrackInterface::TRACK_NUMBER => $trackNumber,
            ShipmentTrackInterface::EXTENSION_ATTRIBUTES_KEY => $trackExtension,
        ]]);

        // do not set the shipment, otherwise cancellation post processors will act on it
        return $this->trackRequestFactory->create(
            [
                'storeId' => $storeId,
                'trackNumber' => $trackNumber,
                'salesTrack' => $track,
            ]
        );
    }
}
