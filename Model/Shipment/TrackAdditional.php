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

    public function getTrackId(): ?int
    {
        return $this->getData(self::TRACK_ID);
    }

    public function getShopOrderId(): string
    {
        return $this->getData(self::SHOP_ORDER_ID);
    }

    public function getVoucherId(): string
    {
        return $this->getData(self::VOUCHER_ID);
    }

    public function getVoucherTrackId(): ?string
    {
        return $this->getData(self::VOUCHER_TRACK_ID);
    }
}
