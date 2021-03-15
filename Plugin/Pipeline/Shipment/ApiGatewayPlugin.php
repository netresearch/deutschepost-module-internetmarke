<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Plugin\Pipeline\Shipment;

use DeutschePost\Internetmarke\Api\Data\SalesProductInterface;
use DeutschePost\Internetmarke\Model\Pipeline\ApiGatewayFactory;
use DeutschePost\Internetmarke\Model\ProductList\SalesProductCollectionLoader;
use Dhl\Paket\Model\Pipeline\ApiGateway;
use Dhl\Paket\Model\ShipmentDate\ShipmentDate;
use Magento\Shipping\Model\Shipment\Request;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentResponse\LabelResponseInterface;
use Netresearch\ShippingCore\Api\Data\Pipeline\ShipmentResponse\ShipmentErrorResponseInterface;
use Netresearch\ShippingCore\Api\Data\Pipeline\TrackRequest\TrackRequestInterface;

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
     * @var ApiGatewayFactory
     */
    private $apiGatewayFactory;

    public function __construct(
        ShipmentDate $shipmentDate,
        SalesProductCollectionLoader $productCollectionLoader,
        ApiGatewayFactory $apiGatewayFactory
    ) {
        $this->shipmentDate = $shipmentDate;
        $this->productCollectionLoader = $productCollectionLoader;
        $this->apiGatewayFactory = $apiGatewayFactory;
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

        $apiGateway = $this->apiGatewayFactory->create(['storeId' => $storeId]);
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
            $track = $cancelRequest->getSalesTrack();
            $extensionAttributes = $track->getExtensionAttributes();
            if ($extensionAttributes && $extensionAttributes->getDpdhlOrderId()) {
                $ours[$requestIndex] = $cancelRequest;
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
