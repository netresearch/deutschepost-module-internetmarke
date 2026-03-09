<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Test\Integration\Provider;

use DeutschePost\Sdk\Internetmarke\Api\Data\PageFormatInterface;
use DeutschePost\Sdk\Internetmarke\Model\PageFormat;
use DeutschePost\Sdk\Internetmarke\Model\ResponseType\LabelCount;
use DeutschePost\Sdk\Internetmarke\Model\ResponseType\PageLayout;

class PageFormatsProvider
{
    /**
     * Obtain SDK response objects.
     *
     * @return PageFormatInterface[]
     */
    public static function getPageFormats(): array
    {
        $labelCount1 = SdkModelFactory::create(LabelCount::class, ['labelX' => 2, 'labelY' => 5]);
        $pageLayout1 = SdkModelFactory::create(PageLayout::class, [
            'orientation' => PageFormatInterface::ORIENTATION_PORTRAIT,
            'labelCount' => $labelCount1,
        ]);

        $labelCount2 = SdkModelFactory::create(LabelCount::class, ['labelX' => 1, 'labelY' => 2]);
        $pageLayout2 = SdkModelFactory::create(PageLayout::class, [
            'orientation' => PageFormatInterface::ORIENTATION_PORTRAIT,
            'labelCount' => $labelCount2,
        ]);

        return [
            SdkModelFactory::create(PageFormat::class, [
                'id' => 1,
                'name' => 'A4 Letter',
                'description' => 'Foo Bar',
                'isAddressPossible' => true,
                'isImagePossible' => false,
                'pageType' => PageFormatInterface::PAGE_TYPE_REGULAR_PAGE,
                'pageLayout' => $pageLayout1,
            ]),
            SdkModelFactory::create(PageFormat::class, [
                'id' => 2,
                'name' => 'A3 Letter',
                'description' => 'Foo Bar',
                'isAddressPossible' => true,
                'isImagePossible' => false,
                'pageType' => PageFormatInterface::PAGE_TYPE_REGULAR_PAGE,
                'pageLayout' => $pageLayout2,
            ]),
        ];
    }
}
