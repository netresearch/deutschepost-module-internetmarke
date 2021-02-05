<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Api\Data;

interface TrackAdditionalInterface
{
    public const TRACK_ID = 'track_id';
    public const SHOP_ORDER_ID = 'shop_order_id';
    public const VOUCHER_ID = 'voucher_id';
    public const VOUCHER_TRACK_ID = 'voucher_track_id';

    public function getId(): int;
    public function getTrackId(): ?int;
    public function getShopOrderId(): string;
    public function getVoucherId(): string;
    public function getVoucherTrackId(): ?string;
}
