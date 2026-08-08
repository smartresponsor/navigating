<?php

declare(strict_types=1);

namespace App\Navigating\Tests;

use App\Navigating\Entity\NavigationItem;
use App\Navigating\Entity\NavigationMenu;
use App\Navigating\EventSubscriber\NavigationEntityInvariantSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PrePersistEventArgs;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class NavigationEntityInvariantTest extends TestCase
{
    #[DataProvider('invalidMenuProvider')]
    public function testRequiredCanonicalAndLengthMenuFieldsAreRejected(NavigationMenu $menu, string $message): void
    {
        $subscriber = $this->subscriber();
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage($message);

        $subscriber->prePersist(new PrePersistEventArgs($menu, $entityManager));
    }

    public static function invalidMenuProvider(): iterable
    {
        yield 'menu key empty' => [(new NavigationMenu())->setMenuKey('   ')->setSlug('main')->setLabel('Main')->setLocation('shell.left.middle')->setType('navigation'), 'Navigation menu key cannot be empty.'];
        yield 'menu key format' => [(new NavigationMenu())->setMenuKey('Main Menu')->setSlug('main')->setLabel('Main')->setLocation('shell.left.middle')->setType('navigation'), 'Navigation menu key must be a lowercase navigation token'];
        yield 'menu key length' => [(new NavigationMenu())->setMenuKey(str_repeat('a', 161))->setSlug('main')->setLabel('Main')->setLocation('shell.left.middle')->setType('navigation'), 'Navigation menu key cannot exceed 160 characters.'];
        yield 'menu slug empty' => [(new NavigationMenu())->setMenuKey('main')->setSlug('   ')->setLabel('Main')->setLocation('shell.left.middle')->setType('navigation'), 'Navigation menu slug cannot be empty.'];
        yield 'menu slug format' => [(new NavigationMenu())->setMenuKey('main')->setSlug('Main_Menu')->setLabel('Main')->setLocation('shell.left.middle')->setType('navigation'), 'Navigation menu slug must use lowercase kebab-case.'];
        yield 'menu slug length' => [(new NavigationMenu())->setMenuKey('main')->setSlug(str_repeat('a', 181))->setLabel('Main')->setLocation('shell.left.middle')->setType('navigation'), 'Navigation menu slug cannot exceed 180 characters.'];
        yield 'menu label empty' => [(new NavigationMenu())->setMenuKey('main')->setSlug('main')->setLabel('   ')->setLocation('shell.left.middle')->setType('navigation'), 'Navigation menu label cannot be empty.'];
        yield 'menu label length' => [(new NavigationMenu())->setMenuKey('main')->setSlug('main')->setLabel(str_repeat('L', 141))->setLocation('shell.left.middle')->setType('navigation'), 'Navigation menu label cannot exceed 140 characters.'];
        yield 'menu location empty' => [(new NavigationMenu())->setMenuKey('main')->setSlug('main')->setLabel('Main')->setLocation('   ')->setType('navigation'), 'Navigation menu location cannot be empty.'];
        yield 'menu location unknown' => [(new NavigationMenu())->setMenuKey('main')->setSlug('main')->setLabel('Main')->setLocation('shell.unknown')->setType('navigation'), 'is not registered in shell_locations.'];
        yield 'menu type empty' => [(new NavigationMenu())->setMenuKey('main')->setSlug('main')->setLabel('Main')->setLocation('shell.left.middle')->setType('   '), 'Navigation menu type cannot be empty.'];
        yield 'menu type length' => [(new NavigationMenu())->setMenuKey('main')->setSlug('main')->setLabel('Main')->setLocation('shell.left.middle')->setType('a'.str_repeat('b', 60)), 'Navigation menu type cannot exceed 60 characters.'];
    }

    #[DataProvider('invalidItemProvider')]
    public function testRequiredCanonicalAndLengthItemFieldsAreRejected(NavigationItem $item, string $message): void
    {
        $subscriber = $this->subscriber();
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage($message);

        $subscriber->prePersist(new PrePersistEventArgs($item, $entityManager));
    }

    public static function invalidItemProvider(): iterable
    {
        $menu = (new NavigationMenu())->setMenuKey('main')->setSlug('main')->setLabel('Main')->setLocation('shell.left.middle')->setType('navigation');

        yield 'missing menu' => [(new NavigationItem())->setNavigationKey('home')->setLabel('Home')->setType('link')->setOperation('index'), 'Navigation item must belong to a navigation menu.'];
        yield 'item key empty' => [(new NavigationItem())->setMenu($menu)->setNavigationKey('   ')->setLabel('Home')->setType('link')->setOperation('index'), 'Navigation item key cannot be empty.'];
        yield 'item key format' => [(new NavigationItem())->setMenu($menu)->setNavigationKey('Home Item')->setLabel('Home')->setType('link')->setOperation('index'), 'Navigation item key must be a lowercase navigation token'];
        yield 'item key length' => [(new NavigationItem())->setMenu($menu)->setNavigationKey(str_repeat('a', 161))->setLabel('Home')->setType('link')->setOperation('index'), 'Navigation item key cannot exceed 160 characters.'];
        yield 'item slug format' => [(new NavigationItem())->setMenu($menu)->setNavigationKey('home')->setSlug('Home_Item')->setLabel('Home')->setType('link')->setOperation('index'), 'Navigation item slug must use lowercase kebab-case.'];
        yield 'item slug length' => [(new NavigationItem())->setMenu($menu)->setNavigationKey('home')->setSlug(str_repeat('a', 181))->setLabel('Home')->setType('link')->setOperation('index'), 'Navigation item slug cannot exceed 180 characters.'];
        yield 'item label empty' => [(new NavigationItem())->setMenu($menu)->setNavigationKey('home')->setLabel('   ')->setType('link')->setOperation('index'), 'Navigation item label cannot be empty.'];
        yield 'item label length' => [(new NavigationItem())->setMenu($menu)->setNavigationKey('home')->setLabel(str_repeat('L', 141))->setType('link')->setOperation('index'), 'Navigation item label cannot exceed 140 characters.'];
        yield 'item type empty' => [(new NavigationItem())->setMenu($menu)->setNavigationKey('home')->setLabel('Home')->setType('   ')->setOperation('index'), 'Navigation item type cannot be empty.'];
        yield 'item type length' => [(new NavigationItem())->setMenu($menu)->setNavigationKey('home')->setLabel('Home')->setType('a'.str_repeat('b', 40))->setOperation('index'), 'Navigation item type cannot exceed 40 characters.'];
        yield 'item operation empty' => [(new NavigationItem())->setMenu($menu)->setNavigationKey('home')->setLabel('Home')->setType('link')->setOperation('   '), 'Navigation item operation cannot be empty.'];
        yield 'item operation format' => [(new NavigationItem())->setMenu($menu)->setNavigationKey('home')->setLabel('Home')->setType('link')->setOperation('Sign In'), 'Navigation item operation must use lowercase snake_case.'];
        yield 'item operation length' => [(new NavigationItem())->setMenu($menu)->setNavigationKey('home')->setLabel('Home')->setType('link')->setOperation('a'.str_repeat('_b', 30)), 'Navigation item operation cannot exceed 60 characters.'];
        yield 'route length' => [(new NavigationItem())->setMenu($menu)->setNavigationKey('home')->setLabel('Home')->setType('link')->setOperation('index')->setRouteName(str_repeat('r', 181)), 'Navigation item route name cannot exceed 180 characters.'];
        yield 'path length' => [(new NavigationItem())->setMenu($menu)->setNavigationKey('home')->setLabel('Home')->setType('link')->setOperation('index')->setPath('/'.str_repeat('p', 512)), 'Navigation item path cannot exceed 512 characters.'];
        yield 'icon length' => [(new NavigationItem())->setMenu($menu)->setNavigationKey('home')->setLabel('Home')->setType('link')->setOperation('index')->setIcon(str_repeat('i', 81)), 'Navigation item icon cannot exceed 80 characters.'];
        yield 'badge length' => [(new NavigationItem())->setMenu($menu)->setNavigationKey('home')->setLabel('Home')->setType('link')->setOperation('index')->setBadge(str_repeat('b', 81)), 'Navigation item badge cannot exceed 80 characters.'];
    }

    public function testExistingNavigationVocabularyIsAccepted(): void
    {
        $menu = (new NavigationMenu())
            ->setMenuKey('right_toolbar_quick')
            ->setSlug('right-toolbar-quick')
            ->setLabel('Quick')
            ->setLocation('shell.header.right.quick.menu')
            ->setType('navigation');
        $item = (new NavigationItem())
            ->setMenu($menu)
            ->setNavigationKey('access_signin')
            ->setSlug('access-signin')
            ->setLabel('Sign in')
            ->setType('link')
            ->setOperation('sign_in');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $subscriber = $this->subscriber();

        $subscriber->prePersist(new PrePersistEventArgs($menu, $entityManager));
        $subscriber->prePersist(new PrePersistEventArgs($item, $entityManager));

        self::addToAssertionCount(1);
    }

    public function testUtf8LabelsUseCharacterLengthInsteadOfByteLength(): void
    {
        $menu = (new NavigationMenu())
            ->setMenuKey('main')
            ->setSlug('main')
            ->setLabel(str_repeat('Меню', 35))
            ->setLocation('shell.left.middle')
            ->setType('navigation');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $this->subscriber()->prePersist(new PrePersistEventArgs($menu, $entityManager));

        self::addToAssertionCount(1);
    }

    private function subscriber(): NavigationEntityInvariantSubscriber
    {
        return new NavigationEntityInvariantSubscriber([
            'shell_locations' => [
                'shell.left.middle' => [],
                'shell.header.right.quick.menu' => [],
            ],
        ]);
    }
}
