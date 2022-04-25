<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Model\ResourceModel\ProductList;

use DeutschePost\Sdk\OneClickForApp\Api\Data\ContractProductInterface;
use DeutschePost\Sdk\ProdWS\Api\Data\SalesProductListInterface;
use Magento\Framework\App\ResourceConnection;

/**
 * Low-level product list access.
 *
 * The model/resource model/collection triad is needed nowhere
 * in the application right now so we speed things up by
 * accessing data directly via the connection.
 *
 * We also operate directly on the SDK data. Mapping it all
 * to application models is avoided for the same reason.
 */
class SaveHandler
{
    private const LIST_TABLE = 'deutschepost_product_list';
    private const SALES_PRODUCT_TABLE = 'deutschepost_product_sales';
    private const BASIC_PRODUCT_TABLE = 'deutschepost_product_basic';
    private const ADDITIONAL_PRODUCT_TABLE = 'deutschepost_product_additional';
    private const PRODUCT_REFERENCE_TABLE = 'deutschepost_product_sales_additional';

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @param SalesProductListInterface[] $productLists
     * @param ContractProductInterface[] $contractProducts
     * @throws \Exception
     */
    public function save(array $productLists, array $contractProducts): void
    {
        $listData = [];
        $salesData = [];
        $basicData = [];
        $additionalData = [];
        $referenceData = [];

        $contractPrices = [];
        foreach ($contractProducts as $contractProduct) {
            $contractPrices[$contractProduct->getId()] = $contractProduct->getPrice();
        }

        $utcTz = new \DateTimeZone('UTC');

        foreach ($productLists as $productList) {
            $validFrom = $productList->getValidFrom();
            $validTo = $productList->getValidTo();

            $listData[] = [
                'list_id' => $productList->getId(),
                'valid_from' => $validFrom->setTimezone($utcTz)->format('Y-m-d H:i:s'),
                'valid_to' => $validTo ? $validTo->setTimezone($utcTz)->format('Y-m-d H:i:s') : null,
            ];

            foreach ($productList->getProducts() as $product) {
                $basicProduct = $product->getComponents()->getBasicProduct();
                $key = sprintf('%s-%s', $basicProduct->getId(), $basicProduct->getVersion());
                $basicData[$key] = [
                    'product_id' => $basicProduct->getId(),
                    'product_list_id' => $productList->getId(),
                    'version' => $basicProduct->getVersion(),
                    'name' => $basicProduct->getName(),
                    'destination' => $basicProduct->getDestination(),
                    'min_length' => $basicProduct->getLength()->getMin(),
                    'max_length' => $basicProduct->getLength()->getMax(),
                    'min_width' => $basicProduct->getWidth()->getMin(),
                    'max_width' => $basicProduct->getWidth()->getMax(),
                    'min_height' => $basicProduct->getHeight()->getMin(),
                    'max_height' => $basicProduct->getHeight()->getMax(),
                    'min_weight' => null,
                    'max_weight' => null,
                    'price' => $basicProduct->getPrice()->getAmount() * 100,
                ];

                if ($basicProduct->getWeight()) {
                    $basicData[$key]['min_weight'] = $basicProduct->getWeight()->getMin();
                    $basicData[$key]['max_weight'] = $basicProduct->getWeight()->getMax();
                }

                $additionalProducts = $product->getComponents()->getProductAdditions();
                foreach ($additionalProducts as $additionalProduct) {
                    $key = sprintf('%s-%s', $additionalProduct->getId(), $additionalProduct->getVersion());
                    $additionalData[$key] = [
                        'product_id' => $additionalProduct->getId(),
                        'product_list_id' => $productList->getId(),
                        'version' => $additionalProduct->getVersion(),
                        'name' => $additionalProduct->getName(),
                        'destination' => $additionalProduct->getDestination(),
                        'price' => $additionalProduct->getPrice()->getAmount() * 100,
                    ];

                    $key = sprintf('%s-%s', $product->getId(), $additionalProduct->getId());
                    $referenceData[$key] = [
                        'sales_product_id' => $product->getId(),
                        'additional_product_id' => $additionalProduct->getId(),
                    ];
                }

                $key = sprintf('%s-%s', $product->getId(), $productList->getId());
                $salesData[$key] = [
                    'product_id' => $product->getId(),
                    'product_list_id' => $productList->getId(),
                    'ppl_id' => $product->getPPLId(),
                    'basic_product_id' => $basicProduct->getId(),
                    'version' => $product->getVersion(),
                    'name' => $product->getName(),
                    'destination' => $product->getDestination(),
                    'min_length' => $product->getLength()->getMin(),
                    'max_length' => $product->getLength()->getMax(),
                    'min_width' => $product->getWidth()->getMin(),
                    'max_width' => $product->getWidth()->getMax(),
                    'min_height' => $product->getHeight()->getMin(),
                    'max_height' => $product->getHeight()->getMax(),
                    'min_weight' => null,
                    'max_weight' => null,
                    'price' => $product->getPrice()->getAmount() * 100,
                    'contract_price' => $contractPrices[$product->getPPLId()] ?? null,
                ];

                if ($product->getWeight()) {
                    $salesData[$key]['min_weight'] = $product->getWeight()->getMin();
                    $salesData[$key]['max_weight'] = $product->getWeight()->getMax();
                }
            }
        }

        $connection = $this->resourceConnection->getConnection();
        $connection->beginTransaction();

        try {
            $connection->delete($connection->getTableName(self::LIST_TABLE));

            $connection->insertMultiple($connection->getTableName(self::LIST_TABLE), $listData);
            $connection->insertMultiple($connection->getTableName(self::BASIC_PRODUCT_TABLE), $basicData);
            $connection->insertMultiple($connection->getTableName(self::ADDITIONAL_PRODUCT_TABLE), $additionalData);
            $connection->insertMultiple($connection->getTableName(self::SALES_PRODUCT_TABLE), $salesData);
            $connection->insertMultiple($connection->getTableName(self::PRODUCT_REFERENCE_TABLE), $referenceData);

            $connection->commit();
        } catch (\Exception $exception) {
            $connection->rollBack();
            throw $exception;
        }
    }
}
