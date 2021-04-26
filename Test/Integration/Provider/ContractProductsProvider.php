<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Test\Integration\Provider;

use DeutschePost\Sdk\OneClickForApp\Api\Data\ContractProductInterface;
use DeutschePost\Sdk\OneClickForApp\Service\AccountInformationService\ContractProduct;

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
            new ContractProduct(2, 303),
            new ContractProduct(4, 808),
        ];
    }
}
