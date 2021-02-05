<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\ResourceModel\ProductList;

use DeutschePost\Internetmarke\Api\Data\SalesProductInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * @method \DeutschePost\Internetmarke\Model\ProductList\SalesProduct[] getItems()
 */
class SalesProductCollection extends AbstractCollection
{
    /**
     * Initialization
     */
    public function _construct()
    {
        $this->_init(\DeutschePost\Internetmarke\Model\ProductList\SalesProduct::class, SalesProduct::class);
    }

    protected function _initSelect()
    {
        parent::_initSelect();

        $this->getSelect()->reset(Select::COLUMNS);
        $this->getSelect()->columns(
            [
                SalesProductInterface::PRODUCT_ID => SalesProductInterface::PRODUCT_ID,
                SalesProductInterface::PPL_ID => SalesProductInterface::PPL_ID,
                SalesProductInterface::NAME => SalesProductInterface::NAME,
                SalesProductInterface::PRICE => new \Zend_Db_Expr('COALESCE(contract_price, price)'),
            ]
        );

        return $this;
    }

    /**
     * Only query sales products valid for the given date.
     *
     * @param string $date Current UTC datetime.
     * @return SalesProductCollection
     */
    public function setDateFilter(string $date): SalesProductCollection
    {
        $this->getSelect()
             ->join(
                 ['product_list' => $this->getTable('deutschepost_product_list')],
                 'main_table.product_list_id = product_list.list_id',
                 []
             );

        $this->addFieldToFilter('valid_from', ['lteq' => $date]);
        $this->addFieldToFilter('valid_to', [['gteq' => $date], ['null' => true]]);

        return $this;
    }

    /**
     * Only query sales products valid for the given route.
     *
     * @param string $originCountry ISO 2 Code
     * @param string $destinationCountry ISO 2 Code
     * @return SalesProductCollection
     */
    public function setRouteFilter(string $originCountry, string $destinationCountry): SalesProductCollection
    {
        if ($originCountry === 'DE' && $destinationCountry === 'DE') {
            $destination = 'national';
        } elseif ($originCountry === 'DE' && $destinationCountry !== 'DE') {
            $destination = 'international';
        } else {
            $destination = 'other';
        }

        $this->addFieldToFilter('destination', ['eq' => $destination]);

        return $this;
    }

    /**
     * Only query sales products matching the given box dimensions.
     *
     * @param float $length Length in cm
     * @param float $width Width in cm
     * @param float $height Height in cm
     * @return SalesProductCollection
     */
    public function setDimensionsFilter(float $length, float $width, float $height): SalesProductCollection
    {
        $this->addFieldToFilter('min_length', ['lteq' => $length]);
        $this->addFieldToFilter('max_length', ['gteq' => $length]);
        $this->addFieldToFilter('min_width', ['lteq' => $width]);
        $this->addFieldToFilter('max_width', ['gteq' => $width]);
        $this->addFieldToFilter('min_height', ['lteq' => $height]);
        $this->addFieldToFilter('max_height', ['gteq' => $height]);

        return $this;
    }

    /**
     * Only query sales products matching the given box dimensions.
     *
     * @param float $weight Weight in g
     * @return SalesProductCollection
     */
    public function setWeightFilter(float $weight): SalesProductCollection
    {
        $this->addFieldToFilter('min_weight', ['lteq' => $weight]);
        $this->addFieldToFilter('max_weight', ['gteq' => $weight]);

        return $this;
    }
}
