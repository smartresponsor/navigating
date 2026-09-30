<?php

declare(strict_types=1);

namespace App\Navigating\Service\Persistence;

use App\Navigating\Repository\NavigationPersistenceRepository;
use App\Navigating\Service\Cache\NavigationConfigCacheService;
use App\Navigating\Service\Snapshot\NavigationAutoBackupService;
use Psr\Log\LoggerInterface;

final readonly class NavigationPersistenceFinalizeService
{
    public function __construct(
        private NavigationConfigCacheService $cache,
        private NavigationAutoBackupService $autoBackup,
        private NavigationPersistenceRepository $persistenceRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function finalizeCommittedChange(): void
    {
        if ($this->persistenceRepository->hasOpenTransaction()) {
            $this->logger->warning('Navigation persistence finalizer was called before the transaction committed; cache and backup side effects were skipped.');

            return;
        }

        try {
            $this->cache->invalidate();
        } catch (\Throwable $exception) {
            $this->logger->error('Navigation cache invalidation failed after persistence change.', [
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
