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
        self::assertStringContainsString("columns: ['menu_id', 'slug']", $item);
        self::assertStringNotContainsString("columns: ['slug']", $item);
        self::assertStringContainsString('type: Types::JSON', $menu);
        self::assertStringContainsString('type: Types::JSON', $item);
        self::assertStringContainsString('#[ORM\\Version]', $menu);
        self::assertStringContainsString('#[ORM\\Version]', $item);
        self::assertStringContainsString('private int $version = 1;', $menu);
        self::assertStringContainsString('private int $version = 1;', $item);
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
        self::assertStringContainsString('Navigation menu keys must be non-empty strings', $import);
        self::assertStringContainsString('Navigation items in menu', $import);
        self::assertStringContainsString("->setSlug(\$this->nullableString(\$itemConfig['slug'] ?? null))", $import);
        self::assertStringNotContainsString("\$menuSlug.'-'.\$this->slugify(\$itemKey)", $import);
    }

    public function testSnapshotRestoreCanonicalizesDerivedHierarchyAndOperation(): void
    {
        $export = self::read('src/Service/Navigation/Snapshot/NavigationSnapshotExportService.php');
        $import = self::read('src/Service/Navigation/Import/NavigationConfigImportService.php');

        self::assertStringContainsString("unset(\$metadata['parent_key'])", $export);
        self::assertStringContainsString("\$metadata['parent_key'] = \$item->getParent()?->getNavigationKey()", $export);
        self::assertStringContainsString("\$parentKey = \$metadata['parent_key'] ?? \$itemConfig['parent_key'] ?? null", $import);
        self::assertStringContainsString("unset(\$metadata['parent_key'])", $import);
        self::assertStringContainsString("\$itemConfig['operation'] ?? \$metadata['operation'] ?? null", $import);
        self::assertStringNotContainsString("\$metadata['operation'] ?? \$itemConfig['operation'] ?? null", $import);
    }

    public function testFixturesManifestAndPortableBackupRecoveryExist(): void
    {
        $fixture = self::read('src/DataFixtures/NavigationFixture.php');
        $export = self::read('src/Service/Navigation/Snapshot/NavigationSnapshotExportService.php');
        $backup = self::read('src/Command/NavigationBackupCreateCommand.php');
        $restore = self::read('src/Command/NavigationBackupRestoreCommand.php');
        $manifestWrite = self::read('src/Command/NavigationManifestWriteCommand.php');
        $manifestRestore = self::read('src/Command/NavigationManifestRestoreCommand.php');

        self::assertStringContainsString('extends Fixture', $fixture);
        self::assertStringContainsString('navigation.install.json', $fixture);
        self::assertStringContainsString('replaceFromConfig', $fixture);
        self::assertStringContainsString("public const FORMAT = 'smartresponsor.navigation'", $export);
        self::assertStringContainsString('public const VERSION = 1', $export);
        self::assertStringContainsString('sha256', $export);
        self::assertStringContainsString('hash_equals', $export);
        self::assertStringContainsString("name: 'navigation:backup:create'", $backup);
        self::assertStringContainsString("name: 'navigation:backup:restore'", $restore);
        self::assertStringContainsString("name: 'navigation:manifest:write'", $manifestWrite);
        self::assertStringContainsString("name: 'navigation:manifest:restore'", $manifestRestore);
    }

    public function testRuntimeUsesDoctrineAsTheOnlyCompleteMenuInventorySource(): void
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
        self::assertStringContainsString("'slug' => \$menu->getSlug()", $databaseProvider);
        self::assertStringContainsString("'slug' => \$item->getSlug()", $databaseProvider);
        self::assertStringContainsString("'operation' => \$item->getOperation()", $databaseProvider);
        self::assertStringContainsString("unset(\$metadata['parent_key'])", $databaseProvider);
        self::assertStringContainsString('$this->cache->remember', $databaseProvider);
        self::assertStringContainsString('CacheItemPoolInterface', $cacheService);
        self::assertStringContainsString('Events::postPersist', $subscriber);
        self::assertStringContainsString('Events::postUpdate', $subscriber);
        self::assertStringContainsString('Events::postRemove', $subscriber);
        self::assertStringContainsString('doctrine.event_subscriber', $services);
        self::assertStringContainsString("\$cache: '@cache.app'", $services);
        self::assertStringContainsString('NavigationEntityInvariantSubscriber', $services);
    }

    private static function read(string $relativePath): string
    {
        $contents = file_get_contents(dirname(__DIR__).'/'.$relativePath);
        self::assertIsString($contents);
        return $contents;
    }
}
