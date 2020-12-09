<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\Webservice;

use DeutschePost\Sdk\OneClickForApp\Api\AccountInformationServiceInterface;
use DeutschePost\Sdk\OneClickForApp\Api\OrderServiceInterface;
use DeutschePost\Sdk\OneClickForApp\Exception\ServiceException;

interface OneClickForAppFactoryInterface
{
    /**
     * @return AccountInformationServiceInterface
     * @throws ServiceException
     */
    public function createInfoService(): AccountInformationServiceInterface;

    /**
     * @return OrderServiceInterface
     * @throws ServiceException
     */
    public function createOrderService(): OrderServiceInterface;
}
