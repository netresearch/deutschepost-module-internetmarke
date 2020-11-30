<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\Config;

use DeutschePost\Internetmarke\Api\Data\PageFormatInterface;
use DeutschePost\Internetmarke\Api\Data\PageFormatInterfaceFactory;
use DeutschePost\Internetmarke\Model\ResourceModel\PageFormat\PageFormat;
use Magento\Framework\App\Config\ScopeConfigInterface;

class ModuleConfig
{
    // Defaults
    private const CONFIG_PATH_VERSION = 'dhlshippingsolutions/dpim/version';

    // todo(nr): add settings to system.xml
    public const CONFIG_PATH_ENABLE_LOGGING = 'dhlshippingsolutions/dpim/logging';
    public const CONFIG_PATH_LOGLEVEL = 'dhlshippingsolutions/dpim/loglevel';

    // 100_account.xml
    private const CONFIG_PATH_PORTOKASSE_EMAIL = 'dhlshippingsolutions/dpim/account/portokasse_username';
    private const CONFIG_PATH_PORTOKASSE_PASSWORD = 'dhlshippingsolutions/dpim/account/portokasse_password';

    // 200_setup.xml
    private const CONFIG_PATH_PAGE_FORMAT = 'dhlshippingsolutions/dpim/setup/page_format';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var PageFormatInterfaceFactory
     */
    private $pageFormatFactory;

    /**
     * @var PageFormat
     */
    private $pageFormatResource;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        PageFormatInterfaceFactory $pageFormatFactory,
        PageFormat $pageFormatResource
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->pageFormatFactory = $pageFormatFactory;
        $this->pageFormatResource = $pageFormatResource;
    }

    public function getModuleVersion(): string
    {
        return $this->scopeConfig->getValue(self::CONFIG_PATH_VERSION);
    }

    public function getAccountEmail(): string
    {
        return $this->scopeConfig->getValue(self::CONFIG_PATH_PORTOKASSE_EMAIL);
    }

    public function getAccountPassword(): string
    {
        return $this->scopeConfig->getValue(self::CONFIG_PATH_PORTOKASSE_PASSWORD);
    }

    public function getPageFormat(): PageFormatInterface
    {
        $pageFormatId = $this->scopeConfig->getValue(self::CONFIG_PATH_PAGE_FORMAT);

        /** @var \DeutschePost\Internetmarke\Model\PageFormat\PageFormat $pageFormat */
        $pageFormat = $this->pageFormatFactory->create();
        $this->pageFormatResource->load($pageFormat, $pageFormatId);

        return $pageFormat;
    }
}
