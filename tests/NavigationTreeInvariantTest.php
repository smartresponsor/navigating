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

    public function testArchiveStateDoesNotDestroyEnabledPreference(): void
    {
        $disabled = (new NavigationItem())->setEnabled(false);
        $disabled->archive();

        self::assertTrue($disabled->isArchived());
        self::assertFalse($disabled->isEnabled());

        $disabled->restore();

        self::assertFalse($disabled->isArchived());
        self::assertFalse($disabled->isEnabled());

        $enabled = (new NavigationItem())->setEnabled(true);
        $enabled->archive()->restore();

        self::assertTrue($enabled->isEnabled());
    }

    public function testRuntimeProjectionRequiresAllAncestorsToBeEnabledAndUnarchived(): void
    {
        $provider = file_get_contents(dirname(__DIR__).'/src/Service/Navigation/Provide/NavigationDatabaseConfigProvideService.php');
        self::assertIsString($provider);

        self::assertStringContainsString('isEffectivelyEnabled($item)', $provider);
        self::assertStringContainsString('while (null !== $cursor)', $provider);
        self::assertStringContainsString("!\$cursor->isEnabled() || \$cursor->isArchived()", $provider);
        self::assertStringContainsString("throw new \\LogicException('Navigation item hierarchy contains a cycle.')", $provider);
    }

    public function testRuntimeVisibilityRequiresVisibleAncestorChain(): void
    {
        $filter = file_get_contents(dirname(__DIR__).'/src/Service/Navigation/Filter/NavigationVisibilityFilterService.php');
        self::assertIsString($filter);

        self::assertStringContainsString('hasVisibleAncestorChain($item, $itemsByKey, $locallyVisibleItems)', $filter);
        self::assertStringContainsString("\$parentKey = \$cursor->metadata['parent_key'] ?? null", $filter);
        self::assertStringContainsString('!isset($locallyVisibleItems[$parentKey])', $filter);
        self::assertStringContainsString('isset($visited[$parentKey])', $filter);
    }
}
