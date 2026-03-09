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
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentResponse\LabelResponseInterface;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentResponse\ShipmentErrorResponseInterface;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentResponse\ShipmentErrorResponseInterfaceFactory;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentResponse\ShipmentResponseInterface;
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
     * Each shipment request has its own API response (one order per shipment).
     * The combined label PDF is retrieved from the order, not from individual vouchers.
     *
     * Note that there will never be more than one package (voucher) per shipment request (cart position):
     * - In manual mode, each package results in a separate web service request.
     * - In bulk mode, all the shipment's items will be packed into one package.
     *
     * @param Request[] $requests
     * @param ArtifactsContainerInterface|ArtifactsContainer $artifactsContainer
     * @return Request[]
     */
    #[\Override]
    public function execute(array $requests, ArtifactsContainerInterface $artifactsContainer): array
    {
        // handle requests that failed during previous stages
        foreach ($artifactsContainer->getErrors() as $requestIndex => $error) {
            $errorMessage = __('Label could not be created: %1', $error['message']);
            $responseData = [
                ShipmentResponseInterface::REQUEST_INDEX => (string) $requestIndex,
                ShipmentResponseInterface::SALES_SHIPMENT => $error['shipment'],
                ShipmentErrorResponseInterface::ERRORS => [$errorMessage],
            ];

            $artifactsContainer->addErrorResponse(
                $requestIndex,
                $this->errorResponseFactory->create(['data' => $responseData])
            );
        }

        $apiResponses = $artifactsContainer->getApiResponses();

        // handle requests that passed previous stages successfully
        foreach ($requests as $requestIndex => $shipmentRequest) {
            $apiResponse = $apiResponses[$requestIndex] ?? null;
            if ($apiResponse === null) {
                continue;
            }

            $vouchers = $apiResponse->getVouchers();
            $voucher = array_shift($vouchers);

            if ($voucher === null) {
                $errorMessage = __('Label could not be created: API response contained no voucher');
                $responseData = [
                    ShipmentResponseInterface::REQUEST_INDEX => (string) $requestIndex,
                    ShipmentResponseInterface::SALES_SHIPMENT => $shipmentRequest->getOrderShipment(),
                    ShipmentErrorResponseInterface::ERRORS => [$errorMessage],
                ];
                $artifactsContainer->addErrorResponse(
                    $requestIndex,
                    $this->errorResponseFactory->create(['data' => $responseData])
                );
                continue;
            }

            $responseData = [
                ShipmentResponseInterface::REQUEST_INDEX => (string) $requestIndex,
                ShipmentResponseInterface::SALES_SHIPMENT => $shipmentRequest->getOrderShipment(),
                LabelResponseInterface::TRACKING_NUMBER => $voucher->getTrackId() ?? $voucher->getVoucherId(),
                LabelResponseInterface::SHIPPING_LABEL_CONTENT => $apiResponse->getLabel(),
                LabelResponseInterface::DOCUMENTS => [],
                LabelResponse::SHOP_ORDER_ID => $apiResponse->getShopOrderId(),
                LabelResponse::VOUCHER_ID => $voucher->getVoucherId(),
                LabelResponse::VOUCHER_TRACK_ID => $voucher->getTrackId(),
            ];

            $artifactsContainer->addLabelResponse(
                $requestIndex,
                $this->shipmentResponseFactory->create(['data' => $responseData])
            );
        }

        return $requests;
    }
}
