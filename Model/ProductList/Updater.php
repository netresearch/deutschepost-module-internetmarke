<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\ProductList;

use DeutschePost\Internetmarke\Model\ResourceModel\ProductList\SaveHandler;
use DeutschePost\Internetmarke\Model\Webservice\OneClickForAppFactoryInterface;
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
     * @var OneClickForAppFactoryInterface
     */
    private $pricesWsFactory;

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
        OneClickForAppFactoryInterface $pricesWsFactory,
        ProductFilter $productFilter,
        SaveHandler $saveHandler,
        LoggerInterface $logger
    ) {
        $this->productsWsFactory = $productsWsFactory;
        $this->pricesWsFactory = $pricesWsFactory;
        $this->productFilter = $productFilter;
        $this->saveHandler = $saveHandler;
        $this->logger = $logger;
    }

    /**
     * @throws CouldNotSaveException
     */
    public function updateProductLists()
    {
        try {
            $productsWebservice = $this->productsWsFactory->create();
            $productLists = $this->productFilter->filter($productsWebservice->getProductLists('NETRESEARCH'));

            $pricesWebservice = $this->pricesWsFactory->createInfoService();
            $prices = $pricesWebservice->getContractProducts();

            $this->saveHandler->save($productLists, $prices);
        } catch (\Exception $exception) {
            $this->logger->error('Product lists update  failed.', ['exception' => $exception]);
            throw new CouldNotSaveException(__('Failed to update product lists: %1', $exception->getMessage()));
        }
    }
}
