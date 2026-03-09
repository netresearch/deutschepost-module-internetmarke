<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Test\Integration\TestDouble;

use DeutschePost\Sdk\Internetmarke\Api\Data\OrderInterface;
use DeutschePost\Sdk\Internetmarke\Api\OrderServiceInterface;
use DeutschePost\Sdk\Internetmarke\Model\OrderRequest;
use DeutschePost\Sdk\Internetmarke\Model\PdfPreviewRequest;
use DeutschePost\Sdk\Internetmarke\Model\PngOrderRequest;
use DeutschePost\Sdk\Internetmarke\Model\PngPreviewRequest;

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

    public function initializeCart(): string
    {
        return 'test-cart-id';
    }

    public function createOrder(OrderRequest $request): OrderInterface
    {
        return $this->order;
    }

    public function createPngOrder(PngOrderRequest $request): OrderInterface
    {
        return $this->order;
    }

    public function previewPdfOrder(PdfPreviewRequest $request): OrderInterface
    {
        return $this->order;
    }

    public function previewPngOrder(PngPreviewRequest $request): OrderInterface
    {
        return $this->order;
    }

    public function getOrder(string $shopOrderId): OrderInterface
    {
        return $this->order;
    }
}
