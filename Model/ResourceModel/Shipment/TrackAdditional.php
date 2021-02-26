<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\ResourceModel\Shipment;

use DeutschePost\Internetmarke\Api\Data\TrackAdditionalInterface;
use Magento\Framework\Model\ResourceModel\Db\VersionControl\AbstractDb;

class TrackAdditional extends AbstractDb
{
    /**
     * Entities with no auto-increment ID must toggle "$_useIsObjectNew" property
     * to distinguish between create and update operations.
     * @see \Magento\Framework\Model\ResourceModel\Db\AbstractDb::isObjectNotNew
     *
     * @var bool
     */
    protected $_useIsObjectNew = true;

    /**
     * Entities with no auto-increment ID must toggle "$_isPkAutoIncrement" property
     * to preserve the ID field.
     * @see \Magento\Framework\Model\ResourceModel\Db\AbstractDb::saveNewObject
     *
     * @var bool
     */
    protected $_isPkAutoIncrement = false;

    /**
     * Init main table and primary key.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('deutschepost_shipment_track', TrackAdditionalInterface::TRACK_ID);
    }
}
