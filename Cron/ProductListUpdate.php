<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Cron;

use DeutschePost\Internetmarke\Model\Config\ModuleConfig;
use DeutschePost\Internetmarke\Model\ProductList\Updater;
use Magento\Framework\Exception\CouldNotSaveException;
use Psr\Log\LoggerInterface;

/**
 * Cron entry point for automatic product update.
 */
class ProductListUpdate
{
    /**
     * @var ModuleConfig
     */
    private $config;

    /**
     * @var Updater
     */
    private $updater;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(ModuleConfig $config, Updater $updater, LoggerInterface $logger)
    {
        $this->config = $config;
        $this->updater = $updater;
        $this->logger = $logger;
    }

    /**
     * Refresh product lists by schedule.
     *
     * Log error, let exception bubble up for the cron module to set schedule status and message.
     * @see \Magento\Cron\Observer\ProcessCronQueueObserver::tryRunJob
     *
     * @throws CouldNotSaveException
     */
    public function execute()
    {
        if (!$this->config->getAccountEmail() || !$this->config->getAccountPassword()) {
            return;
        }

        try {
            $this->updater->updateProductLists();
        } catch (CouldNotSaveException $exception) {
            $this->logger->error('Scheduled product lists update failed.', ['exception' => $exception]);
            throw $exception;
        }
    }
}
