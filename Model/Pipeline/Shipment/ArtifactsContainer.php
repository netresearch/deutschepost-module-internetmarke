<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\Pipeline\Shipment;

use DeutschePost\Sdk\OneClickForApp\Api\Data\OrderInterface;
use Dhl\ShippingCore\Api\Data\Pipeline\ArtifactsContainerInterface;
use Dhl\ShippingCore\Api\Data\Pipeline\ShipmentResponse\LabelResponseInterface;
use Dhl\ShippingCore\Api\Data\Pipeline\ShipmentResponse\ShipmentErrorResponseInterface;
use Magento\Sales\Model\Order\Shipment;

class ArtifactsContainer implements ArtifactsContainerInterface
{
    /**
     * Store id the pipeline runs for.
     *
     * @var int|null
     */
    private $storeId;

    /**
     * Error messages occurred during pipeline execution.
     *
     * @var string[][]|\Magento\Sales\Api\Data\ShipmentInterface[][]
     */
    private $errors = [];

    /**
     * Container for the API (SDK) request data.
     *
     * @var Order
     */
    private $apiRequest;

    /**
     * API (SDK) response objects.
     *
     * @var OrderInterface
     */
    private $apiResponse;

    /**
     * Label response suitable for processing by the core.
     *
     * @var LabelResponseInterface[]
     */
    private $labelResponses = [];

    /**
     * Error response suitable for processing by the core. Contains request id / sequence number.
     *
     * @var ShipmentErrorResponseInterface[]
     */
    private $errorResponses = [];

    /**
     * Set store id for the pipeline.
     *
     * @param int $storeId
     * @return void
     */
    public function setStoreId(int $storeId)
    {
        $this->storeId = $storeId;
    }

    /**
     * Add error message for a shipment request.
     *
     * Text errors may be added during pipeline execution. Finally, they
     * must be converted to error objects before passing them back to Magento.
     *
     * @see addErrorResponse
     *
     * @param string $requestIndex
     * @param Shipment $shipment
     * @param string $errorMessage
     * @return void
     */
    public function addError(string $requestIndex, Shipment $shipment, string $errorMessage)
    {
        $this->errors[$requestIndex] = [
            'shipment' => $shipment,
            'message' => $errorMessage,
        ];
    }

    /**
     * Add a prepared request object, ready for the web service call.
     *
     * @param Order $shipmentOrder
     * @return void
     */
    public function setApiRequest(Order $shipmentOrder)
    {
        $this->apiRequest = $shipmentOrder;
    }

    /**
     * Add the received response object.
     *
     * @param OrderInterface $apiResponse
     * @return void
     */
    public function setApiResponse(OrderInterface $apiResponse)
    {
        $this->apiResponse = $apiResponse;
    }

    /**
     * Add positive label response.
     *
     * @param string $requestIndex
     * @param LabelResponseInterface $labelResponse
     * @return void
     */
    public function addLabelResponse(string $requestIndex, LabelResponseInterface $labelResponse)
    {
        $this->labelResponses[$requestIndex] = $labelResponse;
    }

    /**
     * Add label error.
     *
     * @param string $requestIndex
     * @param ShipmentErrorResponseInterface $errorResponse
     * @return void
     */
    public function addErrorResponse(string $requestIndex, ShipmentErrorResponseInterface $errorResponse)
    {
        $this->errorResponses[$requestIndex] = $errorResponse;
    }

    /**
     * Get store id for the pipeline.
     *
     * @return int
     */
    public function getStoreId(): int
    {
        return (int) $this->storeId;
    }

    /**
     * Obtain the error messages which occurred during pipeline execution.
     *
     * @return \Magento\Sales\Api\Data\ShipmentInterface[][]|string[][]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Obtain the prepared request objects, ready for the web service call.
     *
     * @return Order
     */
    public function getApiRequest(): Order
    {
        return $this->apiRequest;
    }

    /**
     * Obtain the response object as received from the web service.
     *
     * @return OrderInterface|null
     */
    public function getApiResponse(): ?OrderInterface
    {
        return $this->apiResponse;
    }

    /**
     * Obtain the labels retrieved from the web service.
     *
     * @return LabelResponseInterface[]
     */
    public function getLabelResponses(): array
    {
        return $this->labelResponses;
    }

    /**
     * Obtain the label errors occurred during web service call.
     *
     * @return ShipmentErrorResponseInterface[]
     */
    public function getErrorResponses(): array
    {
        return $this->errorResponses;
    }
}
