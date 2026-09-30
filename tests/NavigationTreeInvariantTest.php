<?php

declare(strict_types=1);

namespace App\Navigating\Tests;

use App\Navigating\Entity\NavigationItemEntity;
use App\Navigating\Entity\NavigationMenuEntity;
use App\Navigating\EventSubscriber\NavigationEntityInvariantSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PrePersistEventArgs;
use PHPUnit\Framework\TestCase;

final class NavigationTreeInvariantTest extends TestCase
{
    public function testSelfParentIsRejected(): void
    {
        $item = new NavigationItemEntity();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('cannot be its own parent');

        $item->setParent($item);
    }

    public function testCrossMenuParentIsRejectedAtPersistenceBoundary(): void
    {
        $menuA = (new NavigationMenuEntity())->setMenuKey('a')->setSlug('a');
        $menuB = (new NavigationMenuEntity())->setMenuKey('b')->setSlug('b');
        $item = (new NavigationItemEntity())
            ->setMenu($menuA)
            ->setNavigationKey('item')
            ->setLabel('Item')
            ->setType('link')
            ->setOperation('index');
        $parent = (new NavigationItemEntity())->setMenu($menuB);

        // Cross-field setters must allow a form to transition menu and parent in one submit.
        $item->setParent($parent);
        self::assertSame($parent, $item->getParent());

        $subscriber = new NavigationEntityInvariantSubscriber([
            'shell_locations' => ['shell.left.middle' => []],
        ]);
        $entityManager = $this->createStub(EntityManagerInterface::class);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('same menu');

        $subscriber->prePersist(new PrePersistEventArgs($item, $entityManager));
    }

    public function testMenuAndParentCanTransitionTogetherWithoutSetterOrderDependency(): void
    {
        $menuA = (new NavigationMenuEntity())->setMenuKey('a')->setSlug('a');
        $menuB = (new NavigationMenuEntity())->setMenuKey('b')->setSlug('b');
        $oldParent = (new NavigationItemEntity())->setMenu($menuA);
        $newParent = (new NavigationItemEntity())->setMenu($menuB);
        $item = (new NavigationItemEntity())->setMenu($menuA)->setParent($oldParent);

        $item->setMenu($menuB);
        $item->setParent($newParent);

        self::assertSame($menuB, $item->getMenu());
        self::assertSame($newParent, $item->getParent());
    }

    public function testLongHierarchyCycleIsRejected(): void
    {
        $menu = (new NavigationMenuEntity())->setMenuKey('main')->setSlug('main');
        $a = (new NavigationItemEntity())->setMenu($menu)->setNavigationKey('a');
        $b = (new NavigationItemEntity())->setMenu($menu)->setNavigationKey('b');
        $c = (new NavigationItemEntity())->setMenu($menu)->setNavigationKey('c');

        $b->setParent($a);
        $c->setParent($b);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('cannot contain cycles');

        $a->setParent($c);
    }

    public function testDeletingParentDoesNotCascadeDeleteSubtreeByMappingContract(): void
    {
        $entity = file_get_contents(dirname(__DIR__).'/src/Entity/NavigationItemEntity.php');
        self::assertIsString($entity);

        self::assertStringContainsString("JoinColumn(name: 'parent_id', nullable: true, onDelete: 'SET NULL')", $entity);
        self::assertStringContainsString("JoinColumn(name: 'menu_id', nullable: false, onDelete: 'CASCADE')", $entity);
        self::assertStringContainsString("#[ORM\\OneToMany(mappedBy: 'parent', targetEntity: self::class)]", $entity);
        self::assertStringNotContainsString("mappedBy: 'parent', targetEntity: self::class, cascade:", $entity);
        self::assertStringNotContainsString("mappedBy: 'parent', targetEntity: self::class, orphanRemoval:", $entity);
    }

    public function testMenuOrphanRemovalDoesNotCreateAnIllegalNullableMenuState(): void
    {
        $menu = file_get_contents(dirname(__DIR__).'/src/Entity/NavigationMenuEntity.php');
        self::assertIsString($menu);

        self::assertStringContainsString("mappedBy: 'menu', targetEntity: NavigationItemEntity::class, cascade: ['persist'], orphanRemoval: true", $menu);
        self::assertStringContainsString('public function removeItem(NavigationItemEntity $item): self', $menu);
        self::assertStringContainsString('$this->items->removeElement($item);', $menu);

        $removeMethodStart = strpos($menu, 'public function removeItem(NavigationItemEntity $item): self');
        self::assertIsInt($removeMethodStart);
        $removeMethodEnd = strpos($menu, '#[ORM\\PreUpdate]', $removeMethodStart);
        self::assertIsInt($removeMethodEnd);
        $removeMethod = substr($menu, $removeMethodStart, $removeMethodEnd - $removeMethodStart);
        self::assertStringNotContainsString('setMenu(null)', $removeMethod);
    }

    public function testArchiveStateDoesNotDestroyEnabledPreference(): void
    {
        $disabled = (new NavigationItemEntity())->setEnabled(false);
        $disabled->archive();

        self::assertTrue($disabled->isArchived());
        self::assertFalse($disabled->isEnabled());

        $disabled->restore();

        self::assertFalse($disabled->isArchived());
        self::assertFalse($disabled->isEnabled());

        $enabled = (new NavigationItemEntity())->setEnabled(true);
        $enabled->archive()->restore();

        self::assertTrue($enabled->isEnabled());
    }

    public function testRuntimeProjectionRequiresAllAncestorsToBeEnabledAndUnarchived(): void
    {
        $provider = file_get_contents(dirname(__DIR__).'/src/Service/Provide/NavigationDatabaseConfigProvideService.php');
        self::assertIsString($provider);

        self::assertStringContainsString('isEffectivelyEnabled($item)', $provider);
        self::assertStringContainsString('while (null !== $cursor)', $provider);
        self::assertStringContainsString('!$cursor->isEnabled() || $cursor->isArchived()', $provider);
        self::assertStringContainsString("throw new \\LogicException('Navigation item hierarchy contains a cycle.')", $provider);
    }

    public function testRuntimeVisibilityRequiresVisibleAncestorChain(): void
    {
        $filter = file_get_contents(dirname(__DIR__).'/src/Service/Filter/NavigationVisibilityFilterService.php');
        self::assertIsString($filter);

        self::assertStringContainsString('hasVisibleAncestorChain($item, $itemsByKey, $locallyVisibleItems)', $filter);
        self::assertStringContainsString("\$parentKey = \$cursor->metadata['parent_key'] ?? null", $filter);
        self::assertStringContainsString('!isset($locallyVisibleItems[$parentKey])', $filter);
        self::assertStringContainsString('isset($visited[$parentKey])', $filter);
    }
}
