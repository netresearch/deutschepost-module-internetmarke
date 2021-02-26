<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\ProductList;

use DeutschePost\Internetmarke\Model\ResourceModel\ProductList\SalesProductCollection;
use DeutschePost\Internetmarke\Model\ResourceModel\ProductList\SalesProductCollectionFactory;
use Magento\Framework\Stdlib\DateTime\DateTime;

class SalesProductCollectionLoader
{
    /**
     * @var DateTime
     */
    private $date;

    /**
     * @var SalesProductCollectionFactory
     */
    private $productCollectionFactory;

    public function __construct(DateTime $date, SalesProductCollectionFactory $productCollectionFactory)
    {
        $this->date = $date;
        $this->productCollectionFactory = $productCollectionFactory;
    }

    /**
     * Retrieve collection valid at a given UTC date.
     *
     * @param string $date
     * @return SalesProductCollection
     */
    public function getCollectionByUtcDate(string $date): SalesProductCollection
    {
        $productCollection = $this->productCollectionFactory->create();
        $productCollection->setDateFilter($date);
        return $productCollection;
    }

    /**
     * Retrieve collection valid at a given date.
     *
     * @param \DateTimeInterface $date
     * @return SalesProductCollection
     */
    public function getCollectionByDate(\DateTimeInterface $date): SalesProductCollection
    {
        $date = $date->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
        return $this->getCollectionByUtcDate($date);
    }

    /**
     * Retrieve currently valid product collection
     *
     * @return SalesProductCollection
     */
    public function getCollection(): SalesProductCollection
    {
        $date = $this->date->gmtDate();
        return $this->getCollectionByUtcDate($date);
    }
}
