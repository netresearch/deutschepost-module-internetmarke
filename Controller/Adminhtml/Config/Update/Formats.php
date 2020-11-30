<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Controller\Adminhtml\Config\Update;

use DeutschePost\Internetmarke\Model\PageFormat\Updater;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\CouldNotSaveException;

class Formats extends Action
{
    public const ADMIN_RESOURCE = 'Magento_Shipping::config_shipping';

    /**
     * @var Updater
     */
    private $updater;

    public function __construct(Context $context, Updater $updater)
    {
        $this->updater = $updater;

        parent::__construct($context);
    }

    public function execute(): ResultInterface
    {
        try {
            $this->updater->updatePageFormats();
            $this->messageManager->addSuccessMessage(__('Page formats were successfully updated.'));
        } catch (CouldNotSaveException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        }

        $redirect = $this->resultRedirectFactory->create();
        $redirect->setRefererUrl();
        return $redirect;
    }
}
