<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Plugin\Pipeline\Shipment;

use DeutschePost\Internetmarke\Api\Data\SalesProductInterface;
use DeutschePost\Internetmarke\Model\Pipeline\ApiGatewayFactory;
use DeutschePost\Internetmarke\Model\Pipeline\DeleteShipments\TrackRequest\RollbackRequestFactory;
use DeutschePost\Internetmarke\Model\ProductList\SalesProductCollectionLoader;
use Dhl\Paket\Model\Pipeline\ApiGateway;
use Dhl\Paket\Model\ShipmentDate\ShipmentDate;
use Magento\Framework\Registry;
use Magento\Sales\Api\Data\ShipmentTrackInterface;
use Magento\Shipping\Model\Shipment\Request;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentResponse\LabelResponseInterface;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentResponse\ShipmentErrorResponseInterface;
use Netresearch\ShippingCore\Api\Data\Pipeline\TrackRequest\TrackRequestInterface;
use Netresearch\ShippingCore\Api\Pipeline\ShipmentResponseProcessorInterface;

class ApiGatewayPlugin
{
    /**
     * @var ShipmentDate
     */
    private $shipmentDate;

    /**
     * @var SalesProductCollectionLoader
     */
    private $productCollectionLoader;

    /**
     * @var RollbackRequestFactory
     */
    private $rollbackRequestFactory;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var ShipmentResponseProcessorInterface
     */
    private $bulkCreateResponseProcessor;

    /**
     * @var ApiGatewayFactory
     */
    private $apiGatewayFactory;

    public function __construct(
        ShipmentDate $shipmentDate,
        SalesProductCollectionLoader $productCollectionLoader,
        RollbackRequestFactory $rollbackRequestFactory,
        Registry $registry,
        ShipmentResponseProcessorInterface $bulkCreateResponseProcessor,
        ApiGatewayFactory $apiGatewayFactory
    ) {
        $this->shipmentDate = $shipmentDate;
        $this->productCollectionLoader = $productCollectionLoader;
        $this->rollbackRequestFactory = $rollbackRequestFactory;
        $this->registry = $registry;
        $this->bulkCreateResponseProcessor = $bulkCreateResponseProcessor;
        $this->apiGatewayFactory = $apiGatewayFactory;
    }

    /**
     * Obtain the cancellation request for an Internetmarke voucher.
     *
     * When a voucher is cancelled via shipment details page, then the shipment track
     * is set and the request can be used for cancellation. When the voucher is cancelled
     * via label rollback (and the track with its extension attributes is not yet persisted),
     * then a new cancellation request gets created with all necessary data set.
     *
     * @param TrackRequestInterface $cancelRequest
     * @return TrackRequestInterface|null
     */
    private function getCancellationRequest(TrackRequestInterface $cancelRequest): ?TrackRequestInterface
    {
        $track = $cancelRequest->getSalesTrack();

        // check if we have a regular Internetmarke voucher cancellation request
        if ($track instanceof ShipmentTrackInterface
            && $track->getExtensionAttributes()
            && $track->getExtensionAttributes()->getDpdhlOrderId()
        ) {
            return $cancelRequest;
        }

        // fall back to creating a new cancellation request if applicable
        return $this->rollbackRequestFactory->create($cancelRequest->getStoreId(), $cancelRequest->getTrackNumber());
    }

    /**
     * Intercept the DHL Paket API gateway to invoke the Internetmarke API.
     *
     * The array of shipment requests gets divided by selected shipping product:
     * BCS product orders are sent to the BCS API, Internetmarke product orders
     * are sent to the One Click For App API.
     *
     * Note that there is currently no way to tell apart bulk shipment from
     * packaging popup requests. This needs a solution once we implement bulk
     * shipment with Internetmarke products because they require different
     * post processors to be registered.
     *
     * @param ApiGateway $subject
     * @param callable $proceed
     * @param Request[] $shipmentRequests
     * @return LabelResponseInterface[]|ShipmentErrorResponseInterface[]
     */
    public function aroundCreateShipments(ApiGateway $subject, callable $proceed, array $shipmentRequests): array
    {
        $storeId = current($shipmentRequests)->getOrderShipment()->getStoreId();

        $shipmentDate = $this->shipmentDate->getDate($storeId);
        $productCollection = $this->productCollectionLoader->getCollectionByDate($shipmentDate);

        $pplIds = $productCollection->getColumnValues(SalesProductInterface::PPL_ID);

        $ours = [];
        $theirs = [];

        foreach ($shipmentRequests as $requestIndex => $shipmentRequest) {
            $packages = $shipmentRequest->getData('packages');
            $packageId = $shipmentRequest->getData('package_id');
            $productCode = $packages[$packageId]['params']['shipping_product'];

            if (in_array($productCode, $pplIds, true)) {
                $ours[$requestIndex] = $shipmentRequest;
            } else {
                $theirs[$requestIndex] = $shipmentRequest;
            }
        }

        if (empty($ours)) {
            return $proceed($theirs);
        }

        if ($this->registry->registry('current_shipment')) {
            // packaging popup, manual action
            $apiGateway = $this->apiGatewayFactory->create(['storeId' => $storeId]);
        } else {
            // mass action
            $apiGateway = $this->apiGatewayFactory->create([
                'storeId' => $storeId,
                'createResponseProcessor' => $this->bulkCreateResponseProcessor,
            ]);
        }

        return array_merge($proceed($theirs), $apiGateway->createShipments($ours));
    }

    /**
     * Intercept the DHL Paket API gateway to invoke the Internetmarke API.
     *
     * The array of cancel requests gets divided by availability of the
     * extension attribute ´dpdhl_order_id´. All cancel requests that have the
     * extension attribute will go the the One Click For Refund API
     * and others will go to the BCS API.
     *
     * @param ApiGateway $subject
     * @param callable $proceed
     * @param TrackRequestInterface[] $cancelRequests
     * @return array
     */
    public function aroundCancelShipments(ApiGateway $subject, callable $proceed, array $cancelRequests): array
    {
        $storeId = current($cancelRequests)->getStoreId();

        $ours = [];
        $theirs = [];
        foreach ($cancelRequests as $requestIndex => $cancelRequest) {
            if ($ourRequest = $this->getCancellationRequest($cancelRequest)) {
                $ours[$requestIndex] = $ourRequest;
            } else {
                $theirs[$requestIndex] = $cancelRequest;
            }
        }

        if (empty($ours)) {
            return $proceed($theirs);
        }

        $apiGateway = $this->apiGatewayFactory->create(['storeId' => $storeId]);
        return array_merge($proceed($theirs), $apiGateway->cancelShipments($ours));
    }
}
