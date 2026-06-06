<?php

declare(strict_types=1);

namespace App\Navigating\Tests;

use App\Navigating\Service\Navigation\NavigationTargetResolver;
use App\Navigating\Value\Navigation\NavigationTarget;
use PHPUnit\Framework\TestCase;

final class NavigationTargetResolverTest extends TestCase
{
    public function testUnknownTargetFallsBackToRoot(): void
    {
        $resolver = new NavigationTargetResolver();

        $url = $resolver->resolveUrl(new NavigationTarget(
            type: 'unknown',
        ));

        self::assertSame('/', $url);
    }

    public function testPathTargetNormalizesLeadingSlash(): void
    {
        $resolver = new NavigationTargetResolver();

        $url = $resolver->resolveUrl(new NavigationTarget(
            type: 'path',
            path: 'payment/methods',
        ));

        self::assertSame('/payment/methods', $url);
    }
}
