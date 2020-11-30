<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\ViewModel\Adminhtml\System;

use DeutschePost\Internetmarke\Model\Config\ModuleConfig;
use DeutschePost\Internetmarke\Model\ResourceModel\PageFormat\PageFormatCollectionFactory;
use DeutschePost\Internetmarke\Model\ResourceModel\ProductList\SalesProductCollectionFactory;
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
     * @var SalesProductCollectionFactory
     */
    private $productsCollectionFactory;

    public function __construct(
        UrlInterface $urlBuilder,
        DateTime $date,
        ModuleConfig $config,
        PageFormatCollectionFactory $formatsCollectionFactory,
        SalesProductCollectionFactory $productsCollectionFactory
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->date = $date;
        $this->config = $config;
        $this->formatsCollectionFactory = $formatsCollectionFactory;
        $this->productsCollectionFactory = $productsCollectionFactory;
    }

    public function isWalletConfigured()
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
        $currentDate = $this->date->gmtDate();
        $collection = $this->productsCollectionFactory->create();
        return $collection->setDateFilter($currentDate)->getSize();
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
