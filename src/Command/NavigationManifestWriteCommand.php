<?php

declare(strict_types=1);

namespace App\Navigating\Command;

use App\Navigating\Service\Navigation\Snapshot\NavigationSnapshotFileService;
use App\Navigating\Service\Navigation\Snapshot\NavigationSnapshotService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'navigation:manifest:write', description: 'Write the current navigation database as the repository install manifest.')]
final class NavigationManifestWriteCommand extends Command
{
    public function __construct(
        private readonly NavigationSnapshotService $snapshotService,
        private readonly NavigationSnapshotFileService $snapshotFileService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = dirname(__DIR__, 2).'/resources/navigation/navigation.install.json';

        try {
            $this->snapshotFileService->write($path, $this->snapshotService->create());
        } catch (\Throwable $exception) {
            $output->writeln('<error>'.$exception->getMessage().'</error>');
            return Command::FAILURE;
        }

        $output->writeln('<info>Navigation install manifest written: '.$path.'</info>');
        return Command::SUCCESS;
    }
}
