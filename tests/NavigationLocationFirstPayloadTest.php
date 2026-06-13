<?php

declare(strict_types=1);

namespace App\Navigating\Tests;

use App\Navigating\Model\Navigation\View\NavigationGroupView;
use App\Navigating\Model\Navigation\View\NavigationItemView;
use App\Navigating\Model\Navigation\View\NavigationShellView;
use App\Navigating\Model\Navigation\View\NavigationTargetView;
use PHPUnit\Framework\TestCase;

final class NavigationLocationFirstPayloadTest extends TestCase
{
    public function testItemAndTargetExposeHrefAndUrlForNeutralLocationRendering(): void
    {
        $target = new NavigationTargetView('route', '/vendor/index', 'vendor.index');
        $item = new NavigationItemView('vendor.index', 'navigation.link', 'Vendors', $target, true);

        self::assertSame('/vendor/index', $target->toArray()['href']);
        self::assertSame('/vendor/index', $target->toArray()['url']);
        self::assertSame('/vendor/index', $item->toArray()['href']);
        self::assertSame('/vendor/index', $item->toArray()['url']);
    }

    public function testShellLocationsArePlainLocationProjection(): void
    {
        $target = new NavigationTargetView('route', '/vendor/index', 'vendor.index');
        $item = new NavigationItemView('vendor.index', 'navigation.link', 'Vendors', $target, true);
        $group = new NavigationGroupView('shell.left.middle', 'Business', [$item]);
        $shell = new NavigationShellView(['shell.left.middle' => $group]);

        $locations = $shell->toLocationsArray();

        self::assertArrayHasKey('shell.left.middle', $locations);
        self::assertSame('Vendors', $locations['shell.left.middle'][0]['label']);
    }
}
