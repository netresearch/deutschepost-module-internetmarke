<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Plugin\Sales\Order\Shipment\Track;

use DeutschePost\Internetmarke\Api\Data\SalesProductInterface;
use DeutschePost\Internetmarke\Api\Data\SalesProductInterfaceFactory;
use DeutschePost\Internetmarke\Model\ProductList\SalesProduct;
use DeutschePost\Internetmarke\Model\ResourceModel\ProductList\SalesProduct as SalesProductResource;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\Order\Shipment\Track;

class AddTrackDetails
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
     * Populate track extension attribute.
     *
     * We cannot persist additional data with the track because we do not
     * have access to the track entity during the process. To solve this,
     * we set the data to the shipment (available during pipeline execution)
     * and move it to the track extension once the track gets created.
     *
     * Persisting the extension attribute is done in a separate step after
     * the track itself was saved.
     *
     * @see \DeutschePost\Internetmarke\Model\Pipeline\Shipment\ResponseProcessor\SetLabelApi::processResponse
     * @see \Magento\Shipping\Model\Shipping\LabelGenerator::addTrackingNumbersToShipment
     *
     * @param Shipment $shipment
     * @param Track $track
     * @return null
     */
    public function beforeAddTrack(Shipment $shipment, Track $track)
    {
        // set api identifier
        $track->getExtensionAttributes()->setDpdhlLabelApi($shipment->getData('dpdhl_label_api'));

        // update title
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
