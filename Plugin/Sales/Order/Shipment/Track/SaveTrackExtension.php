<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Plugin\Sales\Order\Shipment\Track;

use DeutschePost\Internetmarke\Api\Data\TrackAdditionalInterface;
use DeutschePost\Internetmarke\Api\Data\TrackAdditionalInterfaceFactory;
use DeutschePost\Internetmarke\Model\Shipment\TrackAdditional;
use DeutschePost\Internetmarke\Model\ResourceModel\Shipment\TrackAdditional as TrackAdditionalResource;
use Magento\Sales\Model\Order\Shipment\Track;
use Magento\Sales\Model\ResourceModel\Order\Shipment\Track as TrackResource;
use Psr\Log\LoggerInterface;

class SaveTrackExtension
{
    /**
     * @var TrackAdditionalInterfaceFactory
     */
    private $factory;

    /**
     * @var TrackAdditionalResource
     */
    private $resource;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        TrackAdditionalInterfaceFactory $factory,
        TrackAdditionalResource $resource,
        LoggerInterface $logger
    ) {
        $this->factory = $factory;
        $this->resource = $resource;
        $this->logger = $logger;
    }

    public function afterSave(TrackResource $subject, TrackResource $result, Track $track): TrackResource
    {
        $labelApi = $track->getExtensionAttributes()->getDpdhlLabelApi();
        if ($labelApi) {
            /** @var TrackAdditional $trackAdditional */
            $trackAdditional = $this->factory->create();
            $trackAdditional->setData(TrackAdditionalInterface::TRACK_ID, $track->getEntityId());
            $trackAdditional->setData(TrackAdditionalInterface::LABEL_API, $labelApi);

            try {
                $this->resource->save($trackAdditional);
            } catch (\Exception $exception) {
                $this->logger->error($exception->getMessage(), ['exception' => $exception]);
            }
        }

        return $result;
    }
}
