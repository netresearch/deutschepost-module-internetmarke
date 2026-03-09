<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Test\Integration\TestDouble;

use DeutschePost\Internetmarke\Model\Webservice\InternetmarkeServiceFactoryInterface;
use DeutschePost\Sdk\Internetmarke\Api\ApiInfoServiceInterface;
use DeutschePost\Sdk\Internetmarke\Api\CatalogServiceInterface;
use DeutschePost\Sdk\Internetmarke\Api\Data\ContractProductInterface;
use DeutschePost\Sdk\Internetmarke\Api\Data\OrderInterface;
use DeutschePost\Sdk\Internetmarke\Api\Data\PageFormatInterface;
use DeutschePost\Sdk\Internetmarke\Api\OrderServiceInterface;
use DeutschePost\Sdk\Internetmarke\Api\RefundServiceInterface;
use Magento\TestFramework\Helper\Bootstrap;

class InternetmarkeTestFactory implements InternetmarkeServiceFactoryInterface
{
    /**
     * @var PageFormatInterface[]
     */
    private $pageFormats;

    /**
     * @var ContractProductInterface[]
     */
    private $contractProducts;

    /**
     * @var OrderInterface|null
     */
    private $order;

    public function __construct(array $pageFormats = [], array $contractProducts = [], ?OrderInterface $order = null)
    {
        $this->pageFormats = $pageFormats;
        $this->contractProducts = $contractProducts;
        $this->order = $order;
    }

    public function createApiInfoService(): ApiInfoServiceInterface
    {
        return Bootstrap::getObjectManager()->create(ApiInfoServiceStub::class);
    }

    public function createCatalogService(): CatalogServiceInterface
    {
        return Bootstrap::getObjectManager()->create(
            CatalogServiceStub::class,
            [
                'pageFormats' => $this->pageFormats,
                'contractProducts' => $this->contractProducts,
            ]
        );
    }

    public function createOrderService(): OrderServiceInterface
    {
        return Bootstrap::getObjectManager()->create(
            OrderServiceStub::class,
            [
                'order' => $this->order,
            ]
        );
    }

    public function createRefundService(): RefundServiceInterface
    {
        return Bootstrap::getObjectManager()->create(RefundServiceStub::class);
    }
}
