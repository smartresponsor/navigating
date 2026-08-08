<?php

declare(strict_types=1);

namespace App\Navigating\Tests;

use App\Navigating\Service\Navigation\Migration\NavigationLegacyMigrationPlanService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;

final class NavigationLegacyMigrationSemanticTest extends TestCase
{
    public function testLegacyPlanPreservesLocationRoleMetadataTargetAndState(): void
    {
        $connection = $this->legacyConnection();
        $this->insertLegacyRow($connection, [
            'navigation_key' => 'catalog_index',
            'label' => 'Catalog',
            'slug' => 'catalog-index',
            'route_name' => 'catalog.index',
            'route_parameters' => json_encode(['page' => 2], JSON_THROW_ON_ERROR),
            'location' => 'shell.left.middle',
            'operation' => 'index',
            'icon' => 'AppstoreOutlined',
            'required_role' => 'ROLE_ADMIN',
            'position' => 10,
            'enabled' => 0,
            'metadata' => json_encode([
                'domain' => 'catalog',
                'resource' => 'catalog',
                'navigation_scope' => 'system',
                'custom' => ['preserve' => true],
            ], JSON_THROW_ON_ERROR),
            'archived_at' => '2026-08-01 10:00:00',
        ]);

        $service = new NavigationLegacyMigrationPlanService($connection, $this->navigationConfig());
        $plan = $service->createPlan();

        self::assertSame('legacy', $plan['state']);
        self::assertSame(1, $plan['rows']);
        self::assertArrayHasKey('main', $plan['shell_groups']);
        $group = $plan['shell_groups']['main'];
        self::assertSame('shell.left.middle', $group['location']);
        self::assertSame([], $group['visible_for_roles']);
        self::assertSame([], $group['visible_for_scopes']);
        self::assertSame([], $group['visible_for_environments']);

        $item = $group['items']['catalog_index'];
        self::assertFalse($item['enabled']);
        self::assertSame(['ROLE_ADMIN'], $item['visible_for_roles']);
        self::assertSame('catalog.index', $item['target']['route']);
        self::assertSame(['page' => 2], $item['target']['params']);
        self::assertSame('catalog', $item['metadata']['domain']);
        self::assertSame('catalog', $item['metadata']['resource']);
        self::assertSame('system', $item['metadata']['navigation_scope']);
        self::assertSame(['preserve' => true], $item['metadata']['custom']);
        self::assertSame(['main:catalog_index'], $plan['archived_items']);
    }

    public function testLegacyPlanRejectsCanonicalLocationChange(): void
    {
        $connection = $this->legacyConnection();
        $this->insertLegacyRow($connection, [
            'navigation_key' => 'catalog_index',
            'location' => 'shell.context.middle',
        ]);

        $service = new NavigationLegacyMigrationPlanService($connection, $this->navigationConfig());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Refusing location-changing migration.');
        $service->createPlan();
    }

    public function testLegacyPlanRejectsMalformedMetadataJson(): void
    {
        $connection = $this->legacyConnection();
        $this->insertLegacyRow($connection, [
            'navigation_key' => 'catalog_index',
            'metadata' => '{broken',
        ]);

        $service = new NavigationLegacyMigrationPlanService($connection, $this->navigationConfig());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid JSON in metadata for legacy navigation item');
        $service->createPlan();
    }

    public function testLegacyPlanRejectsRouteParametersWithoutRoute(): void
    {
        $connection = $this->legacyConnection();
        $this->insertLegacyRow($connection, [
            'navigation_key' => 'catalog_index',
            'route_name' => '',
            'route_parameters' => json_encode(['page' => 2], JSON_THROW_ON_ERROR),
        ]);

        $service = new NavigationLegacyMigrationPlanService($connection, $this->navigationConfig());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('has route parameters but no route name');
        $service->createPlan();
    }

    private function legacyConnection(): Connection
    {
        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $connection->executeStatement(<<<'SQL'
CREATE TABLE navigation_item (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    navigation_key VARCHAR(160) NOT NULL,
    parent_key VARCHAR(160) DEFAULT NULL,
    label VARCHAR(140) NOT NULL,
    slug VARCHAR(180) DEFAULT NULL,
    route_name VARCHAR(180) NOT NULL,
    route_parameters CLOB NOT NULL,
    location VARCHAR(120) NOT NULL,
    operation VARCHAR(60) NOT NULL,
    icon VARCHAR(80) DEFAULT NULL,
    required_role VARCHAR(80) DEFAULT NULL,
    position INTEGER NOT NULL,
    enabled BOOLEAN NOT NULL,
    metadata CLOB NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    archived_at DATETIME DEFAULT NULL
)
SQL);

        return $connection;
    }

    /** @param array<string, mixed> $override */
    private function insertLegacyRow(Connection $connection, array $override): void
    {
        $row = array_replace([
            'navigation_key' => 'item',
            'parent_key' => null,
            'label' => 'Item',
            'slug' => null,
            'route_name' => 'app.index',
            'route_parameters' => '{}',
            'location' => 'shell.left.middle',
            'operation' => 'index',
            'icon' => null,
            'required_role' => null,
            'position' => 0,
            'enabled' => 1,
            'metadata' => '{}',
            'created_at' => '2026-08-01 00:00:00',
            'updated_at' => '2026-08-01 00:00:00',
            'archived_at' => null,
        ], $override);

        $connection->insert('navigation_item', $row);
    }

    /** @return array<string, mixed> */
    private function navigationConfig(): array
    {
        return [
            'shell_locations' => [
                'shell.left.middle' => [],
                'shell.context.middle' => [],
            ],
            'shell_groups' => [
                'main' => [
                    'label' => 'Main',
                    'slug' => 'main',
                    'location' => 'shell.left.middle',
                    'type' => 'navigation',
                    'priority' => 10,
                    'visible_for_roles' => ['ROLE_USER'],
                    'visible_for_scopes' => ['user'],
                    'visible_for_environments' => ['prod'],
                    'items' => [
                        'catalog_index' => [],
                    ],
                ],
            ],
        ];
    }
}
