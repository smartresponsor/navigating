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
        try {
            $this->cache->invalidate();
        } catch (\Throwable $exception) {
            $this->logger->error('Navigation cache invalidation failed after persistence change.', [
                'exception' => $exception,
            ]);
        }

        if ($this->entityManager->getConnection()->getTransactionNestingLevel() > 0) {
            return;
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
