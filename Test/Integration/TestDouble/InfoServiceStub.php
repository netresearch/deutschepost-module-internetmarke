<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Test\Integration\TestDouble;

use DeutschePost\Sdk\OneClickForApp\Api\AccountInformationServiceInterface;
use DeutschePost\Sdk\OneClickForApp\Api\Data\ContractProductInterface;
use DeutschePost\Sdk\OneClickForApp\Api\Data\PageFormatInterface;

class InfoServiceStub implements AccountInformationServiceInterface
{
    /**
     * @var PageFormatInterface[]
     */
    private $pageFormats;

    /**
     * @var ContractProductInterface[]
     */
    private $contractProducts;

    public function __construct(array $pageFormats = [], array $contractProducts = [])
    {
        $this->pageFormats = $pageFormats;
        $this->contractProducts = $contractProducts;
    }

    public function getPageFormats(): array
    {
        return $this->pageFormats;
    }

    public function getContractProducts(): array
    {
        return $this->contractProducts;
    }
}
