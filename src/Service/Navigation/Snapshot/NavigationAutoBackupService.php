<?php

declare(strict_types=1);

namespace App\Navigating\Service\Navigation\Snapshot;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class NavigationAutoBackupService
{
    public function __construct(
        private NavigationSnapshotExportService $snapshotExportService,
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
        $temporary = $directory.'/.auto-latest.'.bin2hex(random_bytes(6)).'.tmp';

        $json = json_encode(
            $snapshot,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        ).PHP_EOL;

        if (false === file_put_contents($temporary, $json, LOCK_EX)) {
            throw new \RuntimeException('Unable to write temporary automatic navigation backup.');
        }

        try {
            if (is_file($latest) && !copy($latest, $previous)) {
                throw new \RuntimeException('Unable to rotate previous automatic navigation backup.');
            }

            if (!rename($temporary, $latest)) {
                throw new \RuntimeException('Unable to promote automatic navigation backup.');
            }
        } finally {
            if (is_file($temporary)) {
                @unlink($temporary);
            }
        }
    }
}
