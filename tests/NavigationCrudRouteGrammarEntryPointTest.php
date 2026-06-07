<?php

declare(strict_types=1);

namespace App\Navigating\Tests;

use PHPUnit\Framework\TestCase;

final class NavigationCrudRouteGrammarEntryPointTest extends TestCase
{
    public function testCanonicalCrudRouteBridgeExists(): void
    {
        $controller = self::read('src/Controllers/Admin/NavigationCrudRouteController.php');
        foreach (self::routeNames() as $routeName) {
            self::assertStringContainsString("name: '".$routeName."'", $controller);
        }
        self::assertStringContainsString('AdminUrlGenerator', $controller);
        self::assertStringContainsString('NavigationItemCrudController::class', $controller);
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
        $routeMap = self::read('config/platform/routes/crud/navigation.yaml');
        foreach (self::routeNames() as $routeName) {
            self::assertStringContainsString($routeName.':', $routeMap);
        }
        self::assertStringContainsString('ea_crud_action: archiveItem', $routeMap);
        self::assertStringContainsString('ea_crud_action: duplicateItem', $routeMap);
    }

    public function testEntitySupportsIdSlugAndArchiveGrammarVariants(): void
    {
        self::assertStringContainsString('uniq_navigation_item_slug', self::read('src/Entity/NavigationItem.php'));
        self::assertStringContainsString('archived_at', self::read('src/Entity/NavigationItem.php'));
        self::assertStringContainsString('function findOneBySlug', self::read('src/Repository/NavigationItemRepository.php'));
    }

    public function testCanonicalCrudBridgeUsesExplicitHttpMethods(): void
    {
        $controller = self::read('src/Controllers/Admin/NavigationCrudRouteController.php');

        foreach (self::expectedRouteMethods() as $routeName => $methods) {
            self::assertStringContainsString("name: '".$routeName."'", $controller);
            self::assertStringContainsString('methods: ['.self::formatPhpMethods($methods).']', $controller);
        }

        self::assertStringNotContainsString("methods: ['ANY']", $controller);
    }

    public function testRouteMapRegistryCarriesHttpMethodGrammar(): void
    {
        $routeMap = self::read('config/platform/routes/crud/navigation.yaml');

        foreach (self::expectedRouteMethods() as $routeName => $methods) {
            self::assertStringContainsString($routeName.':', $routeMap);
            self::assertStringContainsString('methods: ['.self::formatYamlMethods($methods).']', $routeMap);
        }
    }

    /** @return list<string> */
    private static function routeNames(): array
    {
        return ['navigation.index', 'navigation.show_id', 'navigation.show_slug', 'navigation.new', 'navigation.create', 'navigation.edit_id', 'navigation.edit_slug', 'navigation.update_id', 'navigation.update_slug', 'navigation.delete_id', 'navigation.delete_slug', 'navigation.bulk', 'navigation.import', 'navigation.export', 'navigation.archive_id', 'navigation.archive_slug', 'navigation.restore_id', 'navigation.restore_slug', 'navigation.duplicate_id', 'navigation.duplicate_slug'];
    }

    /** @return array<string, list<string>> */
    private static function expectedRouteMethods(): array
    {
        return [
            'navigation.index' => ['GET'],
            'navigation.show_id' => ['GET'],
            'navigation.show_slug' => ['GET'],
            'navigation.new' => ['GET'],
            'navigation.create' => ['POST'],
            'navigation.edit_id' => ['GET'],
            'navigation.edit_slug' => ['GET'],
            'navigation.update_id' => ['POST'],
            'navigation.update_slug' => ['POST'],
            'navigation.delete_id' => ['POST'],
            'navigation.delete_slug' => ['POST'],
            'navigation.bulk' => ['POST'],
            'navigation.import' => ['GET', 'POST'],
            'navigation.export' => ['GET'],
            'navigation.archive_id' => ['POST'],
            'navigation.archive_slug' => ['POST'],
            'navigation.restore_id' => ['POST'],
            'navigation.restore_slug' => ['POST'],
            'navigation.duplicate_id' => ['POST'],
            'navigation.duplicate_slug' => ['POST'],
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
