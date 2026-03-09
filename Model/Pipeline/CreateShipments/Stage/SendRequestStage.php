<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\Pipeline\CreateShipments\Stage;

use DeutschePost\Internetmarke\Model\Pipeline\CreateShipments\ArtifactsContainer;
use DeutschePost\Internetmarke\Model\Webservice\InternetmarkeServiceFactoryInterface;
use DeutschePost\Sdk\Internetmarke\Exception\DetailedServiceException;
use DeutschePost\Sdk\Internetmarke\Exception\ServiceException;
use Magento\Shipping\Model\Shipment\Request;
use Netresearch\ShippingCore\Api\Data\Pipeline\ArtifactsContainerInterface;
use Netresearch\ShippingCore\Api\Pipeline\CreateShipmentsStageInterface;

class SendRequestStage implements CreateShipmentsStageInterface
{
    /**
     * @var InternetmarkeServiceFactoryInterface
     */
    private $webserviceFactory;

    public function __construct(InternetmarkeServiceFactoryInterface $webserviceFactory)
    {
        $this->webserviceFactory = $webserviceFactory;
    }

    /**
     * Send label request objects to shipment service.
     *
     * Each shipment request is sent as an individual API call so that each
     * shipment receives its own label. Failures are recorded per-request.
     *
     * @param Request[] $requests
     * @param ArtifactsContainerInterface|ArtifactsContainer $artifactsContainer
     * @return Request[]
     */
    #[\Override]
    public function execute(array $requests, ArtifactsContainerInterface $artifactsContainer): array
    {
        if (empty($requests)) {
            return [];
        }

        $orderService = $this->webserviceFactory->createOrderService();

        foreach ($artifactsContainer->getApiRequests() as $requestIndex => $orderRequest) {
            try {
                $order = $orderService->createOrder($orderRequest);
                $artifactsContainer->addApiResponse($requestIndex, $order);
            } catch (DetailedServiceException $exception) {
                $artifactsContainer->addError(
                    $requestIndex,
                    $requests[$requestIndex]->getOrderShipment(),
                    $exception->getMessage()
                );
                unset($requests[$requestIndex]);
            } catch (ServiceException) {
                $artifactsContainer->addError(
                    $requestIndex,
                    $requests[$requestIndex]->getOrderShipment(),
                    'Web service request failed.'
                );
                unset($requests[$requestIndex]);
            }
        }

        return $requests;
    }
}
