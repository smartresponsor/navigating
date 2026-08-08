<?php

declare(strict_types=1);

namespace App\Navigating\Command;

use App\Navigating\Entity\NavigationItem;
use App\Navigating\Entity\NavigationMenu;
use App\Navigating\Repository\NavigationMenuRepository;
use App\Navigating\Service\Navigation\Import\NavigationConfigImportService;
use App\Navigating\Service\Navigation\Migration\NavigationLegacyMigrationPlanService;
use App\Navigating\Service\Navigation\Persistence\NavigationPersistenceFinalizeService;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'navigation:database:legacy-upgrade',
    description: 'Upgrade a validated W30 navigation_item table to the W31 menu/item schema using a previously written migration plan.',
)]
final class NavigationLegacyMigrationUpgradeCommand extends Command
{
    private const SHADOW_TABLE = 'navigation_item_legacy_w31';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly NavigationLegacyMigrationPlanService $planService,
        private readonly NavigationConfigImportService $importService,
        private readonly NavigationMenuRepository $menuRepository,
        private readonly NavigationPersistenceFinalizeService $finalizer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('plan', InputArgument::REQUIRED, 'Path to a legacy-upgrade-plan JSON file created by navigation:database:legacy-plan.')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Acknowledge the component-scoped schema replacement after preflight verification.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$input->getOption('force')) {
            $output->writeln('<error>Refusing legacy schema upgrade without --force.</error>');

            return Command::FAILURE;
        }

        $path = (string) $input->getArgument('plan');
        try {
            $payload = $this->readAndValidatePlan($path);
            $livePlan = $this->planService->createPlan();
            $this->assertPlanStillMatchesDatabase($payload, $livePlan);
        } catch (\Throwable $exception) {
            $output->writeln('<error>Legacy upgrade preflight failed: '.$exception->getMessage().'</error>');

            return Command::FAILURE;
        }

        $connection = $this->entityManager->getConnection();
        if (!$connection->getDatabasePlatform() instanceof SQLitePlatform) {
            $output->writeln('<error>The guarded W30 -> W31 legacy upgrade currently supports SQLite only.</error>');

            return Command::FAILURE;
        }

        $schemaManager = $connection->createSchemaManager();
        if ($schemaManager->tablesExist([self::SHADOW_TABLE])) {
            $output->writeln('<error>Recovery shadow table already exists; refusing to overwrite it.</error>');

            return Command::FAILURE;
        }

        try {
            $this->assertNoExternalSqliteDependencies();
            $legacySchemaObjects = $this->captureLegacySchemaObjects();
        } catch (\Throwable $exception) {
            $output->writeln('<error>Legacy SQLite dependency preflight failed: '.$exception->getMessage().'</error>');

            return Command::FAILURE;
        }

        $metadata = [
            $this->entityManager->getClassMetadata(NavigationMenu::class),
            $this->entityManager->getClassMetadata(NavigationItem::class),
        ];

        $foreignKeysEnabled = '1' === (string) $connection->fetchOne('PRAGMA foreign_keys');

        try {
            if ($foreignKeysEnabled) {
                $connection->executeStatement('PRAGMA foreign_keys = OFF');
            }

            $connection->beginTransaction();
            $connection->executeStatement('ALTER TABLE navigation_item RENAME TO '.self::SHADOW_TABLE);
            $this->dropLegacySchemaObjects($legacySchemaObjects);
            (new SchemaTool($this->entityManager))->createSchema($metadata);
            $connection->commit();
        } catch (\Throwable $exception) {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }
            $this->restoreForeignKeyPragma($foreignKeysEnabled);
            $output->writeln('<error>W31 schema creation failed; legacy table transaction was rolled back where supported: '.$exception->getMessage().'</error>');

            return Command::FAILURE;
        }

        try {
            $this->entityManager->clear();
            $this->importService->replaceFromConfig(['shell_groups' => $payload['shell_groups']], true, false);
            $this->restoreArchivedItems($payload['archived_items']);
            $this->assertImportedCount((int) $payload['row_count']);
            $this->assertForeignKeyIntegrity();

            // Keep the physical legacy copy until every fallible database check and
            // connection-state restoration has completed. Dropping the shadow table
            // is the irreversible commit point of the legacy upgrade.
            $this->restoreForeignKeyPragma($foreignKeysEnabled);
            $connection->executeStatement('DROP TABLE '.self::SHADOW_TABLE);
        } catch (\Throwable $exception) {
            $recovered = $this->restoreLegacySchema($metadata, $legacySchemaObjects);
            $this->restoreForeignKeyPragma($foreignKeysEnabled);
            $suffix = $recovered
                ? 'Legacy schema was restored automatically.'
                : 'Automatic restoration could not be completed; the migration plan and any remaining shadow table were preserved for manual recovery.';
            $output->writeln('<error>Legacy upgrade failed. '.$suffix.' '.$exception->getMessage().'</error>');

            return Command::FAILURE;
        }

        // Post-commit housekeeping is intentionally outside the rollback block.
        // The finalizer contains its own error isolation and must never turn a
        // completed schema upgrade into an attempted legacy rollback.
        $this->finalizer->finalizeCommittedChange();

        $output->writeln(sprintf('<info>Legacy navigation upgraded successfully: %d items across %d menus.</info>', (int) $payload['row_count'], count($payload['shell_groups'])));
        $output->writeln('<comment>Validated migration plan retained at '.$path.'</comment>');

        return Command::SUCCESS;
    }

    /** @return array<string, mixed> */
    private function readAndValidatePlan(string $path): array
    {
        if (!is_file($path)) {
            throw new \RuntimeException('Migration plan does not exist: '.$path);
        }
        $json = file_get_contents($path);
        if (!is_string($json)) {
            throw new \RuntimeException('Cannot read migration plan: '.$path);
        }
        $payload = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($payload)) {
            throw new \RuntimeException('Migration plan must decode to an object.');
        }
        if (($payload['format'] ?? null) !== 'smartresponsor.navigation.legacy-upgrade-plan' || ($payload['version'] ?? null) !== 1) {
            throw new \RuntimeException('Unsupported legacy migration plan format or version.');
        }
        $expected = $payload['sha256'] ?? null;
        if (!is_string($expected) || !hash_equals($expected, $this->checksum($payload))) {
            throw new \RuntimeException('Legacy migration plan SHA-256 verification failed.');
        }
        if (!is_array($payload['raw_rows'] ?? null) || !is_array($payload['shell_groups'] ?? null) || !is_array($payload['archived_items'] ?? null)) {
            throw new \RuntimeException('Legacy migration plan payload is incomplete.');
        }

        return $payload;
    }

    /** @param array<string, mixed> $payload @param array<string, mixed> $livePlan */
    private function assertPlanStillMatchesDatabase(array $payload, array $livePlan): void
    {
        if (($livePlan['state'] ?? null) !== 'legacy') {
            throw new \RuntimeException('Database is no longer in the legacy W30 state.');
        }
        if (($payload['row_count'] ?? null) !== ($livePlan['rows'] ?? null)) {
            throw new \RuntimeException('Legacy row count changed after the migration plan was created. Create a new plan.');
        }
        if (($payload['raw_rows'] ?? null) !== ($livePlan['raw_rows'] ?? null)) {
            throw new \RuntimeException('Legacy navigation data changed after the migration plan was created. Create a new plan.');
        }
        if (($payload['shell_groups'] ?? null) !== ($livePlan['shell_groups'] ?? null)) {
            throw new \RuntimeException('Canonical navigation mapping changed after the migration plan was created. Create a new plan.');
        }
    }

    private function assertNoExternalSqliteDependencies(): void
    {
        $connection = $this->entityManager->getConnection();

        $views = $connection->executeQuery(
            "SELECT name FROM sqlite_schema WHERE type = 'view' AND sql IS NOT NULL AND lower(sql) LIKE '%navigation_item%'",
        )->fetchFirstColumn();
        if ([] !== $views) {
            throw new \RuntimeException('Views reference legacy navigation_item: '.implode(', ', array_map('strval', $views)).'.');
        }

        $tables = $connection->executeQuery(
            "SELECT name FROM sqlite_schema WHERE type = 'table' AND name NOT LIKE 'sqlite_%' AND name <> 'navigation_item'",
        )->fetchFirstColumn();
        $foreignKeyDependents = [];
        foreach ($tables as $table) {
            $table = (string) $table;
            foreach ($connection->executeQuery('PRAGMA foreign_key_list('.$this->quoteSqliteIdentifier($table).')')->fetchAllAssociative() as $foreignKey) {
                if ('navigation_item' === ($foreignKey['table'] ?? null)) {
                    $foreignKeyDependents[] = $table;
                    break;
                }
            }
        }
        if ([] !== $foreignKeyDependents) {
            throw new \RuntimeException('Foreign keys outside Navigating reference legacy navigation_item: '.implode(', ', $foreignKeyDependents).'.');
        }
    }

    /** @return list<array{name:string,type:string,sql:string}> */
    private function captureLegacySchemaObjects(): array
    {
        $rows = $this->entityManager->getConnection()->executeQuery(
            "SELECT name, type, sql FROM sqlite_schema WHERE tbl_name = 'navigation_item' AND type IN ('index', 'trigger') AND sql IS NOT NULL ORDER BY type, name",
        )->fetchAllAssociative();

        $objects = [];
        foreach ($rows as $row) {
            $name = $row['name'] ?? null;
            $type = $row['type'] ?? null;
            $sql = $row['sql'] ?? null;
            if (is_string($name) && is_string($type) && is_string($sql)) {
                $objects[] = ['name' => $name, 'type' => $type, 'sql' => $sql];
            }
        }

        return $objects;
    }

    /** @param list<array{name:string,type:string,sql:string}> $objects */
    private function dropLegacySchemaObjects(array $objects): void
    {
        $connection = $this->entityManager->getConnection();
        foreach ($objects as $object) {
            $keyword = 'trigger' === $object['type'] ? 'TRIGGER' : 'INDEX';
            $connection->executeStatement('DROP '.$keyword.' IF EXISTS '.$this->quoteSqliteIdentifier($object['name']));
        }
    }

    /** @param list<mixed> $archived */
    private function restoreArchivedItems(array $archived): void
    {
        $lookup = array_fill_keys(array_values(array_filter($archived, 'is_string')), true);
        if ([] === $lookup) {
            return;
        }
        foreach ($this->menuRepository->findAll() as $menu) {
            foreach ($menu->getItems() as $item) {
                if (isset($lookup[$menu->getMenuKey().':'.$item->getNavigationKey()])) {
                    $item->archive();
                }
            }
        }
        $this->entityManager->flush();
    }

    private function assertImportedCount(int $expected): void
    {
        $actual = 0;
        foreach ($this->menuRepository->findAll() as $menu) {
            $actual += $menu->getItems()->count();
        }
        if ($actual !== $expected) {
            throw new \RuntimeException(sprintf('Imported item count mismatch: expected %d, got %d.', $expected, $actual));
        }
    }

    private function assertForeignKeyIntegrity(): void
    {
        $violations = $this->entityManager->getConnection()->executeQuery('PRAGMA foreign_key_check')->fetchAllAssociative();
        if ([] !== $violations) {
            throw new \RuntimeException('SQLite foreign_key_check reported violations after W31 import.');
        }
    }

    /** @param list<object> $metadata @param list<array{name:string,type:string,sql:string}> $legacySchemaObjects */
    private function restoreLegacySchema(array $metadata, array $legacySchemaObjects): bool
    {
        $connection = $this->entityManager->getConnection();
        try {
            $this->entityManager->clear();
            (new SchemaTool($this->entityManager))->dropSchema($metadata);
            if (!$connection->createSchemaManager()->tablesExist([self::SHADOW_TABLE])) {
                return false;
            }
            $connection->executeStatement('ALTER TABLE '.self::SHADOW_TABLE.' RENAME TO navigation_item');
            foreach ($legacySchemaObjects as $object) {
                $connection->executeStatement($object['sql']);
            }

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function restoreForeignKeyPragma(bool $enabled): void
    {
        if ($this->entityManager->getConnection()->isTransactionActive()) {
            return;
        }
        $this->entityManager->getConnection()->executeStatement('PRAGMA foreign_keys = '.($enabled ? 'ON' : 'OFF'));
    }

    private function quoteSqliteIdentifier(string $identifier): string
    {
        return '"'.str_replace('"', '""', $identifier).'"';
    }

    /** @param array<string, mixed> $payload */
    private function checksum(array $payload): string
    {
        unset($payload['sha256']);

        return hash('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }
}
