<?php

declare(strict_types=1);

namespace App\Navigating\Tests;

use PHPUnit\Framework\TestCase;

final class NavigationCrudRouteGrammarEntryPointTest extends TestCase
{
    public function testCanonicalCrudRouteBridgeExists(): void
    {
        $controller = self::read('src/Controllers/Admin/NavigationMenuCrudRouteController.php');
        foreach (self::routeNames() as $routeName) {
            self::assertStringContainsString("name: '".$routeName."'", $controller);
        }
        self::assertStringContainsString('AdminUrlGenerator', $controller);
        self::assertStringContainsString('NavigationMenuItemCrudController::class', $controller);
        self::assertStringContainsString("#[IsGranted('ROLE_ADMIN')]", $controller);
    }

    public function testCanonicalCrudRoutesAreImportedUnderBackOfficePrefix(): void
    {
        self::assertStringContainsString("prefix: '/%app.back_token%'", self::read('config/routes/navigation_admin_crud.yaml'));
        self::assertStringContainsString('navigation_admin_crud', self::read('config/routes_dev.yaml'));
        self::assertStringContainsString("path: '^/%app.back_token%'", self::read('config/standalone/security.yaml'));
    }

    public function testRouteMapRegistryMatchesCanonicalGrammar(): void
    {
        $routeMap = self::read('config/platform/routes/crud/navigation.menu.yaml');
        foreach (self::routeNames() as $routeName) {
            self::assertStringContainsString($routeName.':', $routeMap);
        }
        self::assertStringContainsString('ea_crud_action: archiveItem', $routeMap);
        self::assertStringContainsString('ea_crud_action: duplicateItem', $routeMap);
    }

    public function testEntitySupportsIdSlugAndArchiveGrammarVariants(): void
    {
        self::assertStringContainsString('uniq_navigation_menu_item_slug', self::read('src/Entity/NavigationMenuItem.php'));
        self::assertStringContainsString('archived_at', self::read('src/Entity/NavigationMenuItem.php'));
        self::assertStringContainsString('function findOneBySlug', self::read('src/Repository/NavigationMenuItemRepository.php'));
    }

    public function testCanonicalCrudBridgeUsesExplicitHttpMethods(): void
    {
        $controller = self::read('src/Controllers/Admin/NavigationMenuCrudRouteController.php');

        foreach (self::expectedRouteMethods() as $routeName => $methods) {
            self::assertStringContainsString("name: '".$routeName."'", $controller);
            self::assertStringContainsString('methods: ['.self::formatPhpMethods($methods).']', $controller);
        }

        self::assertStringNotContainsString("methods: ['ANY']", $controller);
    }

    public function testRouteMapRegistryCarriesHttpMethodGrammar(): void
    {
        $routeMap = self::read('config/platform/routes/crud/navigation.menu.yaml');

        foreach (self::expectedRouteMethods() as $routeName => $methods) {
            self::assertStringContainsString($routeName.':', $routeMap);
            self::assertStringContainsString('methods: ['.self::formatYamlMethods($methods).']', $routeMap);
        }
    }

    /** @return list<string> */
    private static function routeNames(): array
    {
        return ['navigation.menu.index', 'navigation.menu.show_id', 'navigation.menu.show_slug', 'navigation.menu.new', 'navigation.menu.create', 'navigation.menu.edit_id', 'navigation.menu.edit_slug', 'navigation.menu.update_id', 'navigation.menu.update_slug', 'navigation.menu.delete_id', 'navigation.menu.delete_slug', 'navigation.menu.bulk', 'navigation.menu.import', 'navigation.menu.export', 'navigation.menu.archive_id', 'navigation.menu.archive_slug', 'navigation.menu.restore_id', 'navigation.menu.restore_slug', 'navigation.menu.duplicate_id', 'navigation.menu.duplicate_slug'];
    }

    /** @return array<string, list<string>> */
    private static function expectedRouteMethods(): array
    {
        return [
            'navigation.menu.index' => ['GET'],
            'navigation.menu.show_id' => ['GET'],
            'navigation.menu.show_slug' => ['GET'],
            'navigation.menu.new' => ['GET'],
            'navigation.menu.create' => ['POST'],
            'navigation.menu.edit_id' => ['GET'],
            'navigation.menu.edit_slug' => ['GET'],
            'navigation.menu.update_id' => ['POST'],
            'navigation.menu.update_slug' => ['POST'],
            'navigation.menu.delete_id' => ['POST'],
            'navigation.menu.delete_slug' => ['POST'],
            'navigation.menu.bulk' => ['POST'],
            'navigation.menu.import' => ['GET', 'POST'],
            'navigation.menu.export' => ['GET'],
            'navigation.menu.archive_id' => ['POST'],
            'navigation.menu.archive_slug' => ['POST'],
            'navigation.menu.restore_id' => ['POST'],
            'navigation.menu.restore_slug' => ['POST'],
            'navigation.menu.duplicate_id' => ['POST'],
            'navigation.menu.duplicate_slug' => ['POST'],
        ];
    }

    /** @param list<string> $methods */
    private static function formatPhpMethods(array $methods): string
    {
        return implode(', ', array_map(static fn (string $method): string => "'".$method."'", $methods));
    }

    /** @param list<string> $methods */
    private static function formatYamlMethods(array $methods): string
    {
        return implode(', ', $methods);
    }

    private static function read(string $relativePath): string
    {
        $contents = file_get_contents(dirname(__DIR__).'/'.$relativePath);
        self::assertIsString($contents);

        return $contents;
    }
}
