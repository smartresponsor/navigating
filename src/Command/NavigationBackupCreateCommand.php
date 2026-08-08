<?php

declare(strict_types=1);

namespace App\Navigating\Command;

use App\Navigating\Service\Navigation\Snapshot\NavigationSnapshotFileService;
use App\Navigating\Service\Navigation\Snapshot\NavigationSnapshotService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(name: 'navigation:backup:create', description: 'Create a portable JSON backup of all Navigating menu configuration.')]
final class NavigationBackupCreateCommand extends Command
{
    public function __construct(
        private readonly NavigationSnapshotService $snapshotService,
        private readonly NavigationSnapshotFileService $snapshotFileService,
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('path', InputArgument::OPTIONAL, 'Output JSON path. Defaults to var/backup/navigating/.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = $input->getArgument('path');
        $path = is_string($path) && '' !== trim($path)
            ? $this->absolutePath(trim($path))
            : $this->projectDir.'/var/backup/navigating/navigation-'.date('Ymd-His').'-'.bin2hex(random_bytes(4)).'.json';

        try {
            $this->snapshotFileService->write($path, $this->snapshotService->create());
        } catch (\Throwable $exception) {
            $output->writeln('<error>'.$exception->getMessage().'</error>');
            return Command::FAILURE;
        }

        $output->writeln('<info>Navigation backup written: '.$path.'</info>');
        return Command::SUCCESS;
    }

    private function absolutePath(string $path): string
    {
        if (str_starts_with($path, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $path)) {
            return $path;
        }
        return $this->projectDir.'/'.$path;
    }
}
