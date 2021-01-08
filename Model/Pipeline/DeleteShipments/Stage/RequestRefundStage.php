<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\Pipeline\DeleteShipments\Stage;

use DeutschePost\Internetmarke\Model\Pipeline\DeleteShipments\ArtifactsContainer;
use DeutschePost\Internetmarke\Model\Pipeline\DeleteShipments\ResponseDataMapper;
use DeutschePost\Internetmarke\Model\Webservice\OneClickForRefundFactoryInterface;
use DeutschePost\Sdk\OneClickForRefund\Exception\ServiceException;
use Dhl\ShippingCore\Api\Data\Pipeline\ArtifactsContainerInterface;
use Dhl\ShippingCore\Api\Data\Pipeline\TrackRequest\TrackRequestInterface;
use Dhl\ShippingCore\Api\Pipeline\RequestTracksStageInterface;

class RequestRefundStage implements RequestTracksStageInterface
{
    /**
     * @var OneClickForRefundFactoryInterface
     */
    private $webserviceFactory;

    /**
     * @var ResponseDataMapper
     */
    private $responseDataMapper;

    public function __construct(
        OneClickForRefundFactoryInterface $webserviceFactory,
        ResponseDataMapper $responseDataMapper
    ) {
        $this->webserviceFactory = $webserviceFactory;
        $this->responseDataMapper = $responseDataMapper;
    }

    /**
     * Send request data to webservice.
     *
     * Add succeeded and failed requests to artifacts container, pass on succeed requests.
     *
     * @param TrackRequestInterface[] $requests
     * @param ArtifactsContainerInterface|ArtifactsContainer $artifactsContainer
     * @return TrackRequestInterface[]
     */
    public function execute(array $requests, ArtifactsContainerInterface $artifactsContainer): array
    {
        if (empty($requests)) {
            return [];
        }

        $shipmentService = $this->webserviceFactory->createRefundService();

        return array_filter(
            $requests,
            function (TrackRequestInterface $request) use ($artifactsContainer, $shipmentService) {
                $track = $request->getSalesTrack();
                if (!$track) {
                    return false;
                }

                $shopOrderId = $track->getExtensionAttributes()->getDpdhlOrderId();
                $voucherId = $track->getExtensionAttributes()->getDpdhlVoucherId();

                try {
                    $shipmentService->cancelVouchers($shopOrderId, [$voucherId]);
                    $response = $this->responseDataMapper->createTrackResponse(
                        $track->getTrackNumber(),
                        $request->getSalesShipment(),
                        $track
                    );
                    $artifactsContainer->addTrackResponse($track->getTrackNumber(), $response);
                    return true;
                } catch (ServiceException $exception) {
                    $response = $this->responseDataMapper->createErrorResponse(
                        $track->getTrackNumber(),
                        __('Voucher %1 could not be cancelled: %2', $track->getTrackNumber(), $exception->getMessage()),
                        $request->getSalesShipment(),
                        $track
                    );
                    $artifactsContainer->addErrorResponse($track->getTrackNumber(), $response);
                    return false;
                }
            }
        );
    }
}
