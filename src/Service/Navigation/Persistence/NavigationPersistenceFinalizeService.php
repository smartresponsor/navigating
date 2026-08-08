<?php

declare(strict_types=1);

namespace App\Navigating\Service\Navigation\Persistence;

use App\Navigating\Service\Navigation\Cache\NavigationConfigCacheService;
use App\Navigating\Service\Navigation\Snapshot\NavigationAutoBackupService;
use Psr\Log\LoggerInterface;

final readonly class NavigationPersistenceFinalizeService
{
    public function __construct(
        private NavigationConfigCacheService $cache,
        private NavigationAutoBackupService $autoBackup,
        private LoggerInterface $logger,
    ) {
    }

    public function finalizeCommittedChange(): void
    {
        try {
            $this->cache->invalidate();
        } catch (\Throwable $exception) {
            $this->logger->error('Navigation cache invalidation failed after committed persistence change.', [
                'exception' => $exception,
            ]);
        }

        try {
            $this->autoBackup->writeLatest();
        } catch (\Throwable $exception) {
            $this->logger->error('Navigation rolling backup failed after committed persistence change.', [
                'exception' => $exception,
            ]);
        }
    }
}
