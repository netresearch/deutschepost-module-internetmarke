<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\Tracking;

use Dhl\UnifiedTracking\Api\Data\TrackingConfigurationInterface;
use Dhl\UnifiedTracking\Api\TrackingInfoProviderInterface;
use Psr\Log\LoggerInterface;

class TrackingConfiguration implements TrackingConfigurationInterface
{
    public const CARRIER_CODE = 'dpim';

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Returns the carrier code.
     *
     * @return string
     */
    public function getCarrierCode(): string
    {
        return self::CARRIER_CODE;
    }

    /**
     * @return string
     */
    public function getServiceName(): string
    {
        return TrackingInfoProviderInterface::SERVICE_POST_DE;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}
