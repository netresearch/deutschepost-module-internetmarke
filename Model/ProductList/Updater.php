<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\ProductList;

use DeutschePost\Internetmarke\Model\ResourceModel\ProductList\SaveHandler;
use DeutschePost\Internetmarke\Model\Webservice\InternetmarkeServiceFactoryInterface;
use DeutschePost\Internetmarke\Model\Webservice\ProdWsFactoryInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Psr\Log\LoggerInterface;

class Updater
{
    /**
     * @var ProdWsFactoryInterface
     */
    private $productsWsFactory;

    /**
     * @var InternetmarkeServiceFactoryInterface
     */
    private $internetmarkeFactory;

    /**
     * @var ProductFilter
     */
    private $productFilter;

    /**
     * @var SaveHandler
     */
    private $saveHandler;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        ProdWsFactoryInterface $productsWsFactory,
        InternetmarkeServiceFactoryInterface $internetmarkeFactory,
        ProductFilter $productFilter,
        SaveHandler $saveHandler,
        LoggerInterface $logger
    ) {
        $this->productsWsFactory = $productsWsFactory;
        $this->internetmarkeFactory = $internetmarkeFactory;
        $this->productFilter = $productFilter;
        $this->saveHandler = $saveHandler;
        $this->logger = $logger;
    }

    /**
     * @throws CouldNotSaveException
     */
    public function updateProductLists(): void
    {
        try {
            $productsWebservice = $this->productsWsFactory->create();
            $productLists = $this->productFilter->filter($productsWebservice->getProductLists('NETRESEARCH'));

            $catalogService = $this->internetmarkeFactory->createCatalogService();
            $prices = $catalogService->getContractProducts();

            $this->saveHandler->save($productLists, $prices);
        } catch (\Exception $exception) {
            $this->logger->error('Product lists update  failed.', ['exception' => $exception]);
            throw new CouldNotSaveException(__('Failed to update product lists: %1', $exception->getMessage()));
        }
    }
}
