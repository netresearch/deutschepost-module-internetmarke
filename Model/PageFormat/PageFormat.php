<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\PageFormat;

use DeutschePost\Internetmarke\Api\Data\PageFormatInterface;
use DeutschePost\Internetmarke\Model\ResourceModel\PageFormat\PageFormat as PageFormatResource;
use Magento\Framework\Model\AbstractModel;

class PageFormat extends AbstractModel implements PageFormatInterface
{
    /**
     * Initialize resource model.
     */
    #[\Override]
    protected function _construct()
    {
        $this->_init(PageFormatResource::class);
        parent::_construct();
    }

    #[\Override]
    public function getId(): int
    {
        return (int) parent::getId();
    }

    #[\Override]
    public function getName(): string
    {
        return $this->getData(self::NAME);
    }

    #[\Override]
    public function getDescription(): string
    {
        return $this->getData(self::DESCRIPTION);
    }

    #[\Override]
    public function getPrintMedium(): string
    {
        return $this->getData(self::PRINT_MEDIUM);
    }

    #[\Override]
    public function getVoucherColumns(): int
    {
        return (int) $this->getData(self::VOUCHER_COLUMNS);
    }

    #[\Override]
    public function getVoucherRows(): int
    {
        return (int) $this->getData(self::VOUCHER_ROWS);
    }

    #[\Override]
    public function isAddressPossible(): bool
    {
        return (bool) $this->getData(self::IS_ADDRESS_POSSIBLE);
    }

    #[\Override]
    public function isImagePossible(): bool
    {
        return (bool) $this->getData(self::IS_IMAGE_POSSIBLE);
    }
}
