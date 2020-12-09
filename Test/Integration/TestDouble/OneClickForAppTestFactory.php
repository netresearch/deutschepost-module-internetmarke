<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Test\Integration\TestDouble;

use DeutschePost\Internetmarke\Model\Webservice\OneClickForAppFactoryInterface;
use DeutschePost\Sdk\OneClickForApp\Api\AccountInformationServiceInterface;
use DeutschePost\Sdk\OneClickForApp\Api\Data\ContractProductInterface;
use DeutschePost\Sdk\OneClickForApp\Api\Data\OrderInterface;
use DeutschePost\Sdk\OneClickForApp\Api\Data\PageFormatInterface;
use DeutschePost\Sdk\OneClickForApp\Api\OrderServiceInterface;
use Magento\TestFramework\Helper\Bootstrap;

class OneClickForAppTestFactory implements OneClickForAppFactoryInterface
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

    public function __construct(array $pageFormats = [], array $contractProducts = [], OrderInterface $order = null)
    {
        $this->pageFormats = $pageFormats;
        $this->contractProducts = $contractProducts;
        $this->order = $order;
    }

    public function createInfoService(): AccountInformationServiceInterface
    {
        return Bootstrap::getObjectManager()->create(
            InfoServiceStub::class,
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
}
