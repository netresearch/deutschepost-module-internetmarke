<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Test\Integration\TestDouble;

use DeutschePost\Sdk\OneClickForApp\Api\Data\OrderInterface;
use DeutschePost\Sdk\OneClickForApp\Api\OrderServiceInterface;

class OrderServiceStub implements OrderServiceInterface
{
    /**
     * @var OrderInterface
     */
    private $order;

    public function __construct(OrderInterface $order)
    {
        $this->order = $order;
    }

    public function createOrder(
        array $items,
        int $orderTotal,
        int $pageFormat,
        bool $createManifest = false,
        bool $createShippingList = false
    ): OrderInterface {
        return $this->order;
    }
}
