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
            'src/Command/NavigationDatabaseUpdateCommand.php',
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

    public function testAdditiveSchemaUpdateIsComponentScopedAndNonDestructive(): void
    {
        $command = self::read('src/Command/NavigationDatabaseUpdateCommand.php');
        $composer = self::read('composer.json');
        $makefile = self::read('Makefile');
        $workflow = self::read('.github/workflows/sqlite-recovery.yml');

        self::assertStringContainsString("name: 'navigation:database:update'", $command);
        self::assertStringContainsString('NavigationMenu::class', $command);
        self::assertStringContainsString('NavigationItem::class', $command);
        self::assertStringContainsString('getUpdateSchemaSql($metadata, true)', $command);
        self::assertStringContainsString('updateSchema($metadata, true)', $command);
        self::assertStringNotContainsString('dropSchema(', $command);
        self::assertStringNotContainsString('doctrine:schema:update --force', $composer);
        self::assertStringNotContainsString('doctrine:schema:update --force', $makefile);
        self::assertStringNotContainsString('doctrine:schema:update --force', $workflow);
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

    public function testFixtureLoadingCannotPurgeUnrelatedHostTables(): void
    {
        $fixture = self::read('src/DataFixtures/NavigationFixture.php');
        $composer = json_decode(self::read('composer.json'), true, 512, JSON_THROW_ON_ERROR);
        $script = $composer['scripts']['navigation:fixtures'] ?? null;

        self::assertStringContainsString('FixtureGroupInterface', $fixture);
        self::assertStringContainsString("return ['navigating'];", $fixture);
        self::assertSame(
            '@php bin/console doctrine:fixtures:load --group=navigating --append --no-interaction',
            $script,
        );
        self::assertStringContainsString('replaceFromConfig', $fixture);
    }

    public function testComposerExposesRecoveryCommands(): void
    {
        $composer = json_decode(self::read('composer.json'), true, 512, JSON_THROW_ON_ERROR);
        $scripts = $composer['scripts'] ?? [];

        self::assertArrayHasKey('navigation:fixtures', $scripts);
        self::assertArrayHasKey('navigation:backup', $scripts);
        self::assertArrayHasKey('navigation:manifest:write', $scripts);
        self::assertArrayHasKey('navigation:manifest:restore', $scripts);
        self::assertArrayHasKey('navigation:schema:update', $scripts);
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
