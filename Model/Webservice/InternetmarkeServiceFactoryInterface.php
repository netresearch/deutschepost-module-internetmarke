<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\Webservice;

use DeutschePost\Sdk\Internetmarke\Api\ApiInfoServiceInterface;
use DeutschePost\Sdk\Internetmarke\Api\CatalogServiceInterface;
use DeutschePost\Sdk\Internetmarke\Api\OrderServiceInterface;
use DeutschePost\Sdk\Internetmarke\Api\RefundServiceInterface;

interface InternetmarkeServiceFactoryInterface
{
    /**
     * @throws \RuntimeException
     */
    public function createApiInfoService(): ApiInfoServiceInterface;

    /**
     * @throws \RuntimeException
     */
    public function createCatalogService(): CatalogServiceInterface;

    /**
     * @throws \RuntimeException
     */
    public function createOrderService(): OrderServiceInterface;

    /**
     * @throws \RuntimeException
     */
    public function createRefundService(): RefundServiceInterface;
}
