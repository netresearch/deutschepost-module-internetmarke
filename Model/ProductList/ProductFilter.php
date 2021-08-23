<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\ProductList;

use DeutschePost\Sdk\ProdWS\Api\Data\SalesProductInterface;
use DeutschePost\Sdk\ProdWS\Api\Data\SalesProductListInterface;
use DeutschePost\Sdk\ProdWS\Service\ProductInformationService\SalesProductList;

/**
 * Filter shipping products.
 *
 * Only a subset of the shipping products available at the web service
 * are supported in the application. Remove the products that cannot be
 * used for shipping ecommerce goods anyway.
 */
class ProductFilter
{
    /**
     * List of PPL IDs to be used in the application.
     *
     * @var int[]
     */
    private $supportedProducts = [
        21, 31, 41, 197, 198, 199, 282, 290, 1022, 1027, 1029, 1032, 1037, 1039, 1042, 1047, 1049, 10051, 10071,
        10091, 10246, 10247, 10248, 10249, 10250, 10251, 10252, 10253, 10254, 10255, 10256, 10257, 10258, 10259,
        10260, 10261, 10270, 10271, 10272, 10273, 10280, 10281, 10282, 10283, 10284, 10285, 10286, 10287, 10292,
        10293, 11056, 11076, 11096
    ];

    /**
     * @param SalesProductListInterface[] $productLists
     * @return SalesProductListInterface[]
     */
    public function filter(array $productLists): array
    {
        return array_map(
            function (SalesProductListInterface $productList) {
                return new SalesProductList(
                    $productList->getId(),
                    $productList->getValidFrom(),
                    $productList->getValidTo(),
                    array_filter(
                        $productList->getProducts(),
                        function (SalesProductInterface $salesProduct) {
                            return in_array((int) $salesProduct->getPPLId(), $this->supportedProducts);
                        }
                    )
                );
            },
            $productLists
        );
    }
}
