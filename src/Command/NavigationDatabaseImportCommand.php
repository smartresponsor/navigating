<?php

declare(strict_types=1);

namespace App\Navigating\Command;

use App\Navigating\Repository\NavigationMenuRepository;
use App\Navigating\Service\Navigation\Import\NavigationConfigImportService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'navigation:database:import-config',
    description: 'Bootstrap Doctrine-backed navigation menus from the current merged navigation configuration.',
)]
final class NavigationDatabaseImportCommand extends Command
{
    /** @param array<string, mixed> $navigationConfig */
    public function __construct(
        private readonly NavigationConfigImportService $importService,
        private readonly NavigationMenuRepository $menuRepository,
        private readonly array $navigationConfig = [],
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('force', null, InputOption::VALUE_NONE, 'Replace existing navigation menus before importing. Use only for bootstrap/reset.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ([] !== $this->menuRepository->findAll() && true !== $input->getOption('force')) {
            $output->writeln('<error>Navigation database is not empty. Re-run with --force only when an intentional reset is required.</error>');
            return Command::FAILURE;
        }

        try {
            $count = $this->importService->replaceFromConfig($this->navigationConfig);
        } catch (\Throwable $exception) {
            $output->writeln('<error>'.$exception->getMessage().'</error>');
            return Command::FAILURE;
        }

        $output->writeln(sprintf('<info>Imported %d navigation menus into Doctrine storage.</info>', $count));
        return Command::SUCCESS;
    }
}
