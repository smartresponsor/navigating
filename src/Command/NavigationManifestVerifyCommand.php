<?php

declare(strict_types=1);

namespace App\Navigating\Command;

use App\Navigating\Service\Navigation\Snapshot\NavigationSnapshotService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'navigation:manifest:verify', description: 'Verify that the repository install manifest matches the current navigation database state.')]
final class NavigationManifestVerifyCommand extends Command
{
    public function __construct(private readonly NavigationSnapshotService $snapshotService)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = dirname(__DIR__, 2).'/resources/navigation/navigation.install.json';
        if (!is_file($path)) {
            $output->writeln('<error>Navigation install manifest does not exist. Run navigation:manifest:write.</error>');
            return Command::FAILURE;
        }

        try {
            $contents = file_get_contents($path);
            if (!is_string($contents)) {
                throw new \RuntimeException('Unable to read navigation install manifest.');
            }
            $manifest = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($manifest)) {
                throw new \RuntimeException('Navigation install manifest must decode to an object.');
            }
            $this->snapshotService->assertValid($manifest);
            $current = $this->snapshotService->create();
        } catch (\Throwable $exception) {
            $output->writeln('<error>'.$exception->getMessage().'</error>');
            return Command::FAILURE;
        }

        foreach (['shell_groups', 'archived_items'] as $key) {
            if (($manifest[$key] ?? null) !== ($current[$key] ?? null)) {
                $output->writeln('<error>Navigation install manifest differs from the current database. Run navigation:manifest:write and commit the updated manifest.</error>');
                return Command::FAILURE;
            }
        }

        $output->writeln('<info>Navigation install manifest matches the current database configuration.</info>');
        return Command::SUCCESS;
    }
}
