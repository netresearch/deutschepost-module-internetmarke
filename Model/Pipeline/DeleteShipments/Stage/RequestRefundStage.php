<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\Pipeline\DeleteShipments\Stage;

use DeutschePost\Internetmarke\Model\Pipeline\DeleteShipments\ArtifactsContainer;
use DeutschePost\Internetmarke\Model\Pipeline\DeleteShipments\ResponseDataMapper;
use DeutschePost\Internetmarke\Model\Webservice\InternetmarkeServiceFactoryInterface;
use DeutschePost\Sdk\Internetmarke\Exception\ServiceException;
use DeutschePost\Sdk\Internetmarke\Model\RefundRequest;
use DeutschePost\Sdk\Internetmarke\Model\RefundVoucher;
use Netresearch\ShippingCore\Api\Data\Pipeline\ArtifactsContainerInterface;
use Netresearch\ShippingCore\Api\Data\Pipeline\TrackRequest\TrackRequestInterface;
use Netresearch\ShippingCore\Api\Pipeline\RequestTracksStageInterface;

class RequestRefundStage implements RequestTracksStageInterface
{
    /**
     * @var InternetmarkeServiceFactoryInterface
     */
    private $webserviceFactory;

    /**
     * @var ResponseDataMapper
     */
    private $responseDataMapper;

    public function __construct(
        InternetmarkeServiceFactoryInterface $webserviceFactory,
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
    #[\Override]
    public function execute(array $requests, ArtifactsContainerInterface $artifactsContainer): array
    {
        if (empty($requests)) {
            return [];
        }

        $refundService = $this->webserviceFactory->createRefundService();

        return array_filter(
            $requests,
            function (TrackRequestInterface $request) use ($artifactsContainer, $refundService) {
                $track = $request->getSalesTrack();
                if (!$track) {
                    return false;
                }

                $shopOrderId = $track->getExtensionAttributes()->getDpdhlOrderId();
                $voucherId = $track->getExtensionAttributes()->getDpdhlVoucherId();
                $trackId = $track->getExtensionAttributes()->getDpdhlTrackId() ?? '';

                try {
                    $refundService->requestRefund(
                        new RefundRequest($shopOrderId, [new RefundVoucher($voucherId, $trackId)])
                    );
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
