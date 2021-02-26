<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\Webservice;

use DeutschePost\Sdk\ProdWS\Api\ProductInformationServiceInterface;

interface ProdWsFactoryInterface
{
    /**
     * @return ProductInformationServiceInterface
     * @throws \RuntimeException
     */
    public function create(): ProductInformationServiceInterface;
}
