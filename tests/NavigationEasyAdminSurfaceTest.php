<?php

declare(strict_types=1);

namespace App\Navigating\Tests;

use PHPUnit\Framework\TestCase;

final class NavigationEasyAdminSurfaceTest extends TestCase
{
    public function testEasyAdminDoctrineSurfaceFilesAreRegistered(): void
    {
        self::assertFileExists(__DIR__.'/../src/Controllers/Admin/DashboardController.php');
        self::assertFileExists(__DIR__.'/../src/Controllers/Admin/NavigationMenuItemCrudController.php');
        self::assertFileExists(__DIR__.'/../src/Entity/NavigationMenuItem.php');
        self::assertFileExists(__DIR__.'/../src/Repository/NavigationMenuItemRepository.php');
        self::assertFileExists(__DIR__.'/../config/routes/easyadmin.yaml');
        self::assertFileExists(__DIR__.'/../config/standalone/doctrine.yaml');
        self::assertFileExists(__DIR__.'/../config/standalone/security.yaml');
    }

    public function testBackOfficePrefixIsEnvironmentBackedAndRoleAdminProtected(): void
    {
        $dashboard = file_get_contents(__DIR__.'/../src/Controllers/Admin/DashboardController.php');
        $routes = file_get_contents(__DIR__.'/../config/routes/easyadmin.yaml');
        $security = file_get_contents(__DIR__.'/../config/standalone/security.yaml');

        self::assertIsString($dashboard);
        self::assertIsString($routes);
        self::assertIsString($security);
        self::assertStringContainsString("routePath: '/'", $dashboard);
        self::assertStringContainsString("routeName: 'ea'", $dashboard);
        self::assertStringContainsString("prefix: '/%app.back_token%'", $routes);
        self::assertStringContainsString('APP_BACK_TOKEN', $security);
        self::assertStringContainsString("path: '^/%app.back_token%'", $security);
        self::assertStringContainsString('ROLE_ADMIN', $security);
        self::assertStringNotContainsString("routePath: '/ea'", $dashboard);
        self::assertStringNotContainsString("path: '^/ea'", $security);
    }

    public function testNavigationMenuUsesNativeEasyAdminCrudController(): void
    {
        $controller = file_get_contents(__DIR__.'/../src/Controllers/Admin/NavigationMenuItemCrudController.php');
        $entity = file_get_contents(__DIR__.'/../src/Entity/NavigationMenuItem.php');
        self::assertIsString($controller);
        self::assertIsString($entity);

        self::assertStringContainsString('extends AbstractCrudController', $controller);
        self::assertStringContainsString('NavigationMenuItem::class', $controller);
        self::assertStringContainsString('configureActions', $controller);
        self::assertStringContainsString('linkToCrudAction', $controller);
        self::assertStringContainsString('@EasyAdmin/page/content.html.twig', $controller);
        self::assertStringContainsString('#[ORM\\Entity', $entity);
        self::assertStringContainsString("#[ORM\\Table(name: 'navigation_menu_item')]", $entity);
    }

    public function testDoctrineSqliteStandaloneConfigIsPresent(): void
    {
        $composer = file_get_contents(__DIR__.'/../composer.json');
        $doctrine = file_get_contents(__DIR__.'/../config/standalone/doctrine.yaml');
        self::assertIsString($composer);
        self::assertIsString($doctrine);

        self::assertStringContainsString('doctrine/doctrine-bundle', $composer);
        self::assertStringContainsString('doctrine/orm', $composer);
        self::assertStringContainsString('sqlite:///%kernel.project_dir%/var/navigation.sqlite', $doctrine);
        self::assertStringContainsString("prefix: 'App\\\\Navigating\\\\Entity'", $doctrine);
    }

    public function testEntityFirstDatabaseDesignHasIndexesAndNoMigrations(): void
    {
        $entity = file_get_contents(__DIR__.'/../src/Entity/NavigationMenuItem.php');
        self::assertIsString($entity);

        self::assertStringContainsString("#[ORM\\UniqueConstraint(name: 'uniq_navigation_menu_item_menu_key'", $entity);
        self::assertStringContainsString("#[ORM\\Index(name: 'idx_navigation_menu_item_enabled_location_position'", $entity);
        self::assertStringContainsString("#[ORM\\Column(name: 'menu_key'", $entity);
        self::assertStringContainsString("#[ORM\\Column(name: 'parent_key'", $entity);
        self::assertStringContainsString("#[ORM\\Column(name: 'route_name'", $entity);
        self::assertStringContainsString("#[ORM\\Column(name: 'required_role'", $entity);
        self::assertStringContainsString('#[ORM\\Column(type: Types::JSON)]', $entity);
        self::assertDirectoryDoesNotExist(__DIR__.'/../migrations');
        self::assertDirectoryDoesNotExist(__DIR__.'/../src/Migrations');
    }

    public function testEasyAdminCrudExposesEntityFirstFields(): void
    {
        $controller = file_get_contents(__DIR__.'/../src/Controllers/Admin/NavigationMenuItemCrudController.php');
        self::assertIsString($controller);

        self::assertStringContainsString("TextField::new('menuKey')", $controller);
        self::assertStringContainsString("TextField::new('parentKey')", $controller);
        self::assertStringContainsString("TextField::new('requiredRole')", $controller);
        self::assertStringContainsString("TextareaField::new('metadata')", $controller);
    }

    public function testDoctrineStandaloneConfigUsesCurrentDoctrineBundleKeys(): void
    {
        $doctrine = file_get_contents(__DIR__.'/../config/standalone/doctrine.yaml');

        self::assertIsString($doctrine);
        self::assertStringNotContainsString('auto_generate_proxy_classes', $doctrine);
        self::assertStringNotContainsString('controller_resolver', $doctrine);
        self::assertStringContainsString('auto_mapping: false', $doctrine);
        self::assertStringContainsString('mappings:', $doctrine);
        self::assertStringContainsString('Navigating:', $doctrine);
    }
}
