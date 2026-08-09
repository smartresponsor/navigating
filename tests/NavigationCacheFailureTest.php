<?php

declare(strict_types=1);

namespace App\Navigating\Tests;

use App\Navigating\Service\Navigation\Cache\NavigationConfigCacheService;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Contracts\Cache\CacheInterface;

final class NavigationCacheFailureTest extends TestCase
{
    public function testCacheFailureFallsBackToLoader(): void
    {
        $cache = new class implements CacheInterface {
            public function get(string $key, callable $callback, ?float $beta = null, ?array &$metadata = null): mixed
            {
                throw new \RuntimeException('cache unavailable');
            }

            public function delete(string $key): bool
            {
                throw new \RuntimeException('cache unavailable');
            }
        };

        $service = new NavigationConfigCacheService($cache, new NullLogger());
        $loads = 0;

        self::assertSame(
            ['shell_groups' => ['main' => ['items' => []]]],
            $service->remember(static function () use (&$loads): array {
                ++$loads;

                return ['shell_groups' => ['main' => ['items' => []]]];
            }),
        );
        self::assertSame(1, $loads);

        $service->invalidate();
    }

    public function testCacheContractComputesValueThroughCallback(): void
    {
        $cache = new class implements CacheInterface {
            public function get(string $key, callable $callback, ?float $beta = null, ?array &$metadata = null): mixed
            {
                return $callback();
            }

            public function delete(string $key): bool
            {
                return true;
            }
        };

        $service = new NavigationConfigCacheService($cache, new NullLogger());

        self::assertSame(['ok' => true], $service->remember(static fn (): array => ['ok' => true]));
    }
}
