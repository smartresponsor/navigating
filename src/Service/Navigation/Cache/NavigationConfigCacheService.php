<?php

declare(strict_types=1);

namespace App\Navigating\Service\Navigation\Cache;

use Psr\Cache\CacheItemPoolInterface;

final readonly class NavigationConfigCacheService
{
    private const string CACHE_KEY = 'navigating.navigation.database_config.v2';

    public function __construct(
        private CacheItemPoolInterface $cache,
    ) {
    }

    /**
     * @param \Closure(): array<string, mixed> $loader
     *
     * @return array<string, mixed>
     */
    public function remember(\Closure $loader): array
    {
        $item = $this->cache->getItem(self::CACHE_KEY);
        if ($item->isHit()) {
            $value = $item->get();
            if (is_array($value)) {
                return $value;
            }
        }

        $value = $loader();
        $item->set($value);
        $this->cache->save($item);

        return $value;
    }

    public function invalidate(): void
    {
        $this->cache->deleteItem(self::CACHE_KEY);
    }
}
