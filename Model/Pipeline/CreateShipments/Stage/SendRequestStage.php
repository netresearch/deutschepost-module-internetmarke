<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\Pipeline\CreateShipments\Stage;

use DeutschePost\Internetmarke\Model\Pipeline\CreateShipments\ArtifactsContainer;
use DeutschePost\Internetmarke\Model\Webservice\OneClickForAppFactoryInterface;
use DeutschePost\Sdk\OneClickForApp\Exception\DetailedServiceException;
use DeutschePost\Sdk\OneClickForApp\Exception\ServiceException;
use Magento\Shipping\Model\Shipment\Request;
use Netresearch\ShippingCore\Api\Data\Pipeline\ArtifactsContainerInterface;
use Netresearch\ShippingCore\Api\Pipeline\CreateShipmentsStageInterface;

class SendRequestStage implements CreateShipmentsStageInterface
{
    /**
     * @var OneClickForAppFactoryInterface
     */
    private $webserviceFactory;

    public function __construct(OneClickForAppFactoryInterface $webserviceFactory)
    {
        $this->webserviceFactory = $webserviceFactory;
    }

    /**
     * Send label request objects to shipment service.
     *
     * @param Request[] $requests
     * @param ArtifactsContainerInterface|ArtifactsContainer $artifactsContainer
     * @return Request[]
     */
    public function execute(array $requests, ArtifactsContainerInterface $artifactsContainer): array
    {
        if (empty($requests)) {
            return [];
        }

        $apiRequest = $artifactsContainer->getApiRequest();

        try {
            $webservice = $this->webserviceFactory->createOrderService();
            $order = $webservice->createOrder(
                $apiRequest->getPositions(),
                $apiRequest->getAmount(),
                $apiRequest->getPageFormatId()
            );
            $artifactsContainer->setApiResponse($order);
        } catch (DetailedServiceException $exception) {
            // mark all requests as failed
            foreach ($requests as $requestIndex => $shipmentRequest) {
                $artifactsContainer->addError(
                    (string) $requestIndex,
                    $shipmentRequest->getOrderShipment(),
                    $exception->getMessage()
                );
            }

            // no requests passed the stage
            return [];
        } catch (ServiceException $exception) {
            // mark all requests as failed
            foreach ($requests as $requestIndex => $shipmentRequest) {
                $artifactsContainer->addError(
                    (string) $requestIndex,
                    $shipmentRequest->getOrderShipment(),
                    'Web service request failed.'
                );
            }

            // no requests passed the stage
            return [];
        }

        return $requests;
    }
}
