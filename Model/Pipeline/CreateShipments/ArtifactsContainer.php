<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\Pipeline\CreateShipments;

use DeutschePost\Sdk\Internetmarke\Api\Data\OrderInterface;
use DeutschePost\Sdk\Internetmarke\Model\OrderRequest;
use Magento\Sales\Model\Order\Shipment;
use Netresearch\ShippingCore\Api\Data\Pipeline\ArtifactsContainerInterface;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentResponse\LabelResponseInterface;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentResponse\ShipmentErrorResponseInterface;

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
     * Container for the API (SDK) request data, indexed by request index.
     *
     * @var OrderRequest[]
     */
    private $apiRequests = [];

    /**
     * API (SDK) response objects, indexed by request index.
     *
     * @var OrderInterface[]
     */
    private $apiResponses = [];

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
    #[\Override]
    public function setStoreId(int $storeId): void
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
     * @param int $requestIndex
     * @param Shipment $shipment
     * @param string $errorMessage
     * @return void
     */
    public function addError(int $requestIndex, Shipment $shipment, string $errorMessage): void
    {
        $this->errors[$requestIndex] = [
            'shipment' => $shipment,
            'message' => $errorMessage,
        ];
    }

    /**
     * Add a prepared request object for a specific shipment, ready for the web service call.
     *
     * @param int $requestIndex
     * @param OrderRequest $orderRequest
     * @return void
     */
    public function addApiRequest(int $requestIndex, OrderRequest $orderRequest): void
    {
        $this->apiRequests[$requestIndex] = $orderRequest;
    }

    /**
     * Add the received response object for a specific shipment.
     *
     * @param int $requestIndex
     * @param OrderInterface $apiResponse
     * @return void
     */
    public function addApiResponse(int $requestIndex, OrderInterface $apiResponse): void
    {
        $this->apiResponses[$requestIndex] = $apiResponse;
    }

    /**
     * Add positive label response.
     *
     * @param int $requestIndex
     * @param LabelResponseInterface $labelResponse
     * @return void
     */
    public function addLabelResponse(int $requestIndex, LabelResponseInterface $labelResponse): void
    {
        $this->labelResponses[$requestIndex] = $labelResponse;
    }

    /**
     * Add label error.
     *
     * @param int $requestIndex
     * @param ShipmentErrorResponseInterface $errorResponse
     * @return void
     */
    public function addErrorResponse(int $requestIndex, ShipmentErrorResponseInterface $errorResponse): void
    {
        $this->errorResponses[$requestIndex] = $errorResponse;
    }

    /**
     * Get store id for the pipeline.
     *
     * @return int
     */
    #[\Override]
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
     * Obtain the prepared request objects, indexed by request index.
     *
     * @return OrderRequest[]
     */
    public function getApiRequests(): array
    {
        return $this->apiRequests;
    }

    /**
     * Obtain the response objects as received from the web service, indexed by request index.
     *
     * @return OrderInterface[]
     */
    public function getApiResponses(): array
    {
        return $this->apiResponses;
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
