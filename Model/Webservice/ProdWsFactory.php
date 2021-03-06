<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\Webservice;

use DeutschePost\Sdk\ProdWS\Api\ProductInformationServiceInterface;
use DeutschePost\Sdk\ProdWS\Api\ServiceFactoryInterface;
use Psr\Log\LoggerInterface;

class ProdWsFactory implements ProdWsFactoryInterface
{
    /**
     * @var ServiceFactoryInterface
     */
    private $serviceFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(ServiceFactoryInterface $serviceFactory, LoggerInterface $logger)
    {
        $this->serviceFactory = $serviceFactory;
        $this->logger = $logger;
    }

    public function create(): ProductInformationServiceInterface
    {
        return $this->serviceFactory->createProductInformationService(
            'netresearch',
            'A&5%bk?dx7',
            $this->logger
        );
    }
}
