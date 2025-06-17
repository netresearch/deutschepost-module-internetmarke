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
    #[\Override]
    protected function _construct()
    {
        $this->_init(\DeutschePost\Internetmarke\Model\ResourceModel\Shipment\TrackAdditional::class);
        parent::_construct();
    }

    #[\Override]
    public function getId(): int
    {
        return (int) parent::getId();
    }

    #[\Override]
    public function getTrackId(): ?int
    {
        return $this->getData(self::TRACK_ID);
    }

    #[\Override]
    public function getShopOrderId(): string
    {
        return $this->getData(self::SHOP_ORDER_ID);
    }

    #[\Override]
    public function getVoucherId(): string
    {
        return $this->getData(self::VOUCHER_ID);
    }

    #[\Override]
    public function getVoucherTrackId(): ?string
    {
        return $this->getData(self::VOUCHER_TRACK_ID);
    }
}
