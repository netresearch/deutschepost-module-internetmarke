<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\Pipeline\Shipment\Stage;

use DeutschePost\Internetmarke\Model\Pipeline\Shipment\ArtifactsContainer;
use DeutschePost\Internetmarke\Model\Webservice\OneClickForAppFactoryInterface;
use DeutschePost\Sdk\OneClickForApp\Exception\ServiceException;
use Dhl\ShippingCore\Api\Data\Pipeline\ArtifactsContainerInterface;
use Dhl\ShippingCore\Api\Pipeline\CreateShipmentsStageInterface;
use Magento\Shipping\Model\Shipment\Request;

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
