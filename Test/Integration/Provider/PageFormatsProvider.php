<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Test\Integration\Provider;

use DeutschePost\Sdk\OneClickForApp\Api\Data\PageFormatInterface;
use DeutschePost\Sdk\OneClickForApp\Service\AccountInformationService\PageFormat;

class PageFormatsProvider
{
    /**
     * Obtain SDK response objects.
     *
     * @return PageFormatInterface[]
     */
    public static function getPageFormats(): array
    {
        return [
            new PageFormat(
                1,
                'A4 Letter',
                'Foo Bar',
                PageFormat::ORIENTATION_PORTRAIT,
                PageFormat::PAGE_MEDIUM_REGULAR_PAGE,
                210,
                297,
                2,
                5,
                true,
                false
            ),
            new PageFormat(
                2,
                'A3 Letter',
                'Foo Bar',
                PageFormat::ORIENTATION_PORTRAIT,
                PageFormat::PAGE_MEDIUM_REGULAR_PAGE,
                148,
                210,
                1,
                2,
                true,
                false
            ),
        ];
    }
}
