<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Test\Integration\TestDouble;

use DeutschePost\Sdk\Internetmarke\Api\CatalogServiceInterface;
use DeutschePost\Sdk\Internetmarke\Api\Data\ContractProductInterface;
use DeutschePost\Sdk\Internetmarke\Api\Data\PageFormatInterface;

class CatalogServiceStub implements CatalogServiceInterface
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

    public function getPublicCatalog(): array
    {
        return [];
    }

    public function getPrivateCatalog(): array
    {
        return [];
    }
}
