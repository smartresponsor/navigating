<?php

declare(strict_types=1);

namespace App\Navigating\Service\Navigation\Snapshot;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class NavigationAutoBackupService
{
    public function __construct(
        private NavigationSnapshotExportService $snapshotExportService,
        private NavigationSnapshotFileService $snapshotFileService,
        #[Autowire('%kernel.project_dir%')] private string $projectDir,
    ) {
    }

    public function writeLatest(): void
    {
        $snapshot = $this->snapshotExportService->create();
        $groups = $snapshot['shell_groups'] ?? null;
        if (!is_array($groups) || [] === $groups) {
            return;
        }

        $directory = $this->projectDir.'/var/backup/navigating';
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException('Unable to create automatic navigation backup directory.');
        }

        $latest = $directory.'/auto-latest.json';
        $previous = $directory.'/auto-previous.json';

        if (is_file($latest) && !copy($latest, $previous)) {
            throw new \RuntimeException('Unable to rotate previous automatic navigation backup.');
        }

        $this->snapshotFileService->write($latest, $snapshot);
    }
}
