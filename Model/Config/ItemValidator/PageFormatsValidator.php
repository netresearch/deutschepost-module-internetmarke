<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\Config\ItemValidator;

use DeutschePost\Internetmarke\Model\ResourceModel\PageFormat\PageFormatCollectionFactory;
use Dhl\ShippingCore\Model\Config\ItemValidator\DhlSection;
use Netresearch\ShippingCore\Api\Config\ItemValidatorInterface;
use Netresearch\ShippingCore\Api\Data\Config\ItemValidator\ResultInterface;
use Netresearch\ShippingCore\Api\Data\Config\ItemValidator\ResultInterfaceFactory;

class PageFormatsValidator implements ItemValidatorInterface
{
    use DhlSection;
    use InternetmarkeGroup;

    /**
     * @var ResultInterfaceFactory
     */
    private $resultFactory;

    /**
     * @var PageFormatCollectionFactory
     */
    private $formatsCollectionFactory;

    public function __construct(
        ResultInterfaceFactory $resultFactory,
        PageFormatCollectionFactory $formatsCollectionFactory
    ) {
        $this->resultFactory = $resultFactory;
        $this->formatsCollectionFactory = $formatsCollectionFactory;
    }

    public function execute(int $storeId): ResultInterface
    {
        $collection = $this->formatsCollectionFactory->create();

        if ($collection->getSize()) {
            $status = ResultInterface::OK;
            $message = __('Page formats are available.');
        } else {
            $status = ResultInterface::ERROR;
            $message = __('Please update page formats.');
        }

        return $this->resultFactory->create(
            [
                'status' => $status,
                'name' => __('Page Formats'),
                'message' => $message,
                'sectionCode' => $this->getSectionCode(),
                'sectionName' => $this->getSectionName(),
                'groupCode' => $this->getGroupCode(),
                'groupName' => $this->getGroupName(),
            ]
        );
    }
}
