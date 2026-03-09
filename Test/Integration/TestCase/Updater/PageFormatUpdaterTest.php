<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Test\Integration\TestCase\Updater;

use DeutschePost\Internetmarke\Model\PageFormat\PageFormat;
use DeutschePost\Internetmarke\Model\PageFormat\Updater;
use DeutschePost\Internetmarke\Model\ResourceModel\PageFormat\PageFormatCollection;
use DeutschePost\Internetmarke\Model\Webservice\InternetmarkeServiceFactoryInterface;
use DeutschePost\Internetmarke\Test\Integration\TestDouble\HttpMockServiceFactory;
use DeutschePost\Internetmarke\Test\Integration\TestDouble\HttpResponseFactory;
use DeutschePost\Internetmarke\Test\Integration\TestDouble\QueueHttpClient;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for the page format updater.
 *
 * Exercises PageFormat\Updater with real SDK code and DB persistence,
 * mocked only at the HTTP transport level via QueueHttpClient.
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class PageFormatUpdaterTest extends TestCase
{
    private function createUpdater(QueueHttpClient $httpClient): Updater
    {
        $objectManager = Bootstrap::getObjectManager();

        return $objectManager->create(Updater::class, [
            'webserviceFactory' => new HttpMockServiceFactory($httpClient),
        ]);
    }

    /**
     * Happy path: fetches page formats from API and persists to database.
     *
     * HTTP queue: auth → pageFormats success (2 formats)
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function updatePageFormatsFetchesAndPersists(): void
    {
        $httpClient = new QueueHttpClient();
        $httpClient->addResponse(HttpResponseFactory::authSuccess());
        $httpClient->addResponse(HttpResponseFactory::pageFormatsSuccess());

        $this->createUpdater($httpClient)->updatePageFormats();

        $objectManager = Bootstrap::getObjectManager();
        /** @var PageFormatCollection $collection */
        $collection = $objectManager->create(PageFormatCollection::class);

        self::assertCount(2, $collection->getItems());

        /** @var PageFormat $format1 */
        $format1 = $collection->getItemById(1);
        self::assertNotNull($format1);
        self::assertSame('DIN A4 Normalpapier', $format1->getName());
        self::assertSame('REGULARPAGE', $format1->getPrintMedium());
        self::assertSame(1, $format1->getVoucherColumns());
        self::assertSame(1, $format1->getVoucherRows());
        self::assertTrue($format1->isAddressPossible());
        self::assertTrue($format1->isImagePossible());

        /** @var PageFormat $format9 */
        $format9 = $collection->getItemById(9);
        self::assertNotNull($format9);
        self::assertSame('Briefumschlag DIN lang 110 x 220', $format9->getName());
        self::assertSame('ENVELOPE', $format9->getPrintMedium());
        self::assertFalse($format9->isAddressPossible());
        self::assertFalse($format9->isImagePossible());
    }

    /**
     * API returns 400 Bad Request — wrapped as CouldNotSaveException.
     *
     * HTTP queue: auth → 400 error response
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function updatePageFormatsWrapsApiError(): void
    {
        $httpClient = new QueueHttpClient();
        $httpClient->addResponse(HttpResponseFactory::authSuccess());
        $httpClient->addResponse(HttpResponseFactory::error(400, 'badRequest400.json'));

        $this->expectException(CouldNotSaveException::class);
        $this->expectExceptionMessageMatches('/Bad Request/');

        $this->createUpdater($httpClient)->updatePageFormats();
    }
}
