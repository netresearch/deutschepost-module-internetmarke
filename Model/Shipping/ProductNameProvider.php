<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\Shipping;

use DeutschePost\Internetmarke\Api\Data\SalesProductInterface;
use DeutschePost\Internetmarke\Api\Data\SalesProductInterfaceFactory;
use DeutschePost\Internetmarke\Model\ProductList\SalesProduct;
use DeutschePost\Internetmarke\Model\ResourceModel\ProductList\SalesProduct as SalesProductResource;
use Dhl\Paket\Model\Carrier\Paket;
use Netresearch\ShippingCore\Api\Shipping\ProductNameProviderInterface;

class ProductNameProvider implements ProductNameProviderInterface
{
    /**
     * @var SalesProductInterfaceFactory
     */
    private $factory;

    /**
     * @var SalesProductResource
     */
    private $resource;

    public function __construct(SalesProductInterfaceFactory $factory, SalesProductResource $resource)
    {
        $this->factory = $factory;
        $this->resource = $resource;
    }

    public function getCarrierCode(): string
    {
        return Paket::CARRIER_CODE;
    }

    public function getName(string $productCode): string
    {
        /** @var SalesProduct $salesProduct */
        $salesProduct = $this->factory->create();
        $this->resource->load($salesProduct, $productCode, SalesProductInterface::PPL_ID);
        return $salesProduct->getName();
    }
}
