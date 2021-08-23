<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Plugin\Sales\Order\Shipment\Track;

use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Sales\Model\Order\Shipment\Track;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Track\Collection;

class LoadTrackExtension
{
    /**
     * @var JoinProcessorInterface
     */
    protected $extensionAttributesJoinProcessor;

    public function __construct(JoinProcessorInterface $extensionAttributesJoinProcessor)
    {
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
    }

    /**
     * Add extension attributes processing but do not alter arguments of original method.
     *
     * @param Collection $trackCollection
     * @return null
     */
    public function beforeLoadWithFilter(Collection $trackCollection)
    {
        $this->extensionAttributesJoinProcessor->process($trackCollection, Track::class);
        return null;
    }
}
