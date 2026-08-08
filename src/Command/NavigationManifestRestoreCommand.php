<?php

declare(strict_types=1);

namespace App\Navigating\Command;

use App\Navigating\Repository\NavigationMenuRepository;
use App\Navigating\Service\Navigation\Snapshot\NavigationSnapshotService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'navigation:manifest:restore', description: 'Restore navigation from the repository install manifest.')]
final class NavigationManifestRestoreCommand extends Command
{
    public function __construct(
        private readonly NavigationSnapshotService $snapshotService,
        private readonly NavigationMenuRepository $menuRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('force', null, InputOption::VALUE_NONE, 'Replace existing navigation data.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ([] !== $this->menuRepository->findAll() && true !== $input->getOption('force')) {
            $output->writeln('<error>Navigation database is not empty. Re-run with --force to restore the install manifest.</error>');
            return Command::FAILURE;
        }

        $path = dirname(__DIR__, 2).'/resources/navigation/navigation.install.json';
        if (!is_file($path)) {
            $output->writeln('<error>Navigation install manifest does not exist. Run navigation:manifest:write first.</error>');
            return Command::FAILURE;
        }

        try {
            $contents = file_get_contents($path);
            if (!is_string($contents)) {
                throw new \RuntimeException('Unable to read navigation install manifest.');
            }
            $snapshot = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($snapshot)) {
                throw new \RuntimeException('Navigation install manifest must decode to an object.');
            }
            $count = $this->snapshotService->restore($snapshot);
        } catch (\Throwable $exception) {
            $output->writeln('<error>'.$exception->getMessage().'</error>');
            return Command::FAILURE;
        }

        $output->writeln(sprintf('<info>Restored %d navigation menus from install manifest.</info>', $count));
        return Command::SUCCESS;
    }
}
