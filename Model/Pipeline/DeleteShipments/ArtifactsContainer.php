<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\Pipeline\DeleteShipments;

use Netresearch\ShippingCore\Api\Data\Pipeline\ArtifactsContainerInterface;
use Netresearch\ShippingCore\Api\Data\Pipeline\TrackResponse\TrackErrorResponseInterface;
use Netresearch\ShippingCore\Api\Data\Pipeline\TrackResponse\TrackResponseInterface;

class ArtifactsContainer implements ArtifactsContainerInterface
{
    /**
     * Store id the pipeline runs for.
     *
     * @var int|null
     */
    private $storeId;

    /**
     * Label response suitable for processing by the core.
     *
     * @var TrackResponseInterface[]
     */
    private $trackResponses = [];

    /**
     * Error response suitable for processing by the core. Contains request id / tracking number.
     *
     * @var TrackErrorResponseInterface[]
     */
    private $errorResponses = [];

    /**
     * Set store id for the pipeline.
     *
     * @param int $storeId
     * @return void
     */
    public function setStoreId(int $storeId): void
    {
        $this->storeId = $storeId;
    }

    /**
     * Add positive label response.
     *
     * @param string $requestIndex
     * @param TrackResponseInterface $trackResponse
     * @return void
     */
    public function addTrackResponse(string $requestIndex, TrackResponseInterface $trackResponse): void
    {
        $this->trackResponses[$requestIndex] = $trackResponse;
    }

    /**
     * Add cancellation error.
     *
     * @param string $requestIndex
     * @param TrackErrorResponseInterface $errorResponse
     * @return void
     */
    public function addErrorResponse(string $requestIndex, TrackErrorResponseInterface $errorResponse): void
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
     * Obtain the tracks cancelled at the web service.
     *
     * @return TrackResponseInterface[]
     */
    public function getTrackResponses(): array
    {
        return $this->trackResponses;
    }

    /**
     * Obtain the cancellation errors occurred during web service call.
     *
     * @return TrackErrorResponseInterface[]
     */
    public function getErrorResponses(): array
    {
        return $this->errorResponses;
    }
}
