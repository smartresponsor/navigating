<?php

declare(strict_types=1);

namespace App\Navigating\Tests;

use PHPUnit\Framework\TestCase;

final class NavigationEasyAdminSurfaceTest extends TestCase
{
    public function testNativeEasyAdminExceptionFilesExist(): void
    {
        self::assertFileExists(__DIR__.'/../src/Controllers/Admin/DashboardController.php');
        self::assertFileExists(__DIR__.'/../src/Controllers/Admin/NavigationItemCrudController.php');
        self::assertFileExists(__DIR__.'/../src/Controllers/Admin/AGENTS.md');
        self::assertFileExists(__DIR__.'/../src/Entity/NavigationItem.php');
        self::assertFileExists(__DIR__.'/../src/Repository/NavigationItemRepository.php');
        self::assertFileExists(__DIR__.'/../config/routes/easyadmin.yaml');
        self::assertFileExists(__DIR__.'/../config/standalone/doctrine.yaml');
        self::assertFileExists(__DIR__.'/../config/standalone/security.yaml');
    }

    public function testDashboardRemainsNativeEasyAdminAndRoleProtected(): void
    {
        $dashboard = self::read('src/Controllers/Admin/DashboardController.php');

        self::assertStringContainsString('extends AbstractDashboardController', $dashboard);
        self::assertStringContainsString('#[AdminDashboard(', $dashboard);
        self::assertStringContainsString("routePath: '/'", $dashboard);
        self::assertStringContainsString("routeName: 'ea'", $dashboard);
        self::assertStringContainsString("#[IsGranted('ROLE_ADMIN')]", $dashboard);
        self::assertStringContainsString("redirectToRoute('ea_navigation_item_index')", $dashboard);
    }

    public function testRoutePrefixIsEnvironmentBacked(): void
    {
        $routes = self::read('config/routes/easyadmin.yaml');
        $security = self::read('config/standalone/security.yaml');

        self::assertStringContainsString('type: easyadmin.routes', $routes);
        self::assertStringContainsString("prefix: '/%app.back_token%'", $routes);
        self::assertStringContainsString('APP_BACK_TOKEN', $security);
        self::assertStringContainsString('ROLE_ADMIN', $security);
    }

    public function testNavigationItemUsesNativeEasyAdminCrud(): void
    {
        $controller = self::read('src/Controllers/Admin/NavigationItemCrudController.php');

        self::assertStringContainsString('extends AbstractCrudController', $controller);
        self::assertStringContainsString('return NavigationItem::class;', $controller);
        self::assertStringContainsString("#[IsGranted('ROLE_ADMIN')]", $controller);
        self::assertStringContainsString('configureActions', $controller);
        self::assertStringContainsString('linkToCrudAction', $controller);
        self::assertStringContainsString('@EasyAdmin/page/content.html.twig', $controller);
    }

    public function testEasyAdminExceptionHasNearestAutomationRules(): void
    {
        self::assertStringContainsString(
            'EASYADMIN_NATIVE_EXCEPTION',
            self::read('src/Controllers/Admin/AGENTS.md'),
        );
        self::assertStringContainsString(
            'Admin entry points live in `src/Controllers/Admin/`.',
            self::read('README.md'),
        );
        self::assertStringContainsString(
            'Native EasyAdmin templates are used',
            self::read('README.md'),
        );
    }

    private static function read(string $relativePath): string
    {
        $contents = file_get_contents(dirname(__DIR__).'/'.$relativePath);
        self::assertIsString($contents);

        return $contents;
    }
}
