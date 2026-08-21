<?php

declare(strict_types=1);

namespace App\Navigating\Service\Navigation\Cache;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;

final readonly class NavigationConfigCacheService
{
    private const string CACHE_KEY = 'navigating.navigation.database_config.v3';

    public function __construct(
        private CacheInterface $cache,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @param \Closure(): array<string, mixed> $loader
     *
     * @return array<string, mixed>
     */
    public function remember(\Closure $loader): array
    {
        try {
            $value = $this->cache->get(self::CACHE_KEY, static fn (): array => $loader());

            if (is_array($value)) {
                return $value;
            }

            $this->logger->warning('Navigation cache returned a non-array payload; falling back to the database loader.');
        } catch (\Throwable $exception) {
            $this->logger->warning('Navigation cache read/write failed; falling back to the database loader.', [
                'exception' => $exception,
            ]);
        }

        return $loader();
    }

    public function invalidate(): void
    {
        try {
            $this->cache->delete(self::CACHE_KEY);
        } catch (\Throwable $exception) {
            $this->logger->warning('Navigation cache invalidation failed; database-backed navigation remains authoritative.', [
                'exception' => $exception,
            ]);
        }
    }
}
