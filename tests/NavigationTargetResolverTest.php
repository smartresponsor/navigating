<?php

declare(strict_types=1);

namespace App\Navigating\Tests;

use App\Navigating\Service\Navigation\Resolve\NavigationTargetResolveService;
use App\Navigating\Value\Navigation\NavigationTarget;
use PHPUnit\Framework\TestCase;

final class NavigationTargetResolveServiceTest extends TestCase
{
    public function testUnknownTargetFallsBackToRoot(): void
    {
        $resolver = new NavigationTargetResolveService();

        $url = $resolver->resolveUrl(new NavigationTarget(
            type: 'unknown',
        ));

        self::assertSame('', $url);
    }

    public function testPathTargetNormalizesLeadingSlash(): void
    {
        $resolver = new NavigationTargetResolveService();

        $url = $resolver->resolveUrl(new NavigationTarget(
            type: 'path',
            path: 'payment/methods',
        ));

        self::assertSame('/payment/methods', $url);
    }

    public function testFromArrayAcceptsParametersAlias(): void
    {
        $target = NavigationTarget::fromArray([
            'type' => 'route',
            'route' => 'navigation.archive_id',
            'parameters' => [
                'id' => 123,
            ],
        ]);

        self::assertSame(['id' => 123], $target->params);
    }
}
