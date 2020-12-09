<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\Shipment;

use DeutschePost\Internetmarke\Api\Data\TrackAdditionalInterface;
use Magento\Framework\Model\AbstractModel;

class TrackAdditional extends AbstractModel implements TrackAdditionalInterface
{
    /**
     * Initialize resource model.
     */
    protected function _construct()
    {
        $this->_init(\DeutschePost\Internetmarke\Model\ResourceModel\Shipment\TrackAdditional::class);
        parent::_construct();
    }

    public function getId(): int
    {
        return (int) parent::getId();
    }

    public function getLabelApi(): string
    {
        return $this->getData(self::LABEL_API);
    }
}
