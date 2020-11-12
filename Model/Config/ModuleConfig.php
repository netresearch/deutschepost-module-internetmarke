<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;

class ModuleConfig
{
    // Defaults
    private const CONFIG_PATH_VERSION = 'dhlshippingsolutions/dpim/version';
    private const CONFIG_PATH_PORTOKASSE_EMAIL = 'dhlshippingsolutions/dpim/account/portokasse_username';
    private const CONFIG_PATH_PORTOKASSE_PASSWORD = 'dhlshippingsolutions/dpim/account/portokasse_password';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
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
}
