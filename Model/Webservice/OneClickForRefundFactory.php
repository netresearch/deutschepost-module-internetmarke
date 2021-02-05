<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\Webservice;

use DeutschePost\Internetmarke\Model\Config\ModuleConfig;
use DeutschePost\Sdk\OneClickForRefund\Api\Data\CredentialsInterfaceFactory;
use DeutschePost\Sdk\OneClickForRefund\Api\RefundServiceInterface;
use DeutschePost\Sdk\OneClickForRefund\Api\ServiceFactoryInterface;
use DeutschePost\Sdk\OneClickForRefund\Api\TokenStorageInterfaceFactory;
use Psr\Log\LoggerInterface;

class OneClickForRefundFactory implements OneClickForRefundFactoryInterface
{
    /**
     * @var ServiceFactoryInterface
     */
    private $serviceFactory;

    /**
     * @var TokenStorageInterfaceFactory
     */
    private $tokenStorageFactory;

    /**
     * @var CredentialsInterfaceFactory
     */
    private $credentialsFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ModuleConfig
     */
    private $config;

    public function __construct(
        ServiceFactoryInterface $oneClickForRefundFactory,
        TokenStorageInterfaceFactory $tokenStorageFactory,
        CredentialsInterfaceFactory $credentialsFactory,
        LoggerInterface $logger,
        ModuleConfig $config
    ) {
        $this->serviceFactory = $oneClickForRefundFactory;
        $this->tokenStorageFactory = $tokenStorageFactory;
        $this->credentialsFactory = $credentialsFactory;
        $this->logger = $logger;
        $this->config = $config;
    }

    public function createRefundService(): RefundServiceInterface
    {
        $credentials = $this->credentialsFactory->create([
            'username' => $this->config->getAccountEmail(),
            'password' => $this->config->getAccountPassword(),
            'partnerId' => 'ANGMA',
            'partnerKey' => 'F6Wy5cF8pcM8wCusfqLmmWvdsoQFdkxM',
            'keyPhase' => 1,
            'tokenStorage' => $this->tokenStorageFactory->create(),
        ]);

        return $this->serviceFactory->createRefundService($credentials, $this->logger);
    }
}
