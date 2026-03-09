<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\Webservice;

use DeutschePost\Internetmarke\Model\Config\ModuleConfig;
use DeutschePost\Sdk\Internetmarke\Api\ApiInfoServiceInterface;
use DeutschePost\Sdk\Internetmarke\Api\CatalogServiceInterface;
use DeutschePost\Sdk\Internetmarke\Api\OrderServiceInterface;
use DeutschePost\Sdk\Internetmarke\Api\RefundServiceInterface;
use DeutschePost\Sdk\Internetmarke\Api\ServiceFactoryInterface;
use DeutschePost\Sdk\Internetmarke\Service\ServiceFactory;
use Psr\Log\LoggerInterface;

class InternetmarkeServiceFactory implements InternetmarkeServiceFactoryInterface
{
    private const string API_CLIENT_ID = 'pJDOxtJt03guK5eXKYcZt9Ez1bPi2Xvm';
    private const string API_CLIENT_SECRET = '3PaF8IKl2HqMlirE';

    private ?ServiceFactoryInterface $sdkFactory = null;

    public function __construct(
        private readonly ModuleConfig $config,
        private readonly LoggerInterface $logger,
    ) {
    }

    private function getSdkFactory(): ServiceFactoryInterface
    {
        if ($this->sdkFactory === null) {
            $this->sdkFactory = new ServiceFactory(
                self::API_CLIENT_ID,
                self::API_CLIENT_SECRET,
                $this->config->getAccountEmail(),
                $this->config->getAccountPassword(),
                $this->logger,
            );
        }

        return $this->sdkFactory;
    }

    #[\Override]
    public function createApiInfoService(): ApiInfoServiceInterface
    {
        return $this->getSdkFactory()->createApiInfoService();
    }

    #[\Override]
    public function createCatalogService(): CatalogServiceInterface
    {
        return $this->getSdkFactory()->createCatalogService();
    }

    #[\Override]
    public function createOrderService(): OrderServiceInterface
    {
        return $this->getSdkFactory()->createOrderService();
    }

    #[\Override]
    public function createRefundService(): RefundServiceInterface
    {
        return $this->getSdkFactory()->createRefundService();
    }
}
