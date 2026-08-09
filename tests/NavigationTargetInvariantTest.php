<?php

declare(strict_types=1);

namespace App\Navigating\Tests;

use App\Navigating\Entity\NavigationItem;
use App\Navigating\Entity\NavigationMenu;
use App\Navigating\Service\Navigation\Persistence\NavigationEntityInvariantService;
use PHPUnit\Framework\TestCase;

final class NavigationTargetInvariantTest extends TestCase
{
    public function testRouteAndPathCannotCoexist(): void
    {
        $item = $this->item()
            ->setRouteName('vendor_index')
            ->setPath('/vendor');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('either a route target or a path target');

        $this->validate($item);
    }

    public function testRouteParametersRequireRoute(): void
    {
        $item = $this->item()
            ->setRouteParameters(['slug' => 'vendor']);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('route parameters require a route target');

        $this->validate($item);
    }

    public function testRouteTargetWithParametersIsValid(): void
    {
        $item = $this->item()
            ->setRouteName('vendor_show')
            ->setRouteParameters(['slug' => 'vendor']);

        $this->validate($item);
        self::addToAssertionCount(1);
    }

    public function testPathTargetWithoutRouteParametersIsValid(): void
    {
        $item = $this->item()->setPath('/vendor');

        $this->validate($item);
        self::addToAssertionCount(1);
    }

    private function item(): NavigationItem
    {
        $menu = (new NavigationMenu())
            ->setMenuKey('main')
            ->setSlug('main')
            ->setLabel('Main')
            ->setLocation('shell.left.middle')
            ->setType('navigation');

        return (new NavigationItem())
            ->setMenu($menu)
            ->setNavigationKey('vendor')
            ->setLabel('Vendor')
            ->setType('link')
            ->setOperation('index');
    }

    private function validate(NavigationItem $item): void
    {
        (new NavigationEntityInvariantService([
            'shell_locations' => ['shell.left.middle' => []],
        ]))->validate($item);
    }
}
