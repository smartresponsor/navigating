<?php

declare(strict_types=1);

namespace App\Navigating\Tests;

use PHPUnit\Framework\TestCase;

final class NavigationRepositoryScopeContractTest extends TestCase
{
    public function testItemSlugAndIdOrSlugLookupsAreMenuScoped(): void
    {
        $repository = self::read('src/Repository/NavigationItemRepository.php');

        self::assertStringContainsString('findOneByMenuAndSlug(NavigationMenu $menu, string $slug)', $repository);
        self::assertStringContainsString('findOneByMenuIdOrSlug(NavigationMenu $menu, int|string $identifier)', $repository);
        self::assertStringContainsString("'menu' => \$menu", $repository);
        self::assertStringContainsString('return $this->findOneByMenuAndSlug($menu, $identifier);', $repository);

        self::assertStringNotContainsString('function findOneBySlug(', $repository);
        self::assertStringNotContainsString('function findOneByIdOrSlug(', $repository);
    }

    public function testEnabledRuntimeInventoryFetchesItemsAndParentsInOneQueryGraph(): void
    {
        $repository = self::read('src/Repository/NavigationMenuRepository.php');

        self::assertStringContainsString("->addSelect('item', 'parent')", $repository);
        self::assertStringContainsString("->leftJoin('menu.items', 'item')", $repository);
        self::assertStringContainsString("->leftJoin('item.parent', 'parent')", $repository);
        self::assertStringContainsString("->andWhere('menu.enabled = :enabled')", $repository);
        self::assertStringContainsString("->addOrderBy('item.position', 'ASC')", $repository);
        self::assertStringContainsString("->addOrderBy('item.id', 'ASC')", $repository);

        self::assertStringNotContainsString('item.enabled =', $repository);
        self::assertStringNotContainsString('item.archivedAt IS NULL', $repository);
    }

    private static function read(string $relativePath): string
    {
        $contents = file_get_contents(dirname(__DIR__).'/'.$relativePath);
        self::assertIsString($contents);

        return $contents;
    }
}
