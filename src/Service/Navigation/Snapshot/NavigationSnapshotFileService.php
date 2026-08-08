<?php

declare(strict_types=1);

namespace App\Navigating\Service\Navigation\Snapshot;

final readonly class NavigationSnapshotFileService
{
    /** @param array<string, mixed> $payload */
    public function write(string $path, array $payload): void
    {
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException('Unable to create navigation snapshot directory: '.$directory);
        }

        $json = json_encode(
            $payload,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        ).PHP_EOL;

        $temporary = $directory.DIRECTORY_SEPARATOR.'.'.basename($path).'.'.bin2hex(random_bytes(6)).'.tmp';
        $rollback = $directory.DIRECTORY_SEPARATOR.'.'.basename($path).'.'.bin2hex(random_bytes(6)).'.rollback';
        $handle = fopen($temporary, 'xb');
        if (false === $handle) {
            throw new \RuntimeException('Unable to create temporary navigation snapshot: '.$temporary);
        }

        try {
            if (!flock($handle, LOCK_EX)) {
                throw new \RuntimeException('Unable to lock temporary navigation snapshot.');
            }

            $written = 0;
            $length = strlen($json);
            while ($written < $length) {
                $chunk = fwrite($handle, substr($json, $written));
                if (false === $chunk || 0 === $chunk) {
                    throw new \RuntimeException('Unable to write complete navigation snapshot.');
                }
                $written += $chunk;
            }

            if (!fflush($handle)) {
                throw new \RuntimeException('Unable to flush navigation snapshot to disk.');
            }
            if (function_exists('fsync') && !fsync($handle)) {
                throw new \RuntimeException('Unable to synchronize navigation snapshot to disk.');
            }
            flock($handle, LOCK_UN);
            fclose($handle);
            $handle = null;

            $hadExisting = is_file($path);
            if ($hadExisting && !rename($path, $rollback)) {
                throw new \RuntimeException('Unable to preserve the existing navigation snapshot before replacement.');
            }

            try {
                if (!rename($temporary, $path)) {
                    throw new \RuntimeException('Unable to promote temporary navigation snapshot.');
                }
            } catch (\Throwable $exception) {
                if ($hadExisting && is_file($rollback) && !is_file($path)) {
                    @rename($rollback, $path);
                }
                throw $exception;
            }

            if (is_file($rollback)) {
                @unlink($rollback);
            }
        } finally {
            if (is_resource($handle)) {
                @flock($handle, LOCK_UN);
                @fclose($handle);
            }
            if (is_file($temporary)) {
                @unlink($temporary);
            }
        }
    }
}
