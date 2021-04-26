<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\Webservice;

use DeutschePost\Sdk\OneClickForApp\Api\AccountInformationServiceInterface;
use DeutschePost\Sdk\OneClickForApp\Api\OrderServiceInterface;

interface OneClickForAppFactoryInterface
{
    /**
     * @return AccountInformationServiceInterface
     * @throws \RuntimeException
     */
    public function createInfoService(): AccountInformationServiceInterface;

    /**
     * @return OrderServiceInterface
     * @throws \RuntimeException
     */
    public function createOrderService(): OrderServiceInterface;
}
