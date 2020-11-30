<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class PageFormatCollection extends AbstractCollection
{
    /**
     * Initialization
     */
    public function _construct()
    {
        $this->_init(\DeutschePost\Internetmarke\Model\PageFormat\PageFormat::class, PageFormat::class);
    }

    /**
     * Clean up all page formats and replace them with the current collection contents.
     *
     * @return $this
     * @throws \Exception
     */
    public function replace()
    {
        $connection = $this->getConnection();
        $connection->beginTransaction();

        try {
            $connection->delete($this->getMainTable());
            $this->save();
            $connection->commit();
        } catch (\Exception $exception) {
            $connection->rollBack();
            throw $exception;
        }

        return $this;
    }
}
