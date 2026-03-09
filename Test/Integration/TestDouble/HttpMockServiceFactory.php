<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Test\Integration\TestDouble;

use DeutschePost\Internetmarke\Model\Webservice\InternetmarkeServiceFactoryInterface;
use DeutschePost\Sdk\Internetmarke\Api\ApiInfoServiceInterface;
use DeutschePost\Sdk\Internetmarke\Api\CatalogServiceInterface;
use DeutschePost\Sdk\Internetmarke\Api\OrderServiceInterface;
use DeutschePost\Sdk\Internetmarke\Api\RefundServiceInterface;
use DeutschePost\Sdk\Internetmarke\Service\ServiceFactory;
use Psr\Log\NullLogger;

/**
 * Service factory that creates real SDK services backed by a QueueHttpClient.
 *
 * Only the HTTP transport is mocked. The full SDK pipeline (serialization,
 * authentication plugin chain, error handling, response deserialization)
 * runs for real.
 */
class HttpMockServiceFactory implements InternetmarkeServiceFactoryInterface
{
    private ServiceFactory $sdkFactory;

    public function __construct(QueueHttpClient $httpClient)
    {
        $this->sdkFactory = new ServiceFactory(
            'test-client-id',
            'test-client-secret',
            'test@example.com',
            'test-password',
            new NullLogger(),
            $httpClient,
        );
    }

    public function createApiInfoService(): ApiInfoServiceInterface
    {
        return $this->sdkFactory->createApiInfoService();
    }

    public function createCatalogService(): CatalogServiceInterface
    {
        return $this->sdkFactory->createCatalogService();
    }

    public function createOrderService(): OrderServiceInterface
    {
        return $this->sdkFactory->createOrderService();
    }

    public function createRefundService(): RefundServiceInterface
    {
        return $this->sdkFactory->createRefundService();
    }
}
