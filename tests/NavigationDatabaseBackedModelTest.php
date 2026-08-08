<?php

declare(strict_types=1);

namespace App\Navigating\Tests;

use PHPUnit\Framework\TestCase;

final class NavigationDatabaseBackedModelTest extends TestCase
{
    public function testMenuAndItemUseSQLiteFriendlyDoctrineMapping(): void
    {
        $menu = self::read('src/Entity/NavigationMenu.php');
        $item = self::read('src/Entity/NavigationItem.php');

        self::assertStringContainsString("#[ORM\\Table(name: 'navigation_menu')]", $menu);
        self::assertStringContainsString("#[ORM\\Table(name: 'navigation_item')]", $item);
        self::assertStringContainsString("columns: ['menu_id', 'navigation_key']", $item);
        self::assertStringContainsString('type: Types::JSON', $menu);
        self::assertStringContainsString('type: Types::JSON', $item);
        self::assertStringNotContainsString('jsonb', strtolower($menu.$item));
        self::assertStringNotContainsString('uuid_generate', strtolower($menu.$item));
    }

    public function testMenuVisibilityIsPersistedAndProjected(): void
    {
        $menu = self::read('src/Entity/NavigationMenu.php');
        $provider = self::read('src/Service/Navigation/Provide/NavigationDatabaseConfigProvideService.php');

        self::assertStringContainsString('visibleForRoles', $menu);
        self::assertStringContainsString('visibleForScopes', $menu);
        self::assertStringContainsString('visibleForEnvironments', $menu);
        self::assertStringContainsString("'visible_for_roles' => \$menu->getVisibleForRoles()", $provider);
        self::assertStringContainsString("'visible_for_scopes' => \$menu->getVisibleForScopes()", $provider);
        self::assertStringContainsString("'visible_for_environments' => \$menu->getVisibleForEnvironments()", $provider);
        self::assertStringContainsString('#[ORM\\PreUpdate]', $menu);
    }

    public function testBootstrapUsesOneReusableTransactionalImportService(): void
    {
        $command = self::read('src/Command/NavigationDatabaseImportCommand.php');
        $import = self::read('src/Service/Navigation/Import/NavigationConfigImportService.php');

        self::assertStringContainsString("name: 'navigation:database:import-config'", $command);
        self::assertStringContainsString("'force'", $command);
        self::assertStringContainsString('Navigation database is not empty', $command);
        self::assertStringContainsString('NavigationConfigImportService', $command);
        self::assertStringContainsString('wrapInTransaction', $import);
        self::assertStringContainsString('setParent($parent)', $import);
    }

    public function testSnapshotRestoreCanonicalizesDerivedHierarchyAndOperation(): void
    {
        $snapshot = self::read('src/Service/Navigation/Snapshot/NavigationSnapshotService.php');
        $import = self::read('src/Service/Navigation/Import/NavigationConfigImportService.php');

        self::assertStringContainsString("\$metadata['parent_key'] = \$item->getParent()?->getNavigationKey()", $snapshot);
        self::assertStringContainsString("\$parentKey = \$metadata['parent_key'] ?? \$itemConfig['parent_key'] ?? null", $import);
        self::assertStringContainsString("unset(\$metadata['parent_key'])", $import);
        self::assertStringContainsString("\$itemConfig['operation'] ?? \$metadata['operation'] ?? null", $import);
        self::assertStringNotContainsString("\$metadata['operation'] ?? \$itemConfig['operation'] ?? null", $import);
    }

    public function testFixturesManifestAndPortableBackupRecoveryExist(): void
    {
        $fixture = self::read('src/DataFixtures/NavigationFixture.php');
        $snapshot = self::read('src/Service/Navigation/Snapshot/NavigationSnapshotService.php');
        $backup = self::read('src/Command/NavigationBackupCreateCommand.php');
        $restore = self::read('src/Command/NavigationBackupRestoreCommand.php');
        $manifestWrite = self::read('src/Command/NavigationManifestWriteCommand.php');
        $manifestRestore = self::read('src/Command/NavigationManifestRestoreCommand.php');

        self::assertStringContainsString('extends Fixture', $fixture);
        self::assertStringContainsString('navigation.install.json', $fixture);
        self::assertStringContainsString('replaceFromConfig', $fixture);
        self::assertStringContainsString("public const FORMAT = 'smartresponsor.navigation'", $snapshot);
        self::assertStringContainsString("public const VERSION = 1", $snapshot);
        self::assertStringContainsString('sha256', $snapshot);
        self::assertStringContainsString('hash_equals', $snapshot);
        self::assertStringContainsString("name: 'navigation:backup:create'", $backup);
        self::assertStringContainsString("name: 'navigation:backup:restore'", $restore);
        self::assertStringContainsString("name: 'navigation:manifest:write'", $manifestWrite);
        self::assertStringContainsString("name: 'navigation:manifest:restore'", $manifestRestore);
    }

    public function testRuntimeUsesDoctrineAsTheOnlyMenuInventorySource(): void
    {
        $shellProvider = self::read('src/Service/Navigation/Provide/NavigationShellProvideService.php');
        $databaseProvider = self::read('src/Service/Navigation/Provide/NavigationDatabaseConfigProvideService.php');
        $cacheService = self::read('src/Service/Navigation/Cache/NavigationConfigCacheService.php');
        $subscriber = self::read('src/EventSubscriber/NavigationConfigCacheInvalidationSubscriber.php');
        $services = self::read('config/services.yaml');

        self::assertStringContainsString('databaseConfigProvider->provideConfig()', $shellProvider);
        self::assertStringContainsString("\$config['shell_groups'] = \$databaseGroups", $shellProvider);
        self::assertStringContainsString('Navigation database contains no enabled menus', $shellProvider);
        self::assertStringNotContainsString('[] === $databaseConfig ? $this->navigationConfig : $databaseConfig', $shellProvider);
        self::assertStringContainsString('$this->cache->remember', $databaseProvider);
        self::assertStringContainsString('CacheItemPoolInterface', $cacheService);
        self::assertStringContainsString('Events::postPersist', $subscriber);
        self::assertStringContainsString('Events::postUpdate', $subscriber);
        self::assertStringContainsString('Events::postRemove', $subscriber);
        self::assertStringContainsString('doctrine.event_subscriber', $services);
        self::assertStringContainsString("\$cache: '@cache.app'", $services);
    }

    private static function read(string $relativePath): string
    {
        $contents = file_get_contents(dirname(__DIR__).'/'.$relativePath);
        self::assertIsString($contents);
        return $contents;
    }
}
