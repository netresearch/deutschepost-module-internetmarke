<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\PageFormat;

use DeutschePost\Internetmarke\Api\Data\PageFormatInterface;
use DeutschePost\Internetmarke\Api\Data\PageFormatInterfaceFactory;
use DeutschePost\Internetmarke\Model\ResourceModel\PageFormat\PageFormatCollectionFactory;
use DeutschePost\Internetmarke\Model\Webservice\OneClickForAppFactoryInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Psr\Log\LoggerInterface;

class Updater
{
    /**
     * @var OneClickForAppFactoryInterface
     */
    private $webserviceFactory;

    /**
     * @var PageFormatCollectionFactory
     */
    private $collectionFactory;

    /**
     * @var PageFormatInterfaceFactory
     */
    private $itemFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        OneClickForAppFactoryInterface $webserviceFactory,
        PageFormatCollectionFactory $collectionFactory,
        PageFormatInterfaceFactory $itemFactory,
        LoggerInterface $logger
    ) {
        $this->webserviceFactory = $webserviceFactory;
        $this->collectionFactory = $collectionFactory;
        $this->itemFactory = $itemFactory;
        $this->logger = $logger;
    }

    /**
     * @throws CouldNotSaveException
     */
    public function updatePageFormats()
    {
        try {
            $webservice = $this->webserviceFactory->createInfoService();
            $pageFormats = $webservice->getPageFormats();

            $collection = $this->collectionFactory->create();
            foreach ($pageFormats as $pageFormat) {
                /** @var PageFormat $item */
                $item = $this->itemFactory->create(
                    [
                        'data' => [
                            PageFormatInterface::FORMAT_ID => $pageFormat->getId(),
                            PageFormatInterface::NAME => $pageFormat->getName(),
                            PageFormatInterface::DESCRIPTION => $pageFormat->getDescription(),
                            PageFormatInterface::PRINT_MEDIUM => $pageFormat->getPrintMedium(),
                            PageFormatInterface::VOUCHER_COLUMNS => $pageFormat->getColumns(),
                            PageFormatInterface::VOUCHER_ROWS => $pageFormat->getRows(),
                            PageFormatInterface::IS_ADDRESS_POSSIBLE => $pageFormat->isAddressPossible(),
                            PageFormatInterface::IS_IMAGE_POSSIBLE => $pageFormat->isImagePossible(),
                        ]
                    ]
                );

                $collection->addItem($item);
            }

            $collection->replace();
        } catch (\Exception $exception) {
            $this->logger->error('Page formats update failed.', ['exception' => $exception]);
            throw new CouldNotSaveException(__('Failed to update page formats: %1', $exception->getMessage()));
        }
    }
}
