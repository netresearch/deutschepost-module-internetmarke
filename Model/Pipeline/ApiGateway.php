<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\Pipeline;

use DeutschePost\Internetmarke\Model\Pipeline\Shipment\ArtifactsContainer;
use Dhl\ShippingCore\Api\Data\Pipeline\ShipmentResponse\LabelResponseInterface;
use Dhl\ShippingCore\Api\Data\Pipeline\ShipmentResponse\ShipmentErrorResponseInterface;
use Dhl\ShippingCore\Api\Pipeline\CreateShipmentsPipelineInterface;
use Dhl\ShippingCore\Api\Pipeline\ShipmentResponseProcessorInterface;
use Magento\Shipping\Model\Shipment\Request;

class ApiGateway
{
    public const API_IDENTIFIER = 'oneclickforapp';

    /**
     * @var CreateShipmentsPipelineInterface
     */
    private $pipeline;

    /**
     * @var ShipmentResponseProcessorInterface
     */
    private $responseProcessor;

    /**
     * @var int
     */
    private $storeId;

    public function __construct(
        CreateShipmentsPipelineInterface $pipeline,
        ShipmentResponseProcessorInterface $responseProcessor,
        int $storeId
    ) {
        $this->pipeline = $pipeline;
        $this->responseProcessor = $responseProcessor;
        $this->storeId = $storeId;
    }

    /**
     * Convert shipment requests to shipment orders, inform label status management, send to API, return result.
     *
     * The mapped result can be
     * - an array of tracking-label pairs or
     * - an array of errors.
     *
     * @param Request[] $shipmentRequests
     * @return LabelResponseInterface[]|ShipmentErrorResponseInterface[]
     */
    public function createShipments(array $shipmentRequests): array
    {
        /** @var ArtifactsContainer $artifactsContainer */
        $artifactsContainer = $this->pipeline->run($this->storeId, $shipmentRequests);

        $this->responseProcessor->processResponse(
            $artifactsContainer->getLabelResponses(),
            $artifactsContainer->getErrorResponses()
        );

        return array_merge($artifactsContainer->getErrorResponses(), $artifactsContainer->getLabelResponses());
    }
}
