<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Test\Integration\TestCase\Updater;

use DeutschePost\Internetmarke\Model\ProductList\Updater;
use DeutschePost\Internetmarke\Model\Webservice\ProdWsFactoryInterface;
use DeutschePost\Internetmarke\Test\Integration\TestDouble\HttpMockServiceFactory;
use DeutschePost\Internetmarke\Test\Integration\TestDouble\HttpResponseFactory;
use DeutschePost\Internetmarke\Test\Integration\TestDouble\QueueHttpClient;
use DeutschePost\Sdk\ProdWS\Api\ProductInformationServiceInterface;
use DeutschePost\Sdk\ProdWS\Service\ProductInformationService\BasicProduct;
use DeutschePost\Sdk\ProdWS\Service\ProductInformationService\SalesProduct;
use DeutschePost\Sdk\ProdWS\Service\ProductInformationService\ProductAddition;
use DeutschePost\Sdk\ProdWS\Service\ProductInformationService\SalesProductComponents;
use DeutschePost\Sdk\ProdWS\Service\ProductInformationService\SalesProductList;
use DeutschePost\Sdk\ProdWS\Service\ProductInformationService\Value;
use DeutschePost\Sdk\ProdWS\Service\ProductInformationService\ValueRange;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for the contract product updater.
 *
 * Exercises ProductList\Updater with real Internetmarke SDK code and DB persistence.
 * The ProdWS (SOAP) service is mocked at the service level since it's a separate SDK.
 * The Internetmarke REST SDK is mocked only at the HTTP transport level via QueueHttpClient.
 *
 * @magentoAppArea adminhtml
 * @magentoDbIsolation enabled
 */
class ContractProductUpdaterTest extends TestCase
{
    /**
     * Build a minimal SalesProductList with one product that passes the ProductFilter.
     *
     * @return SalesProductList[]
     */
    private function createTestProductLists(): array
    {
        $price = new Value('EUR', 0.85);
        $length = new ValueRange('mm', 140.0, 235.0);
        $width = new ValueRange('mm', 90.0, 125.0);
        $height = new ValueRange('mm', 0.0, 5.0);
        $weight = new ValueRange('g', 0.0, 20.0);

        $basicProduct = new BasicProduct(
            '100',
            'Standardbrief Basic',
            1,
            'national',
            $price,
            $length,
            $width,
            $height,
            $weight,
            new \DateTime('2025-01-01'),
            null,
        );

        $addition = new ProductAddition(
            '200',
            'Einschreiben',
            1,
            'national',
            new Value('EUR', 2.65),
            new \DateTime('2025-01-01'),
            null,
        );

        $components = new SalesProductComponents($basicProduct, [$addition]);

        $salesProduct = new SalesProduct(
            '500',
            'Standardbrief',
            1,
            '21', // PPL ID 21 — in ProductFilter's supported list
            'national',
            $price,
            $length,
            $width,
            $height,
            $weight,
            $components,
        );

        return [
            new SalesProductList(
                1,
                new \DateTime('2025-01-01'),
                null,
                [$salesProduct],
            ),
        ];
    }

    private function createMockProdWsFactory(array $productLists): ProdWsFactoryInterface
    {
        $productInfoService = $this->createMock(ProductInformationServiceInterface::class);
        $productInfoService->method('getProductLists')->willReturn($productLists);

        $factory = $this->createMock(ProdWsFactoryInterface::class);
        $factory->method('create')->willReturn($productInfoService);

        return $factory;
    }

    private function createUpdater(QueueHttpClient $httpClient, array $productLists): Updater
    {
        $objectManager = Bootstrap::getObjectManager();

        return $objectManager->create(Updater::class, [
            'productsWsFactory' => $this->createMockProdWsFactory($productLists),
            'internetmarkeFactory' => new HttpMockServiceFactory($httpClient),
        ]);
    }

    /**
     * Happy path: fetches product lists and contract products, persists to database.
     *
     * HTTP queue: auth → contractProducts success (2 products: 10001@85, 10002@160)
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function updateProductListsFetchesAndPersists(): void
    {
        $httpClient = new QueueHttpClient();
        $httpClient->addResponse(HttpResponseFactory::authSuccess());
        $httpClient->addResponse(HttpResponseFactory::publicCatalogSuccess());

        $productLists = $this->createTestProductLists();
        $this->createUpdater($httpClient, $productLists)->updateProductLists();

        $objectManager = Bootstrap::getObjectManager();
        /** @var ResourceConnection $resource */
        $resource = $objectManager->get(ResourceConnection::class);
        $connection = $resource->getConnection();

        // Verify product list was saved
        $listRows = $connection->fetchAll(
            $connection->select()->from($resource->getTableName('deutschepost_product_list')),
        );
        self::assertCount(1, $listRows);
        self::assertEquals(1, $listRows[0]['list_id']);

        // Verify sales product was saved
        $salesRows = $connection->fetchAll(
            $connection->select()->from($resource->getTableName('deutschepost_product_sales')),
        );
        self::assertCount(1, $salesRows);
        self::assertEquals(500, $salesRows[0]['product_id']);
        self::assertEquals(21, $salesRows[0]['ppl_id']);

        // Verify basic product was saved
        $basicRows = $connection->fetchAll(
            $connection->select()->from($resource->getTableName('deutschepost_product_basic')),
        );
        self::assertCount(1, $basicRows);
        self::assertEquals(100, $basicRows[0]['product_id']);
    }

    /**
     * API returns 400 Bad Request — wrapped as CouldNotSaveException.
     *
     * HTTP queue: auth → 400 error response
     */
    #[\PHPUnit\Framework\Attributes\Test]
    public function updateProductListsWrapsApiError(): void
    {
        $httpClient = new QueueHttpClient();
        $httpClient->addResponse(HttpResponseFactory::authSuccess());
        $httpClient->addResponse(HttpResponseFactory::error(400, 'badRequest400.json'));

        $productLists = $this->createTestProductLists();

        $this->expectException(CouldNotSaveException::class);
        $this->expectExceptionMessageMatches('/Bad Request/');

        $this->createUpdater($httpClient, $productLists)->updateProductLists();
    }
}
