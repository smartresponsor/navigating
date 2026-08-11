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

    public function testAllFunctionalMenuFieldsAreAdministrable(): void
    {
        $controller = self::read('src/Controllers/Admin/NavigationMenuCrudController.php');

        foreach ([
            'menuKey',
            'slug',
            'label',
            'location',
            'type',
            'visibleForRoles',
            'visibleForScopes',
            'visibleForEnvironments',
            'priority',
            'enabled',
            'metadata',
        ] as $field) {
            self::assertStringContainsString("new('{$field}')", $controller, $field);
        }

        self::assertStringContainsString("setSearchFields(['menuKey', 'slug', 'label', 'location', 'type'])", $controller);
        self::assertStringContainsString("DateTimeField::new('objectCreatedAt')->hideOnForm()", $controller);
        self::assertStringContainsString("DateTimeField::new('objectModifiedAt')->hideOnForm()", $controller);
    }

    public function testAllFunctionalItemFieldsAreAdministrable(): void
    {
        $controller = self::read('src/Controllers/Admin/NavigationItemCrudController.php');

        foreach ([
            'menu',
            'parent',
            'navigationKey',
            'label',
            'slug',
            'type',
            'routeName',
            'path',
            'routeParameters',
            'operation',
            'icon',
            'badge',
            'visibleForRoles',
            'visibleForScopes',
            'visibleForEnvironments',
            'position',
            'enabled',
            'metadata',
            'archivedAt',
        ] as $field) {
            self::assertStringContainsString("new('{$field}')", $controller, $field);
        }

        self::assertStringContainsString("'menu.menuKey'", $controller);
        self::assertStringContainsString("'parent.navigationKey'", $controller);
        self::assertStringContainsString('Parent must belong to the same menu.', $controller);
        self::assertStringContainsString('Hierarchy is represented by the parent association, not metadata.parent_key.', $controller);
        self::assertStringContainsString("DateTimeField::new('objectCreatedAt')->hideOnForm()", $controller);
        self::assertStringContainsString("DateTimeField::new('objectModifiedAt')->hideOnForm()", $controller);
    }

    public function testParentAssociationIsMenuAwareInAdministration(): void
    {
        $controller = self::read('src/Controllers/Admin/NavigationItemCrudController.php');

        self::assertStringContainsString('use Doctrine\\ORM\\QueryBuilder;', $controller);
        self::assertStringContainsString('->autocomplete(callback: static function (NavigationItem $candidate): string', $controller);
        self::assertStringContainsString("sprintf('[%s] %s — %s'", $controller);
        self::assertStringContainsString('null !== $currentItem?->getMenu()', $controller);
        self::assertStringContainsString('->setQueryBuilder(static function (QueryBuilder $queryBuilder)', $controller);
        self::assertStringContainsString('navigation_parent_menu', $controller);
        self::assertStringContainsString('navigation_current_item_id', $controller);
    }

    public function testDuplicateActionKeepsDerivedValuesInsidePersistenceLimits(): void
    {
        $controller = self::read('src/Controllers/Admin/NavigationItemCrudController.php');

        self::assertStringContainsString("\$token = date('YmdHis').'-'.bin2hex(random_bytes(5));", $controller);
        self::assertStringContainsString("appendWithinLimit(\$item->getNavigationKey(), '.copy.'.\$token, 160)", $controller);
        self::assertStringContainsString("appendWithinLimit(\$item->getLabel(), ' copy', 140)", $controller);
        self::assertStringContainsString("appendWithinLimit(\$item->getSlug(), '-copy-'.\$token, 180)", $controller);
        self::assertStringContainsString('private function appendWithinLimit', $controller);
    }

    public function testEasyAdminExceptionHasNearestAutomationRules(): void
    {
        $rules = self::read('src/Controllers/Admin/AGENTS.md');

        self::assertStringContainsString('EASYADMIN_NATIVE_EXCEPTION', $rules);
        self::assertStringContainsString('NavigationMenuCrudController.php', $rules);
        self::assertStringContainsString('NavigationItemCrudController.php', $rules);
        self::assertStringContainsString('all administrator-managed functional persistence fields', $rules);
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
