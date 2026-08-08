<?php

declare(strict_types=1);

namespace App\Navigating\Command;

use App\Navigating\Entity\NavigationItem;
use App\Navigating\Entity\NavigationMenu;
use App\Navigating\Service\Navigation\Snapshot\NavigationSnapshotService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'navigation:database:rebuild',
    description: 'Safely rebuild only Navigating Doctrine tables after creating a portable snapshot.',
)]
final class NavigationDatabaseRebuildCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly NavigationSnapshotService $snapshotService,
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('force', null, InputOption::VALUE_NONE, 'Required acknowledgement for destructive table rebuild.')
            ->addOption('backup-path', null, InputOption::VALUE_REQUIRED, 'Optional explicit backup path before rebuild.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (true !== $input->getOption('force')) {
            $output->writeln('<error>This command rebuilds navigation_menu and navigation_item. Re-run with --force.</error>');

            return Command::FAILURE;
        }

        $metadata = [
            $this->entityManager->getClassMetadata(NavigationMenu::class),
            $this->entityManager->getClassMetadata(NavigationItem::class),
        ];

        try {
            $snapshot = $this->snapshotService->create();
            $backupPath = $this->resolveBackupPath($input->getOption('backup-path'));
            $this->writeSnapshot($backupPath, $snapshot);

            $output->writeln('<info>Navigation pre-rebuild backup: '.$backupPath.'</info>');

            $schemaTool = new SchemaTool($this->entityManager);
            $schemaTool->dropSchema($metadata);
            $schemaTool->createSchema($metadata);
            $this->entityManager->clear();

            $this->snapshotService->restore($snapshot);
        } catch (\Throwable $exception) {
            $output->writeln('<error>Navigation rebuild failed: '.$exception->getMessage().'</error>');
            $output->writeln('<comment>The pre-rebuild JSON backup is retained when it was created successfully.</comment>');

            return Command::FAILURE;
        }

        $output->writeln('<info>Navigating tables rebuilt and restored successfully.</info>');

        return Command::SUCCESS;
    }

    private function resolveBackupPath(mixed $value): string
    {
        if (is_string($value) && '' !== trim($value)) {
            $value = trim($value);
            if (str_starts_with($value, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $value)) {
                return $value;
            }

            return $this->projectDir.'/'.$value;
        }

        return $this->projectDir.'/var/backup/navigating/pre-rebuild-'.date('Ymd-His').'.json';
    }

    /** @param array<string, mixed> $snapshot */
    private function writeSnapshot(string $path, array $snapshot): void
    {
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException('Unable to create navigation backup directory.');
        }

        $json = json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        if (false === file_put_contents($path, $json.PHP_EOL, LOCK_EX)) {
            throw new \RuntimeException('Unable to write navigation pre-rebuild backup.');
        }
    }
}
