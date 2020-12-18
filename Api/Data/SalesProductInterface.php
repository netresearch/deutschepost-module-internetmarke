<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Api\Data;

interface SalesProductInterface
{
    public const PRODUCT_ID = 'product_id';
    public const PPL_ID = 'ppl_id';
    public const NAME = 'name';
    public const PRICE = 'price';

    /**
     * Obtain ID in the ProdWS system (an aggregation of multiple source systems).
     *
     * @return int
     */
    public function getId(): int;

    /**
     * Obtain ID in the PPL source system, used for booking vouchers.
     *
     * @return int
     */
    public function getPPLId(): int;

    /**
     * Obtain product name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Obtain product price in euro cents.
     *
     * @return int
     */
    public function getPrice(): int;
}
