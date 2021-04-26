<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\Webservice;

use DeutschePost\Sdk\OneClickForRefund\Api\RefundServiceInterface;

interface OneClickForRefundFactoryInterface
{
    /**
     * @return RefundServiceInterface
     * @throws \RuntimeException
     */
    public function createRefundService(): RefundServiceInterface;
}
