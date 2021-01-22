<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Api\Data;

interface PageFormatInterface
{
    public const FORMAT_ID = 'format_id';
    public const NAME = 'name';
    public const DESCRIPTION = 'description';
    public const PRINT_MEDIUM = 'print_medium';
    public const VOUCHER_COLUMNS = 'voucher_columns';
    public const VOUCHER_ROWS = 'voucher_rows';
    public const IS_ADDRESS_POSSIBLE = 'is_address_possible';
    public const IS_IMAGE_POSSIBLE = 'is_image_possible';

    public function getId(): int;
    public function getName(): string;
    public function getDescription(): string;
    public function getPrintMedium(): string;
    public function getVoucherColumns(): int;
    public function getVoucherRows(): int;
    public function isAddressPossible(): bool;
    public function isImagePossible(): bool;
}
