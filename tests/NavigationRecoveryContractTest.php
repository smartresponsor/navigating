<?php

declare(strict_types=1);

namespace App\Navigating\Tests;

use PHPUnit\Framework\TestCase;

final class NavigationRecoveryContractTest extends TestCase
{
    public function testRecoverySurfaceExists(): void
    {
        foreach ([
            'src/DataFixtures/NavigationFixture.php',
            'src/Service/Navigation/Import/NavigationConfigImportService.php',
            'src/Service/Navigation/Snapshot/NavigationSnapshotService.php',
            'src/Service/Navigation/Snapshot/NavigationAutoBackupService.php',
            'src/EventSubscriber/NavigationAutoBackupSubscriber.php',
            'src/Command/NavigationBackupCreateCommand.php',
            'src/Command/NavigationBackupRestoreCommand.php',
            'src/Command/NavigationManifestWriteCommand.php',
            'src/Command/NavigationManifestVerifyCommand.php',
            'src/Command/NavigationManifestRestoreCommand.php',
            'src/Command/NavigationDatabaseRebuildCommand.php',
            'docs/navigation-recovery.md',
        ] as $path) {
            self::assertFileExists(self::path($path), $path);
        }
    }

    public function testPortableSnapshotIsVersionedAndChecksummed(): void
    {
        $snapshot = self::read('src/Service/Navigation/Snapshot/NavigationSnapshotService.php');

        self::assertStringContainsString("public const FORMAT = 'smartresponsor.navigation'", $snapshot);
        self::assertStringContainsString('public const VERSION = 1', $snapshot);
        self::assertStringContainsString("\$payload['sha256']", $snapshot);
        self::assertStringContainsString('hash_equals', $snapshot);
        self::assertStringContainsString("'archived_items'", $snapshot);
        self::assertStringContainsString("'slug' => \$item->getSlug()", $snapshot);
        self::assertStringContainsString("'slug' => \$menu->getSlug()", $snapshot);
    }

    public function testSafeRebuildIsComponentScoped(): void
    {
        $command = self::read('src/Command/NavigationDatabaseRebuildCommand.php');

        self::assertStringContainsString("name: 'navigation:database:rebuild'", $command);
        self::assertStringContainsString('NavigationMenu::class', $command);
        self::assertStringContainsString('NavigationItem::class', $command);
        self::assertStringContainsString('new SchemaTool', $command);
        self::assertStringContainsString('dropSchema($metadata)', $command);
        self::assertStringContainsString('createSchema($metadata)', $command);
        self::assertStringContainsString('snapshotService->restore($snapshot)', $command);
        self::assertStringNotContainsString('doctrine:schema:drop', $command);
        self::assertStringNotContainsString('--full-database', $command);
    }

    public function testAutomaticBackupKeepsTwoRollingSnapshots(): void
    {
        $service = self::read('src/Service/Navigation/Snapshot/NavigationAutoBackupService.php');
        $subscriber = self::read('src/EventSubscriber/NavigationAutoBackupSubscriber.php');
        $services = self::read('config/services.yaml');

        self::assertStringContainsString('auto-latest.json', $service);
        self::assertStringContainsString('auto-previous.json', $service);
        self::assertStringContainsString("[] === \$groups", $service);
        self::assertStringContainsString('Events::postFlush', $subscriber);
        self::assertStringContainsString('autoBackup->writeLatest()', $subscriber);
        self::assertStringContainsString('Automatic Navigating backup failed after Doctrine flush.', $subscriber);
        self::assertStringContainsString('NavigationAutoBackupSubscriber', $services);
        self::assertStringContainsString('doctrine.event_subscriber', $services);
    }

    public function testComposerExposesRecoveryCommands(): void
    {
        $composer = json_decode(self::read('composer.json'), true, 512, JSON_THROW_ON_ERROR);
        $scripts = $composer['scripts'] ?? [];

        self::assertArrayHasKey('navigation:fixtures', $scripts);
        self::assertArrayHasKey('navigation:backup', $scripts);
        self::assertArrayHasKey('navigation:manifest:write', $scripts);
        self::assertArrayHasKey('navigation:manifest:restore', $scripts);
        self::assertArrayHasKey('navigation:rebuild', $scripts);
        self::assertArrayHasKey('navigation:schema:safe', $scripts);
    }

    private static function read(string $relativePath): string
    {
        $contents = file_get_contents(self::path($relativePath));
        self::assertIsString($contents);

        return $contents;
    }

    private static function path(string $relativePath): string
    {
        return dirname(__DIR__).'/'.$relativePath;
    }
}
