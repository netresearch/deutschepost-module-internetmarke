<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Plugin\Sales\Order\Shipment\Track;

use DeutschePost\Internetmarke\Api\Data\SalesProductInterface;
use DeutschePost\Internetmarke\Api\Data\SalesProductInterfaceFactory;
use DeutschePost\Internetmarke\Model\Pipeline\CreateShipments\ResponseProcessor\CreateTrackExtension;
use DeutschePost\Internetmarke\Model\ProductList\SalesProduct;
use DeutschePost\Internetmarke\Model\ResourceModel\ProductList\SalesProduct as SalesProductResource;
use DeutschePost\Internetmarke\Model\Shipment\TrackAdditional;
use Dhl\Paket\Model\Carrier\Paket;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\Order\Shipment\Track;

class UpdateTitle
{
    /**
     * @var SalesProductInterfaceFactory
     */
    private $factory;

    /**
     * @var SalesProductResource
     */
    private $resource;

    /**
     * @var mixed[]
     */
    private $packages;

    public function __construct(SalesProductInterfaceFactory $factory, SalesProductResource $resource)
    {
        $this->factory = $factory;
        $this->resource = $resource;
    }

    /**
     * Update track title.
     *
     * By default, Magento sets the carrier title as track title.
     * To make it more obvious that this particular label was created
     * via the INTERNETMARKE API, we set the shipping product name instead.
     *
     * @see \Magento\Shipping\Model\Shipping\LabelGenerator::addTrackingNumbersToShipment
     *
     * @param Shipment $shipment
     * @param Track $track
     * @return null
     */
    public function beforeAddTrack(Shipment $shipment, Track $track)
    {
        if ($track->getCarrierCode() !== Paket::CARRIER_CODE) {
            // DP tracks are created through the Parcel Germany carrier
            return null;
        }

        /** @var TrackAdditional[] $shipmentTracks */
        $shipmentTracks = $shipment->getData(CreateTrackExtension::TRACK_EXTENSION_KEY);
        if (!$shipmentTracks) {
            // not a DP shipment, regular DHL shipment
            return null;
        }

        if (empty($this->packages)) {
            $this->packages = $shipment->getPackages();
        }

        $package = array_shift($this->packages);
        if (!isset($package['params'], $package['params']['shipping_product'])) {
            return null;
        }

        /** @var SalesProduct $salesProduct */
        $salesProduct = $this->factory->create();
        $this->resource->load($salesProduct, $package['params']['shipping_product'], SalesProductInterface::PPL_ID);

        $track->setTitle($salesProduct->getName());

        return null;
    }
}
