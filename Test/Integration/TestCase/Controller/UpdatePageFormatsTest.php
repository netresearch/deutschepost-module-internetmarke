<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Test\Integration\TestCase\Controller;

use DeutschePost\Internetmarke\Model\PageFormat\PageFormat;
use DeutschePost\Internetmarke\Model\ResourceModel\PageFormat\PageFormatCollection;
use DeutschePost\Internetmarke\Model\Webservice\InternetmarkeServiceFactoryInterface;
use DeutschePost\Internetmarke\Test\Integration\Provider\PageFormatsProvider;
use DeutschePost\Internetmarke\Test\Integration\TestDouble\InternetmarkeTestFactory;
use DeutschePost\Sdk\Internetmarke\Api\Data\PageFormatInterface;
use Magento\Framework\Exception\AuthenticationException;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class UpdatePageFormatsTest extends AbstractBackendController
{
    /**
     * The resource used to authorize action
     *
     * @var string
     */
    protected $resource = 'Magento_Shipping::config_shipping';

    /**
     * The uri at which to access the controller
     *
     * @var string
     */
    protected $uri = 'backend/dpim/config_update/formats';

    /**
     * @var string
     */
    protected $httpMethod = 'GET';

    /**
     * Replace DI preference, register test service factory to prevent API calls.
     *
     * @throws AuthenticationException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->_objectManager->configure(
            [
                'preferences' => [
                    InternetmarkeServiceFactoryInterface::class => InternetmarkeTestFactory::class
                ]
            ]
        );
    }

    /**
     * @return PageFormatInterface[]
     */
    public static function dataProvider(): array
    {
        return [
            [PageFormatsProvider::getPageFormats()],
        ];
    }

    /**
     * @param PageFormatInterface[] $apiPageFormats
     */
    #[\PHPUnit\Framework\Attributes\Test]
    #[\PHPUnit\Framework\Attributes\DataProvider('dataProvider')]
    public function updatePageFormats(array $apiPageFormats)
    {
        $serviceFactory = $this->_objectManager->create(
            InternetmarkeTestFactory::class,
            ['pageFormats' => $apiPageFormats]
        );
        $this->_objectManager->addSharedInstance($serviceFactory, InternetmarkeTestFactory::class);

        $pageFormatIds = array_map(
            function (PageFormatInterface $pageFormat) {
                return $pageFormat->getId();
            },
            $apiPageFormats
        );

        $this->dispatch($this->uri);

        /** @var PageFormatCollection $collection */
        $collection = $this->_objectManager->create(PageFormatCollection::class);
        $pageFormats = $collection->addFieldToFilter(PageFormat::FORMAT_ID, ['in' => $pageFormatIds])->getItems();

        self::assertCount(count($apiPageFormats), $pageFormats);
        foreach ($apiPageFormats as $apiPageFormat) {
            self::assertArrayHasKey($apiPageFormat->getId(), $pageFormats);

            /** @var PageFormat $pageFormat */
            $pageFormat = $pageFormats[$apiPageFormat->getId()];
            self::assertSame($apiPageFormat->getId(), $pageFormat->getId());
            self::assertSame($apiPageFormat->getName(), $pageFormat->getName());
            $labelCount = $apiPageFormat->getPageLayout()->getLabelCount();
            self::assertSame($labelCount->getLabelX(), $pageFormat->getVoucherColumns());
            self::assertSame($labelCount->getLabelY(), $pageFormat->getVoucherRows());
        }
    }
}
