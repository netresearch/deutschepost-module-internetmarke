<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Controller\Adminhtml\Config\Update;

use DeutschePost\Internetmarke\Model\ProductList\Updater;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\CouldNotSaveException;

class Products extends Action implements HttpGetActionInterface
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

    /**
     * Update product lists.
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        try {
            $this->updater->updateProductLists();
            $this->messageManager->addSuccessMessage(__('Product lists were successfully updated.'));
        } catch (CouldNotSaveException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        }

        $redirect = $this->resultRedirectFactory->create();
        $redirect->setRefererUrl();
        return $redirect;
    }
}
