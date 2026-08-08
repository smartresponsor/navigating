<?php

declare(strict_types=1);

namespace App\Navigating\Service\Navigation\Snapshot;

use App\Navigating\Repository\NavigationMenuRepository;
use App\Navigating\Service\Navigation\Import\NavigationConfigImportService;
use App\Navigating\Service\Navigation\Persistence\NavigationPersistenceFinalizeService;
use Doctrine\ORM\EntityManagerInterface;

final readonly class NavigationSnapshotService
{
    public const FORMAT = NavigationSnapshotExportService::FORMAT;
    public const VERSION = NavigationSnapshotExportService::VERSION;

    public function __construct(
        private NavigationSnapshotExportService $exportService,
        private NavigationMenuRepository $menuRepository,
        private NavigationConfigImportService $importService,
        private NavigationPersistenceFinalizeService $finalizer,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /** @return array<string, mixed> */
    public function create(): array
    {
        return $this->exportService->create();
    }

    /** @param array<string, mixed> $snapshot */
    public function restore(array $snapshot): int
    {
        $this->assertValid($snapshot);

        $count = $this->entityManager->wrapInTransaction(function () use ($snapshot): int {
            $count = $this->importService->replaceFromConfig(['shell_groups' => $snapshot['shell_groups']], false, false);
            $archived = is_array($snapshot['archived_items'] ?? null) ? $snapshot['archived_items'] : [];

            if ([] !== $archived) {
                $lookup = array_fill_keys(array_filter($archived, 'is_string'), true);
                foreach ($this->menuRepository->findAll() as $menu) {
                    foreach ($menu->getItems() as $item) {
                        if (isset($lookup[$menu->getMenuKey().':'.$item->getNavigationKey()])) {
                            $item->archive();
                        }
                    }
                }
                $this->entityManager->flush();
            }

            return $count;
        });

        $this->finalizer->finalizeCommittedChange();

        return $count;
    }

    /** @param array<string, mixed> $snapshot */
    public function assertValid(array $snapshot): void
    {
        $this->exportService->assertValid($snapshot);
    }
}
