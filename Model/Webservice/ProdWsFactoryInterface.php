<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\Webservice;

use DeutschePost\Sdk\ProdWS\Api\ProductInformationServiceInterface;
use DeutschePost\Sdk\ProdWS\Exception\ServiceException;

interface ProdWsFactoryInterface
{
    /**
     * @return ProductInformationServiceInterface
     * @throws ServiceException
     */
    public function create(): ProductInformationServiceInterface;
}
