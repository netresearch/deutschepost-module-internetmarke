<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\Pipeline\Shipment;

use DeutschePost\Sdk\OneClickForApp\Api\ShoppingCartPositionBuilderInterface;
use Magento\Shipping\Model\Shipment\Request;

class RequestDataMapper
{
    /**
     * The order request builder.
     *
     * @var ShoppingCartPositionBuilderInterface
     */
    private $itemBuilder;

    public function __construct(ShoppingCartPositionBuilderInterface $itemBuilder)
    {
        $this->itemBuilder = $itemBuilder;
    }

    /**
     * Map the Magento shipment request to an SDK request object using the SDK request builder.
     *
     * @param Request $request The shipment request
     *
     * @return object
     */
    public function mapRequest(Request $request)
    {
    }
}
