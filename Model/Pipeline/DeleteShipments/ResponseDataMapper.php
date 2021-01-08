<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\Pipeline\DeleteShipments;

use Dhl\ShippingCore\Api\Data\Pipeline\TrackResponse\TrackErrorResponseInterface;
use Dhl\ShippingCore\Api\Data\Pipeline\TrackResponse\TrackErrorResponseInterfaceFactory;
use Dhl\ShippingCore\Api\Data\Pipeline\TrackResponse\TrackResponseInterface;
use Dhl\ShippingCore\Api\Data\Pipeline\TrackResponse\TrackResponseInterfaceFactory;
use Magento\Framework\Phrase;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\Data\ShipmentTrackInterface;

/**
 * Convert API response into the carrier response format that the shipping module understands.
 */
class ResponseDataMapper
{
    /**
     * @var TrackResponseInterfaceFactory
     */
    private $trackResponseFactory;

    /**
     * @var TrackErrorResponseInterfaceFactory
     */
    private $errorResponseFactory;

    public function __construct(
        TrackResponseInterfaceFactory $trackResponseFactory,
        TrackErrorResponseInterfaceFactory $errorResponseFactory
    ) {
        $this->trackResponseFactory = $trackResponseFactory;
        $this->errorResponseFactory = $errorResponseFactory;
    }

    /**
     * Map created shipment into response object as required by the shipping module.
     *
     * @param string $trackNumber
     * @param ShipmentInterface|null $salesShipment
     * @param ShipmentTrackInterface|null $salesTrack
     * @return TrackResponseInterface
     */
    public function createTrackResponse(
        string $trackNumber,
        ShipmentInterface $salesShipment = null,
        ShipmentTrackInterface $salesTrack = null
    ): TrackResponseInterface {
        $responseData = [
            TrackResponseInterface::TRACK_NUMBER => $trackNumber,
            TrackResponseInterface::SALES_SHIPMENT => $salesShipment,
            TrackResponseInterface::SALES_TRACK => $salesTrack,
        ];

        return $this->trackResponseFactory->create(['data' => $responseData]);
    }

    /**
     * Map error message into response object as required by the shipping module.
     *
     * @param string $trackNumber
     * @param Phrase $message
     * @param ShipmentInterface|null $salesShipment
     * @param ShipmentTrackInterface|null $salesTrack
     * @return TrackErrorResponseInterface
     */
    public function createErrorResponse(
        string $trackNumber,
        Phrase $message,
        ShipmentInterface $salesShipment = null,
        ShipmentTrackInterface $salesTrack = null
    ): TrackErrorResponseInterface {
        $responseData = [
            TrackErrorResponseInterface::TRACK_NUMBER => $trackNumber,
            TrackErrorResponseInterface::ERRORS => $message,
            TrackErrorResponseInterface::SALES_SHIPMENT => $salesShipment,
            TrackErrorResponseInterface::SALES_TRACK => $salesTrack,
        ];

        return $this->errorResponseFactory->create(['data' => $responseData]);
    }
}