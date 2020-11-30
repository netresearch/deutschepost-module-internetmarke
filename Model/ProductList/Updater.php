<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\ProductList;

use DeutschePost\Internetmarke\Model\ResourceModel\ProductList\SaveHandler;
use DeutschePost\Internetmarke\Model\Webservice\OneClickForAppFactory;
use DeutschePost\Internetmarke\Model\Webservice\ProdWsFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use Psr\Log\LoggerInterface;

class Updater
{
    /**
     * @var ProdWsFactory
     */
    private $productsWsFactory;

    /**
     * @var OneClickForAppFactory
     */
    private $pricesWsFactory;

    /**
     * @var SaveHandler
     */
    private $saveHandler;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        ProdWsFactory $productsWsFactory,
        OneClickForAppFactory $pricesWsFactory,
        SaveHandler $saveHandler,
        LoggerInterface $logger
    ) {
        $this->productsWsFactory = $productsWsFactory;
        $this->pricesWsFactory = $pricesWsFactory;
        $this->saveHandler = $saveHandler;
        $this->logger = $logger;
    }

    /**
     * @throws CouldNotSaveException
     */
    public function updateProductLists()
    {
        try {
            $productsWebservice = $this->productsWsFactory->create($this->logger);
            $productLists = $productsWebservice->getProductLists('NETRESEARCH');

            $pricesWebservice = $this->pricesWsFactory->createInfoService($this->logger);
            $prices = $pricesWebservice->getContractProducts();

            $this->saveHandler->save($productLists, $prices);
        } catch (\Exception $exception) {
            $this->logger->error('Product lists update  failed.', ['exception' => $exception]);
            throw new CouldNotSaveException(__('Failed to update product lists: %1', $exception->getMessage()));
        }
    }
}
