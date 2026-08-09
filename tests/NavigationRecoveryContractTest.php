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
            'src/Service/Navigation/Persistence/NavigationPersistenceFinalizeService.php',
            'src/Service/Navigation/Snapshot/NavigationSnapshotExportService.php',
            'src/Service/Navigation/Snapshot/NavigationSnapshotService.php',
            'src/Service/Navigation/Snapshot/NavigationSnapshotFileService.php',
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

    public function testPortableSnapshotIsVersionedChecksummedAndCycleFree(): void
    {
        $export = self::read('src/Service/Navigation/Snapshot/NavigationSnapshotExportService.php');
        $snapshot = self::read('src/Service/Navigation/Snapshot/NavigationSnapshotService.php');
        $autoBackup = self::read('src/Service/Navigation/Snapshot/NavigationAutoBackupService.php');

        self::assertStringContainsString("public const FORMAT = 'smartresponsor.navigation'", $export);
        self::assertStringContainsString('public const VERSION = 1', $export);
        self::assertStringContainsString("\$payload['sha256']", $export);
        self::assertStringContainsString('hash_equals', $export);
        self::assertStringContainsString("'archived_items'", $export);
        self::assertStringContainsString("'slug' => \$item->getSlug()", $export);
        self::assertStringContainsString("'slug' => \$menu->getSlug()", $export);
        self::assertStringContainsString('NavigationSnapshotExportService $exportService', $snapshot);
        self::assertStringContainsString('NavigationSnapshotExportService $snapshotExportService', $autoBackup);
        self::assertStringNotContainsString('NavigationSnapshotService $snapshotService', $autoBackup);
    }

    public function testRecoveryArtifactsUseCrashSafeFilePromotion(): void
    {
        $writer = self::read('src/Service/Navigation/Snapshot/NavigationSnapshotFileService.php');
        $backup = self::read('src/Command/NavigationBackupCreateCommand.php');
        $manifest = self::read('src/Command/NavigationManifestWriteCommand.php');
        $legacyPlan = self::read('src/Command/NavigationLegacyMigrationPlanCommand.php');
        $autoBackup = self::read('src/Service/Navigation/Snapshot/NavigationAutoBackupService.php');

        self::assertStringContainsString("fopen(\$temporary, 'xb')", $writer);
        self::assertStringContainsString('fflush($handle)', $writer);
        self::assertStringContainsString("function_exists('fsync')", $writer);
        self::assertStringContainsString('rename($path, $rollback)', $writer);
        self::assertStringContainsString('rename($temporary, $path)', $writer);
        self::assertStringContainsString('@rename($rollback, $path)', $writer);
        self::assertStringContainsString('NavigationSnapshotFileService', $backup);
        self::assertStringContainsString('snapshotFileService->write', $backup);
        self::assertStringContainsString('NavigationSnapshotFileService', $manifest);
        self::assertStringContainsString('snapshotFileService->write', $manifest);
        self::assertStringContainsString('NavigationSnapshotFileService', $legacyPlan);
        self::assertStringContainsString('snapshotFileService->write', $legacyPlan);
        self::assertStringContainsString('NavigationSnapshotFileService', $autoBackup);
        self::assertStringContainsString('snapshotFileService->write', $autoBackup);
    }

    public function testSchemaUpdateIsComponentScopedForDoctrineOrm36(): void
    {
        $command = self::read('src/Command/NavigationDatabaseUpdateCommand.php');
        $composer = self::read('composer.json');
        $makefile = self::read('Makefile');
        $workflow = self::read('.github/workflows/sqlite-recovery.yml');

        self::assertStringContainsString("name: 'navigation:database:update'", $command);
        self::assertStringContainsString("'navigation_menu' => true", $command);
        self::assertStringContainsString("'navigation_item' => true", $command);
        self::assertStringContainsString('getSchemaAssetsFilter()', $command);
        self::assertStringContainsString('setSchemaAssetsFilter(', $command);
        self::assertStringContainsString('getUpdateSchemaSql($metadata)', $command);
        self::assertStringContainsString('updateSchema($metadata)', $command);
        self::assertStringNotContainsString('getUpdateSchemaSql($metadata, true)', $command);
        self::assertStringNotContainsString('updateSchema($metadata, true)', $command);
        self::assertStringContainsString('finally', $command);
        self::assertStringContainsString('setSchemaAssetsFilter($previousFilter)', $command);
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

    public function testAutomaticBackupKeepsTwoRollingCommittedSnapshots(): void
    {
        $service = self::read('src/Service/Navigation/Snapshot/NavigationAutoBackupService.php');
        $subscriber = self::read('src/EventSubscriber/NavigationAutoBackupSubscriber.php');
        $services = self::read('config/services.yaml');

        self::assertStringContainsString('auto-latest.json', $service);
        self::assertStringContainsString('auto-previous.json', $service);
        self::assertStringContainsString("[] === \$groups", $service);
        self::assertStringContainsString('Events::postFlush', $subscriber);
        self::assertStringContainsString('getTransactionNestingLevel() > 0', $subscriber);
        self::assertStringContainsString('autoBackup->writeLatest()', $subscriber);
        self::assertStringContainsString('Automatic Navigating backup failed after Doctrine flush.', $subscriber);
        self::assertStringContainsString('NavigationAutoBackupSubscriber', $services);
        self::assertStringContainsString('doctrine.event_subscriber', $services);
    }

    public function testNavigationCacheUsesNewGenerationAndInvalidatesOnlyOutsideExplicitTransactions(): void
    {
        $cache = self::read('src/Service/Navigation/Cache/NavigationConfigCacheService.php');
        $subscriber = self::read('src/EventSubscriber/NavigationConfigCacheInvalidationSubscriber.php');

        self::assertStringContainsString("CACHE_KEY = 'navigating.navigation.database_config.v2'", $cache);
        self::assertStringNotContainsString('database_config.v1', $cache);
        self::assertStringContainsString('Events::postFlush', $subscriber);
        self::assertStringContainsString('private bool $dirty = false;', $subscriber);
        self::assertStringContainsString('getTransactionNestingLevel() > 0', $subscriber);
        self::assertStringContainsString('$this->dirty = false;', $subscriber);
        self::assertStringContainsString('$this->cache->invalidate();', $subscriber);

        $transactionCheck = strpos($subscriber, 'getTransactionNestingLevel() > 0');
        $cacheInvalidate = strpos($subscriber, '$this->cache->invalidate();');
        self::assertIsInt($transactionCheck);
        self::assertIsInt($cacheInvalidate);
        self::assertLessThan($cacheInvalidate, $transactionCheck, 'Cache invalidation must occur only after the explicit-transaction guard.');
    }

    public function testBulkPersistenceFinalizesOnlyAfterCommit(): void
    {
        $finalizer = self::read('src/Service/Navigation/Persistence/NavigationPersistenceFinalizeService.php');
        $import = self::read('src/Service/Navigation/Import/NavigationConfigImportService.php');
        $snapshot = self::read('src/Service/Navigation/Snapshot/NavigationSnapshotService.php');

        self::assertStringContainsString('cache->invalidate()', $finalizer);
        self::assertStringContainsString('getTransactionNestingLevel() > 0', $finalizer);
        self::assertStringContainsString('side effects were skipped', $finalizer);
        self::assertStringContainsString('autoBackup->writeLatest()', $finalizer);

        $transactionCheck = strpos($finalizer, 'getTransactionNestingLevel() > 0');
        $cacheInvalidate = strpos($finalizer, 'cache->invalidate()');
        $backupWrite = strpos($finalizer, 'autoBackup->writeLatest()');
        self::assertIsInt($transactionCheck);
        self::assertIsInt($cacheInvalidate);
        self::assertIsInt($backupWrite);
        self::assertLessThan($cacheInvalidate, $transactionCheck, 'The finalizer must reject active transactions before cache invalidation.');
        self::assertLessThan($backupWrite, $cacheInvalidate, 'Rolling backup must remain after cache invalidation on the committed path.');

        self::assertStringContainsString('replaceFromConfig(array $config, bool $requireEmpty = false, bool $finalize = true)', $import);
        self::assertStringContainsString('finalizeCommittedChange()', $import);
        self::assertStringContainsString("replaceFromConfig(['shell_groups' => \$snapshot['shell_groups']], false, false)", $snapshot);
        self::assertStringContainsString('$this->finalizer->finalizeCommittedChange();', $snapshot);
        self::assertStringContainsString('wrapInTransaction', $snapshot);
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
