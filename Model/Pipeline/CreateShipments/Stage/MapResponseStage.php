<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\Pipeline\CreateShipments\Stage;

use DeutschePost\Internetmarke\Model\Pipeline\CreateShipments\ArtifactsContainer;
use DeutschePost\Internetmarke\Model\Pipeline\CreateShipments\ShipmentResponse\LabelResponse;
use DeutschePost\Internetmarke\Model\Pipeline\CreateShipments\ShipmentResponse\LabelResponseFactory;
use Magento\Shipping\Model\Shipment\Request;
use Netresearch\ShippingCore\Api\Data\Pipeline\ArtifactsContainerInterface;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentResponse\ShipmentErrorResponseInterface;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentResponse\ShipmentErrorResponseInterfaceFactory;
use Netresearch\ShippingCore\Api\Pipeline\CreateShipmentsStageInterface;

class MapResponseStage implements CreateShipmentsStageInterface
{
    /**
     * @var LabelResponseFactory
     */
    private $shipmentResponseFactory;

    /**
     * @var ShipmentErrorResponseInterfaceFactory
     */
    private $errorResponseFactory;

    public function __construct(
        LabelResponseFactory $shipmentResponseFactory,
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
            $errorMessage =  __('Label could not be created: %1', $error['message']);
            $responseData = [
                ShipmentErrorResponseInterface::REQUEST_INDEX => (string) $requestIndex,
                ShipmentErrorResponseInterface::ERRORS => $errorMessage,
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
                    LabelResponse::REQUEST_INDEX => $requestIndex,
                    LabelResponse::SALES_SHIPMENT => $shipmentRequest->getOrderShipment(),
                    LabelResponse::TRACKING_NUMBER => $voucher->getTrackId() ?? $voucher->getVoucherId(),
                    LabelResponse::SHIPPING_LABEL_CONTENT => $voucher->getLabel(),
                    LabelResponse::SHOP_ORDER_ID => $apiResponse->getId(),
                    LabelResponse::VOUCHER_ID => $voucher->getVoucherId(),
                    LabelResponse::VOUCHER_TRACK_ID => $voucher->getTrackId(),
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
