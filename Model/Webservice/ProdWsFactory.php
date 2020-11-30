<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\Webservice;

use DeutschePost\Sdk\ProdWS\Api\ProductInformationServiceInterface;
use DeutschePost\Sdk\ProdWS\Api\ServiceFactoryInterface;
use DeutschePost\Sdk\ProdWS\Exception\ServiceException;
use Psr\Log\LoggerInterface;

class ProdWsFactory
{
    /**
     * @var ServiceFactoryInterface
     */
    private $serviceFactory;

    public function __construct(ServiceFactoryInterface $serviceFactory)
    {
        $this->serviceFactory = $serviceFactory;
    }

    /**
     * @param LoggerInterface $logger
     * @return ProductInformationServiceInterface
     * @throws ServiceException
     */
    public function create(LoggerInterface $logger): ProductInformationServiceInterface
    {
        return $this->serviceFactory->createProductInformationService('netresearch', 'A&5%bk?dx7', $logger);
    }
}
