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

    public function testBootstrapCommandIsExplicitAndNonDestructiveByDefault(): void
    {
        $command = self::read('src/Command/NavigationDatabaseImportCommand.php');

        self::assertStringContainsString("name: 'navigation:database:import-config'", $command);
        self::assertStringContainsString("'force'", $command);
        self::assertStringContainsString('Navigation database is not empty', $command);
        self::assertStringContainsString('wrapInTransaction', $command);
        self::assertStringContainsString('setParent($parent)', $command);
    }

    public function testRuntimePrefersDoctrineStorageAndCachesProjection(): void
    {
        $shellProvider = self::read('src/Service/Navigation/Provide/NavigationShellProvideService.php');
        $databaseProvider = self::read('src/Service/Navigation/Provide/NavigationDatabaseConfigProvideService.php');
        $cache = self::read('src/Service/Navigation/Cache/NavigationConfigCacheService.php');
        $subscriber = self::read('src/EventSubscriber/NavigationConfigCacheInvalidationSubscriber.php');
        $services = self::read('config/services.yaml');

        self::assertStringContainsString('databaseConfigProvider->provideConfig()', $shellProvider);
        self::assertStringContainsString('[] === $databaseConfig ? $this->navigationConfig : $databaseConfig', $shellProvider);
        self::assertStringContainsString('$this->cache->remember', $databaseProvider);
        self::assertStringContainsString('CacheItemPoolInterface', $cache);
        self::assertStringContainsString('Events::postPersist', $subscriber);
        self::assertStringContainsString('Events::postUpdate', $subscriber);
        self::assertStringContainsString('Events::postRemove', $subscriber);
        self::assertStringContainsString('doctrine.event_subscriber', $services);
        self::assertStringContainsString("$cache: '@cache.app'", $services);
    }

    private static function read(string $relativePath): string
    {
        $contents = file_get_contents(dirname(__DIR__).'/'.$relativePath);
        self::assertIsString($contents);

        return $contents;
    }
}
