<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\Config\Source;

use DeutschePost\Internetmarke\Api\Data\PageFormatInterface;
use DeutschePost\Internetmarke\Model\ResourceModel\Pageformat\PageFormatCollection;
use DeutschePost\Internetmarke\Model\ResourceModel\Pageformat\PageFormatCollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

class PageFormats implements OptionSourceInterface
{
    /**
     * @var PageFormatCollectionFactory
     */
    private $collectionFactory;

    public function __construct(PageFormatCollectionFactory $collectionFactory)
    {
        $this->collectionFactory = $collectionFactory;
    }

    public function toOptionArray()
    {
        $collection = $this->collectionFactory->create();

        // only allow single-item page formats
        $collection->addFieldToFilter(PageFormatInterface::VOUCHER_COLUMNS, ['eq' => 1]);
        $collection->addFieldToFilter(PageFormatInterface::VOUCHER_ROWS, ['eq' => 1]);

        /** @var PageFormatInterface[] $pageFormats */
        $pageFormats = $collection->getItems();

        $emptyOption = [
            'value' => '',
            'label' => __('-- Please Select --'),
        ];

        if (empty($pageFormats)) {
            return [$emptyOption];
        }

        $options = [
            $emptyOption,
            'standard' => [
                'label' => __('Standard Formats'),
                'value' => [],
            ],
            'brother' => [
                'label' => 'Brother',
                'value' => [],
            ],
            'dymo' => [
                'label' => 'Dymo',
                'value' => [],
            ],
            'herma' => [
                'label' => 'Herma',
                'value' => [],
            ],
            'leitz' => [
                'label' => 'Leitz',
                'value' => [],
            ],
            'seiko' => [
                'label' => 'Seiko',
                'value' => [],
            ],
            'zweckform' => [
                'label' => 'Zweckform',
                'value' => [],
            ],
        ];

        $companyNames = array_keys($options);

        foreach ($pageFormats as $pageFormat) {
            $company = strstr(strtolower($pageFormat->getName()), ' ', true);

            if (!in_array($company, $companyNames, true)) {
                $company = 'standard';
            }

            $options[$company]['value'][]= [
                'value' => $pageFormat->getId(),
                'label' => sprintf(
                    "%s%s",
                    $pageFormat->getName(),
                    $pageFormat->isAddressPossible() ? ' (A)' : ''
                ),
            ];
        }

        return $options;
    }
}
