<?php

declare(strict_types=1);

namespace App\Navigating\Tests;

use App\Navigating\Entity\NavigationItem;
use App\Navigating\EventSubscriber\NavigationItemTargetInvariantSubscriber;
use PHPUnit\Framework\TestCase;

final class NavigationTargetInvariantTest extends TestCase
{
    public function testRouteAndPathCannotCoexist(): void
    {
        $item = (new NavigationItem())
            ->setRouteName('vendor_index')
            ->setPath('/vendor');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('either a route target or a path target');

        $this->validate($item);
    }

    public function testRouteParametersRequireRoute(): void
    {
        $item = (new NavigationItem())
            ->setRouteParameters(['slug' => 'vendor']);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('route parameters require a route target');

        $this->validate($item);
    }

    public function testRouteTargetWithParametersIsValid(): void
    {
        $item = (new NavigationItem())
            ->setRouteName('vendor_show')
            ->setRouteParameters(['slug' => 'vendor']);

        $this->validate($item);
        self::addToAssertionCount(1);
    }

    public function testPathTargetWithoutRouteParametersIsValid(): void
    {
        $item = (new NavigationItem())
            ->setPath('/vendor');

        $this->validate($item);
        self::addToAssertionCount(1);
    }

    private function validate(NavigationItem $item): void
    {
        $subscriber = new NavigationItemTargetInvariantSubscriber();
        $method = new \ReflectionMethod($subscriber, 'validate');
        $method->invoke($subscriber, $item);
    }
}
