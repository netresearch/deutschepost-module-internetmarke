<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\Pipeline\CreateShipments;

class Order
{
    /**
     * @var int
     */
    private $amount;

    /**
     * @var int
     */
    private $pageFormatId;

    /**
     * @var object[]
     */
    private $positions;

    public function __construct(int $amount, int $pageFormatId, array $positions)
    {
        $this->amount = $amount;
        $this->pageFormatId = $pageFormatId;
        $this->positions = $positions;
    }

    /**
     * @return int
     */
    public function getAmount(): int
    {
        return $this->amount;
    }

    /**
     * @return int
     */
    public function getPageFormatId(): int
    {
        return $this->pageFormatId;
    }

    /**
     * @return object[]
     */
    public function getPositions(): array
    {
        return $this->positions;
    }
}
