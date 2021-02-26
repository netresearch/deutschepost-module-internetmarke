<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\Pipeline\CreateShipments\ShipmentResponse;

use Dhl\ShippingCore\Model\Pipeline\Shipment\ShipmentResponse\LabelResponse as CoreLabelResponse;

/**
 * The response type consumed by the core carrier to persist label binary and tracking number.
 */
class LabelResponse extends CoreLabelResponse
{
    public const SHOP_ORDER_ID = 'shop_order_id';
    public const VOUCHER_ID = 'voucher_id';
    public const VOUCHER_TRACK_ID = 'voucher_track_id';

    /**
     * Get shop order id from response
     *
     * @return string
     */
    public function getShopOrderId(): string
    {
        return $this->getData(self::SHOP_ORDER_ID);
    }

    /**
     * Get voucher id from response.
     *
     * @return string
     */
    public function getVoucherId(): string
    {
        return $this->getData(self::VOUCHER_ID);
    }

    /**
     * Get the voucher's track id from response.
     *
     * @return string|null
     */
    public function getVoucherTrackId(): ?string
    {
        return $this->getData(self::VOUCHER_TRACK_ID);
    }
}
