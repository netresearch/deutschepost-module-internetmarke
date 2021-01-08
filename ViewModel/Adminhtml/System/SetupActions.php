<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\ViewModel\Adminhtml\System;

use DeutschePost\Internetmarke\Model\Config\ModuleConfig;
use DeutschePost\Internetmarke\Model\ProductList\SalesProductCollectionLoader;
use DeutschePost\Internetmarke\Model\ResourceModel\PageFormat\PageFormatCollectionFactory;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class SetupActions implements ArgumentInterface
{
    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var DateTime
     */
    private $date;

    /**
     * @var ModuleConfig
     */
    private $config;

    /**
     * @var PageFormatCollectionFactory
     */
    private $formatsCollectionFactory;

    /**
     * @var SalesProductCollectionLoader
     */
    private $productCollectionLoader;

    public function __construct(
        UrlInterface $urlBuilder,
        DateTime $date,
        ModuleConfig $config,
        PageFormatCollectionFactory $formatsCollectionFactory,
        SalesProductCollectionLoader $productCollectionLoader
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->date = $date;
        $this->config = $config;
        $this->formatsCollectionFactory = $formatsCollectionFactory;
        $this->productCollectionLoader = $productCollectionLoader;
    }

    public function isWalletConfigured(): bool
    {
        return $this->config->getAccountEmail() && $this->config->getAccountPassword();
    }

    public function getPageFormatCount(): int
    {
        $collection = $this->formatsCollectionFactory->create();
        return $collection->getSize();
    }

    public function getProductsCount(): int
    {
        return $this->productCollectionLoader->getCollection()->getSize();
    }

    public function getFormatsUpdateUrl(): string
    {
        return $this->urlBuilder->getUrl('dpim/config_update/formats');
    }

    public function getProductsUpdateUrl(): string
    {
        return $this->urlBuilder->getUrl('dpim/config_update/products');
    }
}
