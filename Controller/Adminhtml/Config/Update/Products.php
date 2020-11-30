<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Controller\Adminhtml\Config\Update;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultInterface;

class Products extends Action
{
    public const ADMIN_RESOURCE = 'Magento_Shipping::config_shipping';

    /**
     * Update page formats.
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $redirect = $this->resultRedirectFactory->create();
        $redirect->setRefererUrl();
        return $redirect;
    }
}
