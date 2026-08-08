<?php

declare(strict_types=1);

namespace App\Navigating\Tests;

use App\Navigating\Entity\NavigationItem;
use App\Navigating\Entity\NavigationMenu;
use App\Navigating\EventSubscriber\NavigationEntityInvariantSubscriber;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class NavigationEntityInvariantTest extends TestCase
{
    #[DataProvider('invalidMenuProvider')]
    public function testRequiredMenuFieldsAreRejected(NavigationMenu $menu, string $message): void
    {
        $subscriber = new NavigationEntityInvariantSubscriber();
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage($message);

        $subscriber->prePersist(new PrePersistEventArgs($menu, $entityManager));
    }

    public static function invalidMenuProvider(): iterable
    {
        yield 'menu key' => [
            (new NavigationMenu())->setMenuKey('   ')->setSlug('main')->setLabel('Main')->setLocation('shell.left')->setType('navigation'),
            'Navigation menu key cannot be empty.',
        ];
        yield 'menu slug' => [
            (new NavigationMenu())->setMenuKey('main')->setSlug('   ')->setLabel('Main')->setLocation('shell.left')->setType('navigation'),
            'Navigation menu slug cannot be empty.',
        ];
        yield 'menu label' => [
            (new NavigationMenu())->setMenuKey('main')->setSlug('main')->setLabel('   ')->setLocation('shell.left')->setType('navigation'),
            'Navigation menu label cannot be empty.',
        ];
        yield 'menu location' => [
            (new NavigationMenu())->setMenuKey('main')->setSlug('main')->setLabel('Main')->setLocation('   ')->setType('navigation'),
            'Navigation menu location cannot be empty.',
        ];
        yield 'menu type' => [
            (new NavigationMenu())->setMenuKey('main')->setSlug('main')->setLabel('Main')->setLocation('shell.left')->setType('   '),
            'Navigation menu type cannot be empty.',
        ];
    }

    #[DataProvider('invalidItemProvider')]
    public function testRequiredItemFieldsAreRejected(NavigationItem $item, string $message): void
    {
        $subscriber = new NavigationEntityInvariantSubscriber();
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage($message);

        $subscriber->prePersist(new PrePersistEventArgs($item, $entityManager));
    }

    public static function invalidItemProvider(): iterable
    {
        $menu = (new NavigationMenu())->setMenuKey('main')->setSlug('main')->setLabel('Main')->setLocation('shell.left')->setType('navigation');

        yield 'missing menu' => [
            (new NavigationItem())->setNavigationKey('home')->setLabel('Home')->setType('link')->setOperation('index'),
            'Navigation item must belong to a navigation menu.',
        ];
        yield 'item key' => [
            (new NavigationItem())->setMenu($menu)->setNavigationKey('   ')->setLabel('Home')->setType('link')->setOperation('index'),
            'Navigation item key cannot be empty.',
        ];
        yield 'item label' => [
            (new NavigationItem())->setMenu($menu)->setNavigationKey('home')->setLabel('   ')->setType('link')->setOperation('index'),
            'Navigation item label cannot be empty.',
        ];
        yield 'item type' => [
            (new NavigationItem())->setMenu($menu)->setNavigationKey('home')->setLabel('Home')->setType('   ')->setOperation('index'),
            'Navigation item type cannot be empty.',
        ];
        yield 'item operation' => [
            (new NavigationItem())->setMenu($menu)->setNavigationKey('home')->setLabel('Home')->setType('link')->setOperation('   '),
            'Navigation item operation cannot be empty.',
        ];
    }
}
