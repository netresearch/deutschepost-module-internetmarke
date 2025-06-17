<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\ProductList;

use DeutschePost\Internetmarke\Api\Data\SalesProductInterface;
use DeutschePost\Internetmarke\Model\ResourceModel\ProductList\SalesProduct as SalesProductResource;
use DeutschePost\Internetmarke\Model\ResourceModel\ProductList\SaveHandler;
use Magento\Framework\Model\AbstractModel;

class SalesProduct extends AbstractModel implements SalesProductInterface
{
    /**
     * Initialize resource model.
     */
    #[\Override]
    protected function _construct()
    {
        $this->_init(SalesProductResource::class);
        parent::_construct();
    }

    #[\Override]
    public function getId(): int
    {
        return (int) parent::getId();
    }

    #[\Override]
    public function getPPLId(): int
    {
        return (int) $this->getData(self::PPL_ID);
    }

    #[\Override]
    public function getName(): string
    {
        return (string) $this->getData(self::NAME);
    }

    #[\Override]
    public function getPrice(): int
    {
        return (int) $this->getData(self::PRICE);
    }

    #[\Override]
    public function save(): never
    {
        $className = SaveHandler::class;
        throw new \RuntimeException("Use {$className} for updating the product list.");
    }

    #[\Override]
    public function delete(): never
    {
        $className = SaveHandler::class;
        throw new \RuntimeException("Use {$className} for updating the product list.");
    }
}
