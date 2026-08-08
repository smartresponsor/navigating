<?php

declare(strict_types=1);

namespace App\Navigating\Tests;

use PHPUnit\Framework\TestCase;

final class NavigationEasyAdminSurfaceTest extends TestCase
{
    public function testNativeEasyAdminExceptionFilesExist(): void
    {
        self::assertFileExists(__DIR__.'/../src/Controllers/Admin/DashboardController.php');
        self::assertFileExists(__DIR__.'/../src/Controllers/Admin/NavigationMenuCrudController.php');
        self::assertFileExists(__DIR__.'/../src/Controllers/Admin/NavigationItemCrudController.php');
        self::assertFileExists(__DIR__.'/../src/Controllers/Admin/AGENTS.md');
        self::assertFileExists(__DIR__.'/../src/Entity/NavigationMenu.php');
        self::assertFileExists(__DIR__.'/../src/Entity/NavigationItem.php');
        self::assertFileExists(__DIR__.'/../src/Repository/NavigationMenuRepository.php');
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
        self::assertStringContainsString("redirectToRoute('ea_navigation_menu_index')", $dashboard);
        self::assertStringContainsString("linkToCrud('Menus'", $dashboard);
        self::assertStringContainsString("linkToCrud('Items'", $dashboard);
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

    public function testNavigationEntitiesUseNativeEasyAdminCrud(): void
    {
        $menuController = self::read('src/Controllers/Admin/NavigationMenuCrudController.php');
        $itemController = self::read('src/Controllers/Admin/NavigationItemCrudController.php');

        self::assertStringContainsString('extends AbstractCrudController', $menuController);
        self::assertStringContainsString('return NavigationMenu::class;', $menuController);
        self::assertStringContainsString("#[IsGranted('ROLE_ADMIN')]", $menuController);

        self::assertStringContainsString('extends AbstractCrudController', $itemController);
        self::assertStringContainsString('return NavigationItem::class;', $itemController);
        self::assertStringContainsString("#[IsGranted('ROLE_ADMIN')]", $itemController);
        self::assertStringContainsString("AssociationField::new('menu')", $itemController);
        self::assertStringContainsString("AssociationField::new('parent')", $itemController);
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
