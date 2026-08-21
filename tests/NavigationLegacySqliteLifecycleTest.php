<?php

declare(strict_types=1);

namespace App\Navigating\Tests;

use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;

final class NavigationLegacySqliteLifecycleTest extends TestCase
{
    public function testLegacyRenameAndSchemaObjectRemovalRollbackAtomicallyOnSqlite(): void
    {
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $connection->executeStatement('CREATE TABLE navigation_item (id INTEGER PRIMARY KEY AUTOINCREMENT, navigation_key VARCHAR(160) NOT NULL)');
        $connection->executeStatement('CREATE INDEX idx_navigation_item_route_name ON navigation_item (navigation_key)');
        $connection->executeStatement('CREATE TABLE navigation_audit (item_id INTEGER NOT NULL)');
        $connection->executeStatement("CREATE TRIGGER trg_navigation_item_insert AFTER INSERT ON navigation_item BEGIN INSERT INTO navigation_audit(item_id) VALUES (NEW.id); END");
        $connection->executeStatement("INSERT INTO navigation_item (navigation_key) VALUES ('legacy_item')");

        $connection->beginTransaction();
        $connection->executeStatement('ALTER TABLE navigation_item RENAME TO navigation_item_legacy_w31');
        $connection->executeStatement('DROP INDEX idx_navigation_item_route_name');
        $connection->executeStatement('DROP TRIGGER trg_navigation_item_insert');
        $connection->executeStatement('CREATE TABLE navigation_item (id INTEGER PRIMARY KEY AUTOINCREMENT, navigation_key VARCHAR(160) NOT NULL)');
        $connection->executeStatement('CREATE INDEX idx_navigation_item_route_name ON navigation_item (navigation_key)');
        $connection->rollBack();

        self::assertTrue($connection->createSchemaManager()->tablesExist(['navigation_item']));
        self::assertFalse($connection->createSchemaManager()->tablesExist(['navigation_item_legacy_w31']));
        self::assertSame('legacy_item', $connection->fetchOne('SELECT navigation_key FROM navigation_item WHERE id = 1'));
        self::assertSame(1, (int) $connection->fetchOne("SELECT COUNT(*) FROM sqlite_schema WHERE type = 'index' AND name = 'idx_navigation_item_route_name'"));
        self::assertSame(1, (int) $connection->fetchOne("SELECT COUNT(*) FROM sqlite_schema WHERE type = 'trigger' AND name = 'trg_navigation_item_insert'"));

        $connection->executeStatement("INSERT INTO navigation_item (navigation_key) VALUES ('second_item')");
        self::assertSame(2, (int) $connection->fetchOne('SELECT MAX(item_id) FROM navigation_audit'));
    }

    public function testForeignKeyCheckDetectsBrokenReferencesEvenWhenEnforcementIsOff(): void
    {
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $connection->executeStatement('PRAGMA foreign_keys = OFF');
        $connection->executeStatement('CREATE TABLE navigation_menu (id INTEGER PRIMARY KEY)');
        $connection->executeStatement('CREATE TABLE navigation_item (id INTEGER PRIMARY KEY, menu_id INTEGER NOT NULL REFERENCES navigation_menu(id))');
        $connection->executeStatement('INSERT INTO navigation_item (id, menu_id) VALUES (1, 999)');

        $violations = $connection->executeQuery('PRAGMA foreign_key_check')->fetchAllAssociative();

        self::assertNotEmpty($violations);
        self::assertSame('navigation_item', $violations[0]['table'] ?? null);
    }
}
