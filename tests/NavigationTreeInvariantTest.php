<?php

declare(strict_types=1);

namespace App\Navigating\Tests;

use App\Navigating\Entity\NavigationItem;
use App\Navigating\Entity\NavigationMenu;
use PHPUnit\Framework\TestCase;

final class NavigationTreeInvariantTest extends TestCase
{
    public function testSelfParentIsRejected(): void
    {
        $item = new NavigationItem();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('cannot be its own parent');

        $item->setParent($item);
    }

    public function testCrossMenuParentIsRejected(): void
    {
        $menuA = (new NavigationMenu())->setMenuKey('a')->setSlug('a');
        $menuB = (new NavigationMenu())->setMenuKey('b')->setSlug('b');
        $item = (new NavigationItem())->setMenu($menuA);
        $parent = (new NavigationItem())->setMenu($menuB);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('same menu');

        $item->setParent($parent);
    }

    public function testLongHierarchyCycleIsRejected(): void
    {
        $menu = (new NavigationMenu())->setMenuKey('main')->setSlug('main');
        $a = (new NavigationItem())->setMenu($menu)->setNavigationKey('a');
        $b = (new NavigationItem())->setMenu($menu)->setNavigationKey('b');
        $c = (new NavigationItem())->setMenu($menu)->setNavigationKey('c');

        $b->setParent($a);
        $c->setParent($b);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('cannot contain cycles');

        $a->setParent($c);
    }

    public function testDeletingParentDoesNotCascadeDeleteSubtreeByMappingContract(): void
    {
        $entity = file_get_contents(dirname(__DIR__).'/src/Entity/NavigationItem.php');
        self::assertIsString($entity);

        self::assertStringContainsString("JoinColumn(name: 'parent_id', nullable: true, onDelete: 'SET NULL')", $entity);
        self::assertStringContainsString("JoinColumn(name: 'menu_id', nullable: false, onDelete: 'CASCADE')", $entity);
    }
}
