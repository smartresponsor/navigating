<?php

declare(strict_types=1);

namespace App\Navigating\Command;

use App\Navigating\Entity\NavigationItem;
use App\Navigating\Entity\NavigationMenu;
use App\Navigating\Repository\NavigationMenuRepository;
use App\Navigating\Service\Navigation\Import\NavigationConfigImportService;
use App\Navigating\Service\Navigation\Migration\NavigationLegacyMigrationPlanService;
use App\Navigating\Service\Navigation\Persistence\NavigationPersistenceFinalizeService;
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
        $schemaManager = $connection->createSchemaManager();
        if ($schemaManager->tablesExist([self::SHADOW_TABLE])) {
            $output->writeln('<error>Recovery shadow table already exists; refusing to overwrite it.</error>');

            return Command::FAILURE;
        }

        $metadata = [
            $this->entityManager->getClassMetadata(NavigationMenu::class),
            $this->entityManager->getClassMetadata(NavigationItem::class),
        ];

        try {
            $connection->beginTransaction();
            $connection->executeStatement('ALTER TABLE navigation_item RENAME TO '.self::SHADOW_TABLE);
            (new SchemaTool($this->entityManager))->createSchema($metadata);
            $connection->commit();
        } catch (\Throwable $exception) {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }
            $output->writeln('<error>W31 schema creation failed; legacy table transaction was rolled back where supported: '.$exception->getMessage().'</error>');

            return Command::FAILURE;
        }

        try {
            $this->entityManager->clear();
            $this->importService->replaceFromConfig(['shell_groups' => $payload['shell_groups']], true, false);
            $this->restoreArchivedItems($payload['archived_items']);
            $this->assertImportedCount((int) $payload['row_count']);
            $connection->executeStatement('DROP TABLE '.self::SHADOW_TABLE);
            $this->finalizer->finalizeCommittedChange();
        } catch (\Throwable $exception) {
            $this->restoreLegacySchema($metadata);
            $output->writeln('<error>Legacy upgrade failed. Recovery was attempted and the migration plan remains untouched: '.$exception->getMessage().'</error>');

            return Command::FAILURE;
        }

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

    /** @param list<object> $metadata */
    private function restoreLegacySchema(array $metadata): void
    {
        $connection = $this->entityManager->getConnection();
        try {
            $this->entityManager->clear();
            (new SchemaTool($this->entityManager))->dropSchema($metadata);
            if ($connection->createSchemaManager()->tablesExist([self::SHADOW_TABLE])) {
                $connection->executeStatement('ALTER TABLE '.self::SHADOW_TABLE.' RENAME TO navigation_item');
            }
        } catch (\Throwable) {
            // Preserve the shadow table and JSON plan for manual recovery if automatic rollback cannot complete.
        }
    }

    /** @param array<string, mixed> $payload */
    private function checksum(array $payload): string
    {
        unset($payload['sha256']);

        return hash('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }
}
