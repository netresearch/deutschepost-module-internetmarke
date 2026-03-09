<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Test\Integration\TestDouble;

use DeutschePost\Sdk\Internetmarke\Api\Data\RefundInterface;
use DeutschePost\Sdk\Internetmarke\Api\RefundServiceInterface;
use DeutschePost\Sdk\Internetmarke\Model\Refund;
use DeutschePost\Sdk\Internetmarke\Model\RefundRequest;

class RefundServiceStub implements RefundServiceInterface
{
    private RefundInterface $refund;

    public function __construct(?RefundInterface $refund = null)
    {
        $this->refund = $refund ?? new Refund();
    }

    public function requestRefund(RefundRequest $request): RefundInterface
    {
        return $this->refund;
    }

    public function getRetoureState(
        ?string $shopRetoureId = null,
        ?int $retoureTransactionId = null,
        ?string $startDate = null,
        ?string $endDate = null,
    ): array {
        return [];
    }
}
