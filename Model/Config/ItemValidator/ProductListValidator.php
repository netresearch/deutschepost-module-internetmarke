<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\Config\ItemValidator;

use DeutschePost\Internetmarke\Model\ProductList\SalesProductCollectionLoader;
use Dhl\ShippingCore\Model\Config\ItemValidator\DhlSection;
use Netresearch\ShippingCore\Api\Config\ItemValidatorInterface;
use Netresearch\ShippingCore\Api\Data\Config\ItemValidator\ResultInterface;
use Netresearch\ShippingCore\Api\Data\Config\ItemValidator\ResultInterfaceFactory;

class ProductListValidator implements ItemValidatorInterface
{
    use DhlSection;
    use InternetmarkeGroup;

    /**
     * @var ResultInterfaceFactory
     */
    private $resultFactory;

    /**
     * @var SalesProductCollectionLoader
     */
    private $productCollectionLoader;

    public function __construct(
        ResultInterfaceFactory $resultFactory,
        SalesProductCollectionLoader $productCollectionLoader
    ) {
        $this->resultFactory = $resultFactory;
        $this->productCollectionLoader = $productCollectionLoader;
    }

    public function execute(int $storeId): ResultInterface
    {
        $collection = $this->productCollectionLoader->getCollection();

        if ($collection->getSize()) {
            $status = ResultInterface::OK;
            $message = __('Product list is available.');
        } else {
            $status = ResultInterface::ERROR;
            $message = __('Please update shipping products.');
        }

        return $this->resultFactory->create(
            [
                'status' => $status,
                'name' => __('Product List'),
                'message' => $message,
                'sectionCode' => $this->getSectionCode(),
                'sectionName' => $this->getSectionName(),
                'groupCode' => $this->getGroupCode(),
                'groupName' => $this->getGroupName(),
            ]
        );
    }
}
