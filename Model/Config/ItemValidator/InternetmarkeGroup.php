<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\Config\ItemValidator;

use Magento\Framework\Phrase;

trait InternetmarkeGroup
{
    public function getGroupCode(): string
    {
        return Group::CODE;
    }

    public function getGroupName(): Phrase
    {
        return __('Deutsche Post Internetmarke');
    }
}
