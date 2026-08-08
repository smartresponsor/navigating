<?php

declare(strict_types=1);

namespace App\Navigating\Service\Navigation\Persistence;

use App\Navigating\Service\Navigation\Cache\NavigationConfigCacheService;
use App\Navigating\Service\Navigation\Snapshot\NavigationAutoBackupService;

final readonly class NavigationPersistenceFinalizeService
{
    public function __construct(
        private NavigationConfigCacheService $cache,
        private NavigationAutoBackupService $autoBackup,
    ) {
    }

    public function finalizeCommittedChange(): void
    {
        $this->cache->invalidate();
        $this->autoBackup->writeLatest();
    }
}
