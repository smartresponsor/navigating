<?php

declare(strict_types=1);

namespace App\Navigating\Service\Navigation\Persistence;

use App\Navigating\Service\Navigation\Cache\NavigationConfigCacheService;
use App\Navigating\Service\Navigation\Snapshot\NavigationAutoBackupService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final readonly class NavigationPersistenceFinalizeService
{
    public function __construct(
        private NavigationConfigCacheService $cache,
        private NavigationAutoBackupService $autoBackup,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {
    }

    public function finalizeCommittedChange(): void
    {
        if ($this->entityManager->getConnection()->getTransactionNestingLevel() > 0) {
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
