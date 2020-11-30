<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\Webservice;

use DeutschePost\Internetmarke\Model\Config\ModuleConfig;
use DeutschePost\Sdk\OneClickForApp\Api\AccountInformationServiceInterface;
use DeutschePost\Sdk\OneClickForApp\Api\Data\CredentialsInterfaceFactory;
use DeutschePost\Sdk\OneClickForApp\Api\OrderServiceInterface;
use DeutschePost\Sdk\OneClickForApp\Api\ServiceFactoryInterface;
use DeutschePost\Sdk\OneClickForApp\Api\TokenStorageInterfaceFactory;
use DeutschePost\Sdk\OneClickForApp\Exception\ServiceException;
use Psr\Log\LoggerInterface;

class OneClickForAppFactory
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
        ServiceFactoryInterface $oneClickForAppFactory,
        TokenStorageInterfaceFactory $tokenStorageFactory,
        CredentialsInterfaceFactory $credentialsFactory,
        LoggerInterface $logger,
        ModuleConfig $config
    ) {
        $this->serviceFactory = $oneClickForAppFactory;
        $this->tokenStorageFactory = $tokenStorageFactory;
        $this->credentialsFactory = $credentialsFactory;
        $this->logger = $logger;
        $this->config = $config;
    }

    /**
     * @return AccountInformationServiceInterface
     * @throws ServiceException
     */
    public function createInfoService(): AccountInformationServiceInterface
    {
        $credentials = $this->credentialsFactory->create([
            'username' => $this->config->getAccountEmail(),
            'password' => $this->config->getAccountPassword(),
            'partnerId' => 'ANGMA',
            'partnerKey' => 'F6Wy5cF8pcM8wCusfqLmmWvdsoQFdkxM',
            'keyPhase' => 1,
            'tokenStorage' => $this->tokenStorageFactory->create(),
        ]);

        return $this->serviceFactory->createAccountInformationService($credentials, $this->logger);
    }

    /**
     * @return OrderServiceInterface
     * @throws ServiceException
     */
    public function createOrderService(): OrderServiceInterface
    {
        $credentials = $this->credentialsFactory->create([
            'username' => $this->config->getAccountEmail(),
            'password' => $this->config->getAccountPassword(),
            'partnerId' => 'ANGMA',
            'partnerKey' => 'F6Wy5cF8pcM8wCusfqLmmWvdsoQFdkxM',
            'keyPhase' => 1,
            'tokenStorage' => $this->tokenStorageFactory->create(),
        ]);

        return $this->serviceFactory->createOrderService($credentials, $this->logger);
    }
}
