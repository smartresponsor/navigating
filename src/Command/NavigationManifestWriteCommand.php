<?php

declare(strict_types=1);

namespace App\Navigating\Command;

use App\Navigating\Service\Navigation\Snapshot\NavigationSnapshotService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'navigation:manifest:write', description: 'Write the current navigation database as the repository install manifest.')]
final class NavigationManifestWriteCommand extends Command
{
    public function __construct(private readonly NavigationSnapshotService $snapshotService)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = dirname(__DIR__, 2).'/resources/navigation/navigation.install.json';
        $directory = dirname($path);

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            $output->writeln('<error>Unable to create navigation manifest directory.</error>');
            return Command::FAILURE;
        }

        try {
            $json = json_encode($this->snapshotService->create(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
            if (false === file_put_contents($path, $json.PHP_EOL, LOCK_EX)) {
                throw new \RuntimeException('Unable to write navigation install manifest.');
            }
        } catch (\Throwable $exception) {
            $output->writeln('<error>'.$exception->getMessage().'</error>');
            return Command::FAILURE;
        }

        $output->writeln('<info>Navigation install manifest written: '.$path.'</info>');
        return Command::SUCCESS;
    }
}
