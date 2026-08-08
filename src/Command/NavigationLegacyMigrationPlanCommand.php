<?php

declare(strict_types=1);

namespace App\Navigating\Command;

use App\Navigating\Service\Navigation\Migration\NavigationLegacyMigrationPlanService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'navigation:database:legacy-plan',
    description: 'Validate legacy Navigating data and write a lossless W30-to-W31 migration plan without changing the database.',
)]
final class NavigationLegacyMigrationPlanCommand extends Command
{
    public function __construct(
        private readonly NavigationLegacyMigrationPlanService $planService,
        #[Autowire('%kernel.project_dir%/var/backup/navigating')]
        private readonly string $backupDirectory,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $plan = $this->planService->createPlan();
        } catch (\Throwable $exception) {
            $output->writeln('<error>Legacy navigation migration preflight failed: '.$exception->getMessage().'</error>');

            return Command::FAILURE;
        }

        if ('current' === $plan['state']) {
            $output->writeln('<info>Navigating database is already on the W31 schema.</info>');

            return Command::SUCCESS;
        }
        if ('empty' === $plan['state']) {
            $output->writeln('<comment>No legacy navigation_item table exists. Use normal W31 installation/bootstrap.</comment>');

            return Command::SUCCESS;
        }
        if ('legacy' !== $plan['state']) {
            $output->writeln('<error>Navigating schema is not recognized as either legacy W30 or current W31.</error>');

            return Command::FAILURE;
        }

        if (!is_dir($this->backupDirectory) && !mkdir($this->backupDirectory, 0775, true) && !is_dir($this->backupDirectory)) {
            $output->writeln('<error>Cannot create Navigating backup directory.</error>');

            return Command::FAILURE;
        }

        $payload = [
            'format' => 'smartresponsor.navigation.legacy-upgrade-plan',
            'version' => 1,
            'created_at' => (new \DateTimeImmutable())->format(DATE_ATOM),
            'source_state' => 'legacy-w30',
            'row_count' => $plan['rows'],
            'raw_rows' => $plan['raw_rows'],
            'shell_groups' => $plan['shell_groups'],
            'archived_items' => $plan['archived_items'],
        ];
        $payload['sha256'] = $this->checksum($payload);

        $path = rtrim($this->backupDirectory, '/\\').DIRECTORY_SEPARATOR.'legacy-upgrade-plan-'.(new \DateTimeImmutable())->format('Ymd-His').'.json';
        try {
            $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n";
        } catch (\JsonException $exception) {
            $output->writeln('<error>Cannot serialize legacy migration plan: '.$exception->getMessage().'</error>');

            return Command::FAILURE;
        }

        if (false === file_put_contents($path, $json, LOCK_EX)) {
            $output->writeln('<error>Cannot write legacy migration plan: '.$path.'</error>');

            return Command::FAILURE;
        }

        $output->writeln(sprintf('<info>Legacy migration plan validated for %d navigation items.</info>', $plan['rows']));
        $output->writeln(sprintf('<info>Future W31 menus: %d.</info>', count($plan['shell_groups'])));
        $output->writeln('<info>Database was not modified.</info>');
        $output->writeln('<comment>Plan: '.$path.'</comment>');

        return Command::SUCCESS;
    }

    /** @param array<string, mixed> $payload */
    private function checksum(array $payload): string
    {
        unset($payload['sha256']);

        return hash('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }
}
