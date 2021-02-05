<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\ViewModel\Adminhtml\System\Config\InfoBox;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class Additional implements ArgumentInterface
{
    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    public function __construct(UrlInterface $urlBuilder)
    {
        $this->urlBuilder = $urlBuilder;
    }

    public function getConfigGroupLink(): string
    {
        return $this->urlBuilder->getUrl(
            'adminhtml/system_config/edit',
            [
                'section' => 'dhlshippingsolutions',
                '_fragment' => 'dhlshippingsolutions_dpim-link',
            ]
        );
    }
}
