<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\ViewModel\Adminhtml\System;

use DeutschePost\Internetmarke\Model\Config\ModuleConfig;
use DeutschePost\Internetmarke\Model\Config\Source\PageFormats;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class SetupActions implements ArgumentInterface
{
    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var ModuleConfig
     */
    private $config;

    /**
     * @var PageFormats
     */
    private $pageFormatSource;

    public function __construct(UrlInterface $urlBuilder, ModuleConfig $config, PageFormats $pageFormatSource)
    {
        $this->urlBuilder = $urlBuilder;
        $this->config = $config;
        $this->pageFormatSource = $pageFormatSource;
    }

    public function isWalletConfigured()
    {
        return $this->config->getAccountEmail() && $this->config->getAccountPassword();
    }

    public function getPageFormatCount(): int
    {
        return $this->pageFormatSource->getCollection()->getSize();
    }

    public function getProductsCount(): int
    {
        return 0;
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
