<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\ResourceModel\ProductList;

use DeutschePost\Internetmarke\Api\Data\SalesProductInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\Model\ResourceModel\Db\VersionControl\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\VersionControl\RelationComposite;
use Magento\Framework\Model\ResourceModel\Db\VersionControl\Snapshot;
use Magento\Framework\Stdlib\DateTime\DateTime;

class SalesProduct extends AbstractDb
{
    /**
     * @var DateTime
     */
    private $date;

    /**
     * Entities with no auto-increment ID must toggle "$_useIsObjectNew" property
     * to distinguish between create and update operations.
     * @see \Magento\Framework\Model\ResourceModel\Db\AbstractDb::isObjectNotNew
     *
     * @var bool
     */
    protected $_useIsObjectNew = true;

    /**
     * Entities with no auto-increment ID must toggle "$_isPkAutoIncrement" property
     * to preserve the ID field.
     * @see \Magento\Framework\Model\ResourceModel\Db\AbstractDb::saveNewObject
     *
     * @var bool
     */
    protected $_isPkAutoIncrement = false;

    public function __construct(
        Context $context,
        Snapshot $entitySnapshot,
        RelationComposite $entityRelationComposite,
        DateTime $date,
        $connectionName = null
    ) {
        $this->date = $date;

        parent::__construct($context, $entitySnapshot, $entityRelationComposite, $connectionName);
    }

    /**
     * Init main table and primary key.
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('deutschepost_product_sales', SalesProductInterface::PRODUCT_ID);
        $this->_uniqueFields = [
            'field' => [
                'product_list_id',
                'product_id'
            ],
            'title' => 'The product must be unique per product list.',
        ];
    }

    public function save(AbstractModel $object)
    {
        $className = SaveHandler::class;
        throw new \RuntimeException("Use {$className} for updating the product list.");
    }

    public function delete(AbstractModel $object)
    {
        $className = SaveHandler::class;
        throw new \RuntimeException("Use {$className} for updating the product list.");
    }

    protected function _getLoadSelect($field, $value, $object)
    {
        $select = parent::_getLoadSelect($field, $value, $object);

        $select->join(
            ['product_list' => $this->getTable('deutschepost_product_list')],
            'product_list_id = product_list.list_id',
            []
        );

        $currentDate = $this->date->gmtDate();
        $select->where('valid_from <= ?', $currentDate);
        $select->where('valid_to >= ? OR valid_to IS NULL', $currentDate);

        return $select;
    }
}
