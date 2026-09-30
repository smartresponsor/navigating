<?php

declare(strict_types=1);

namespace App\Navigating\Command;

use App\Navigating\Repository\NavigationPersistenceRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'navigation:database:update',
    description: 'Synchronize only Navigating Doctrine tables without treating unrelated host tables as schema targets.',
)]
final class NavigationDatabaseUpdateCommand extends Command
{
    public function __construct(private readonly NavigationPersistenceRepository $persistenceRepository)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            if (!$this->persistenceRepository->synchronizeOwnedSchema()) {
                $output->writeln('<info>Navigating schema is already up to date.</info>');

                return Command::SUCCESS;
            }
        } catch (\Throwable $exception) {
            $output->writeln('<error>Navigating schema update failed: '.$exception->getMessage().'</error>');

            return Command::FAILURE;
        }

        $output->writeln('<info>Navigating schema synchronized successfully.</info>');
        $output->writeln('<comment>Only navigation_menu and navigation_item were visible to Doctrine schema comparison.</comment>');

        return Command::SUCCESS;
    }
}
