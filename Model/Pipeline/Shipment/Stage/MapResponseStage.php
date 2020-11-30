<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\Pipeline\Shipment\Stage;

use DeutschePost\Internetmarke\Model\Pipeline\Shipment\ArtifactsContainer;
use Dhl\ShippingCore\Api\Data\Pipeline\ArtifactsContainerInterface;
use Dhl\ShippingCore\Api\Data\Pipeline\ShipmentResponse\LabelResponseInterface;
use Dhl\ShippingCore\Api\Data\Pipeline\ShipmentResponse\LabelResponseInterfaceFactory;
use Dhl\ShippingCore\Api\Data\Pipeline\ShipmentResponse\ShipmentErrorResponseInterface;
use Dhl\ShippingCore\Api\Data\Pipeline\ShipmentResponse\ShipmentErrorResponseInterfaceFactory;
use Dhl\ShippingCore\Api\Pipeline\CreateShipmentsStageInterface;
use Magento\Shipping\Model\Shipment\Request;

class MapResponseStage implements CreateShipmentsStageInterface
{
    /**
     * @var LabelResponseInterfaceFactory
     */
    private $shipmentResponseFactory;

    /**
     * @var ShipmentErrorResponseInterfaceFactory
     */
    private $errorResponseFactory;

    public function __construct(
        LabelResponseInterfaceFactory $shipmentResponseFactory,
        ShipmentErrorResponseInterfaceFactory $errorResponseFactory
    ) {
        $this->shipmentResponseFactory = $shipmentResponseFactory;
        $this->errorResponseFactory = $errorResponseFactory;
    }

    /**
     * Transform collected results into response objects suitable for processing by the core.
     *
     * Note that there will never be more than one package (voucher) per shipment request (cart position):
     * - In manual mode, each package results in a separate web service request.
     * - In bulk mode, all the shipment's items will be packed into one package.
     *
     * @param Request[] $requests
     * @param ArtifactsContainerInterface|ArtifactsContainer $artifactsContainer
     * @return Request[]
     */
    public function execute(array $requests, ArtifactsContainerInterface $artifactsContainer): array
    {
        // handle requests that failed during previous stages
        foreach ($artifactsContainer->getErrors() as $requestIndex => $error) {
            $responseData = [
                ShipmentErrorResponseInterface::REQUEST_INDEX => (string) $requestIndex,
                ShipmentErrorResponseInterface::ERRORS => $error['message'],
                ShipmentErrorResponseInterface::SALES_SHIPMENT => $error['shipment'],
            ];

            $artifactsContainer->addErrorResponse(
                (string) $requestIndex,
                $this->errorResponseFactory->create(['data' => $responseData])
            );
        }

        $apiResponse = $artifactsContainer->getApiResponse();
        if ($apiResponse) {
            $vouchers = $apiResponse->getVouchers();

            // handle requests that passed previous stages successfully
            foreach ($requests as $requestIndex => $shipmentRequest) {
                // vouchers are returned in the same sequence like they are requested
                $voucher = array_shift($vouchers);

                $responseData = [
                    LabelResponseInterface::REQUEST_INDEX => $requestIndex,
                    LabelResponseInterface::SALES_SHIPMENT => $shipmentRequest->getOrderShipment(),
                    LabelResponseInterface::TRACKING_NUMBER => $voucher->getTrackId() ?? $voucher->getVoucherId(),
                    LabelResponseInterface::SHIPPING_LABEL_CONTENT => $voucher->getLabel(),
                ];

                $artifactsContainer->addLabelResponse(
                    (string) $requestIndex,
                    $this->shipmentResponseFactory->create(['data' => $responseData])
                );
            }
        }

        return $requests;
    }
}
