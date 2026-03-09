<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Test\Integration\Provider;

use DeutschePost\Sdk\Internetmarke\Api\Data\ContractProductInterface;
use DeutschePost\Sdk\Internetmarke\Model\ContractProduct;

class ContractProductsProvider
{
    /**
     * Obtain SDK response objects.
     *
     * @return ContractProductInterface[]
     */
    public static function getContractProducts(): array
    {
        return [
            SdkModelFactory::create(ContractProduct::class, ['productCode' => 2, 'price' => 303]),
            SdkModelFactory::create(ContractProduct::class, ['productCode' => 4, 'price' => 808]),
        ];
    }
}
