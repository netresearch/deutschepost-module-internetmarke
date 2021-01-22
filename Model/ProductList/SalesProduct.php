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
    protected function _construct()
    {
        $this->_init(SalesProductResource::class);
        parent::_construct();
    }

    public function getId(): int
    {
        return (int) parent::getId();
    }

    public function getPPLId(): int
    {
        return (int) $this->getData(self::PPL_ID);
    }

    public function getName(): string
    {
        return $this->getData(self::NAME);
    }

    public function getPrice(): int
    {
        return (int) $this->getData(self::PRICE);
    }

    public function save()
    {
        $className = SaveHandler::class;
        throw new \RuntimeException("Use {$className} for updating the product list.");
    }

    public function delete()
    {
        $className = SaveHandler::class;
        throw new \RuntimeException("Use {$className} for updating the product list.");
    }
}
