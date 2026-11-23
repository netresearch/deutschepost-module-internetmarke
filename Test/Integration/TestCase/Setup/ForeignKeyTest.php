<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Test\Integration\TestCase\Setup;

use Magento\Framework\App\ResourceConnection;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

/**
 * MySQL 8.4 rejects foreign keys that reference anything other than a complete
 * PRIMARY or UNIQUE key (restrict_fk_on_non_standard_key=ON). Magento switches the
 * check off during setup, so installs pass, but importing a database dump fails.
 */
class ForeignKeyTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function foreignKeysReferenceCompleteUniqueKeys(): void
    {
        $resource = Bootstrap::getObjectManager()->get(ResourceConnection::class);
        $connection = $resource->getConnection();
        $tablePattern = str_replace('_', '\\_', $resource->getTableName('deutschepost_')) . '%';

        $nonStandardKeys = $connection->fetchCol(
            <<<'SQL'
            SELECT CONCAT(k.TABLE_NAME, '.', k.CONSTRAINT_NAME)
            FROM information_schema.KEY_COLUMN_USAGE k
            WHERE k.TABLE_SCHEMA = DATABASE()
              AND k.TABLE_NAME LIKE :table_pattern
              AND k.REFERENCED_TABLE_NAME IS NOT NULL
            GROUP BY k.TABLE_NAME, k.CONSTRAINT_NAME, k.REFERENCED_TABLE_NAME
            HAVING GROUP_CONCAT(k.REFERENCED_COLUMN_NAME ORDER BY k.POSITION_IN_UNIQUE_CONSTRAINT) NOT IN (
                SELECT GROUP_CONCAT(s.COLUMN_NAME ORDER BY s.SEQ_IN_INDEX)
                FROM information_schema.STATISTICS s
                WHERE s.TABLE_SCHEMA = DATABASE()
                  AND s.TABLE_NAME = k.REFERENCED_TABLE_NAME
                  AND s.NON_UNIQUE = 0
                GROUP BY s.INDEX_NAME
            )
            SQL,
            ['table_pattern' => $tablePattern]
        );

        self::assertSame([], $nonStandardKeys);
    }
}
