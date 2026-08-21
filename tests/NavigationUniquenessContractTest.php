<?php

declare(strict_types=1);

namespace App\Navigating\Tests;

use PHPUnit\Framework\TestCase;

final class NavigationUniquenessContractTest extends TestCase
{
    public function testDatabaseAndApplicationUniquenessContractsStayAligned(): void
    {
        $menuEntity = self::read('src/Entity/NavigationMenu.php');
        $itemEntity = self::read('src/Entity/NavigationItem.php');
        $menuRepository = self::read('src/Repository/NavigationMenuRepository.php');
        $itemRepository = self::read('src/Repository/NavigationItemRepository.php');
        $service = self::read('src/Service/Navigation/Persistence/NavigationEntityUniquenessService.php');

        self::assertStringContainsString("uniq_navigation_menu_key", $menuEntity);
        self::assertStringContainsString("uniq_navigation_menu_slug", $menuEntity);
        self::assertStringContainsString("uniq_navigation_item_menu_key", $itemEntity);
        self::assertStringContainsString("uniq_navigation_item_menu_slug", $itemEntity);

        self::assertStringContainsString('existsOtherWithMenuKey', $menuRepository);
        self::assertStringContainsString('existsOtherWithSlug', $menuRepository);
        self::assertStringContainsString('existsOtherWithNavigationKey', $itemRepository);
        self::assertStringContainsString("->andWhere('item.menu = :menu')", $itemRepository);
        self::assertStringContainsString('existsOtherWithSlug($menu', $service);
        self::assertStringContainsString('already in use in menu', $service);
    }

    public function testEasyAdminShowsPrecheckErrorsAndHandlesDatabaseRaces(): void
    {
        $extension = self::read('src/Form/Extension/NavigationEntityInvariantTypeExtension.php');
        $menuController = self::read('src/Controllers/Admin/NavigationMenuCrudController.php');
        $itemController = self::read('src/Controllers/Admin/NavigationItemCrudController.php');

        self::assertStringContainsString('NavigationEntityUniquenessService', $extension);
        self::assertStringContainsString('$this->uniqueness?->validate($entity)', $extension);

        foreach ([$menuController, $itemController] as $controller) {
            self::assertStringContainsString('UniqueConstraintViolationException', $controller);
            self::assertStringContainsString('public function new(AdminContext $context)', $controller);
            self::assertStringContainsString('catch (UniqueConstraintViolationException)', $controller);
        }
    }

    public function testDuplicateActionUsesCollisionResistantIdentityAndDatabaseFallback(): void
    {
        $controller = self::read('src/Controllers/Admin/NavigationItemCrudController.php');
        $duplicateStart = strpos($controller, 'public function duplicateItem(');
        self::assertIsInt($duplicateStart);
        $duplicateEnd = strpos($controller, 'private function resolveNavigationItem(', $duplicateStart);
        self::assertIsInt($duplicateEnd);
        $duplicate = substr($controller, $duplicateStart, $duplicateEnd - $duplicateStart);

        self::assertStringContainsString("\$token = date('YmdHis').'-'.bin2hex(random_bytes(5));", $duplicate);
        self::assertStringContainsString("'.copy.'.\$token", $duplicate);
        self::assertStringContainsString("'-copy-'.\$token", $duplicate);
        self::assertStringContainsString('catch (UniqueConstraintViolationException)', $duplicate);
        self::assertStringNotContainsString("\$timestamp = date('YmdHis');", $duplicate);
    }

    private static function read(string $relativePath): string
    {
        $contents = file_get_contents(dirname(__DIR__).'/'.$relativePath);
        self::assertIsString($contents);

        return $contents;
    }
}
