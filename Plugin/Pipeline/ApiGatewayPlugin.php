<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Plugin\Pipeline;

use DeutschePost\Internetmarke\Api\Data\SalesProductInterface;
use DeutschePost\Internetmarke\Model\Pipeline\ApiGatewayFactory;
use DeutschePost\Internetmarke\Model\ProductList\SalesProductCollectionLoader;
use Dhl\Paket\Model\Pipeline\ApiGateway;
use Dhl\Paket\Model\ShipmentDate\ShipmentDate;
use Dhl\ShippingCore\Api\Data\Pipeline\ShipmentResponse\LabelResponseInterface;
use Dhl\ShippingCore\Api\Data\Pipeline\ShipmentResponse\ShipmentErrorResponseInterface;
use Magento\Shipping\Model\Shipment\Request;

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
}
