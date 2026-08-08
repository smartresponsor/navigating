<?php

declare(strict_types=1);

namespace App\Navigating\Command;

use App\Navigating\Repository\NavigationMenuRepository;
use App\Navigating\Service\Navigation\Snapshot\NavigationSnapshotService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(name: 'navigation:backup:restore', description: 'Restore Navigating menu configuration from a portable JSON backup.')]
final class NavigationBackupRestoreCommand extends Command
{
    public function __construct(
        private readonly NavigationSnapshotService $snapshotService,
        private readonly NavigationMenuRepository $menuRepository,
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('path', InputArgument::REQUIRED, 'Backup JSON path.');
        $this->addOption('force', null, InputOption::VALUE_NONE, 'Replace existing navigation data.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ([] !== $this->menuRepository->findAll() && true !== $input->getOption('force')) {
            $output->writeln('<error>Navigation database is not empty. Re-run with --force to restore this backup.</error>');
            return Command::FAILURE;
        }

        $path = $this->absolutePath((string) $input->getArgument('path'));
        if (!is_file($path)) {
            $output->writeln('<error>Navigation backup file not found: '.$path.'</error>');
            return Command::FAILURE;
        }

        try {
            $contents = file_get_contents($path);
            if (!is_string($contents)) {
                throw new \RuntimeException('Unable to read navigation backup.');
            }
            $snapshot = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($snapshot)) {
                throw new \RuntimeException('Navigation backup must decode to an object.');
            }
            $count = $this->snapshotService->restore($snapshot);
        } catch (\Throwable $exception) {
            $output->writeln('<error>'.$exception->getMessage().'</error>');
            return Command::FAILURE;
        }

        $output->writeln(sprintf('<info>Restored %d navigation menus from %s.</info>', $count, $path));
        return Command::SUCCESS;
    }

    private function absolutePath(string $path): string
    {
        $path = trim($path);
        if (str_starts_with($path, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $path)) {
            return $path;
        }
        return $this->projectDir.'/'.$path;
    }
}
