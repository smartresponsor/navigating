<?php

declare(strict_types=1);

namespace App\Navigating\Command;

use App\Navigating\Service\Navigation\NavigationConfigValidator;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'navigation:config:validate',
    description: 'Validates the configured Navigation MVP schema.',
)]
final class NavigationValidateConfigCommand extends Command
{
    /**
     * @param array<string, mixed> $navigationConfig
     */
    public function __construct(
        private readonly NavigationConfigValidator $validator,
        private readonly array $navigationConfig = [],
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $result = $this->validator->validate($this->navigationConfig);

        foreach ($result->warnings as $warning) {
            $io->warning($warning);
        }

        if (!$result->isValid()) {
            foreach ($result->errors as $error) {
                $io->error($error);
            }

            return Command::FAILURE;
        }

        $io->success('Navigation config is valid.');

        return Command::SUCCESS;
    }
}
