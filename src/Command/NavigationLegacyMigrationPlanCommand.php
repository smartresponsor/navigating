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

        $this->renderReport($output, $plan['shell_groups'], $plan['archived_items'], $plan['raw_rows']);
        $output->writeln('<info>Database was not modified.</info>');
        $output->writeln('<comment>Plan: '.$path.'</comment>');
        $output->writeln('<comment>Plan includes the raw legacy rows, converted W31 payload and SHA-256 checksum.</comment>');

        return Command::SUCCESS;
    }

    /**
     * @param array<string, mixed> $groups
     * @param list<mixed> $archivedItems
     * @param list<array<string, mixed>> $rawRows
     */
    private function renderReport(OutputInterface $output, array $groups, array $archivedItems, array $rawRows): void
    {
        $disabledCount = 0;
        $roleRestrictedCount = 0;
        foreach ($rawRows as $row) {
            if (!(bool) ($row['enabled'] ?? true)) {
                ++$disabledCount;
            }
            if (is_string($row['required_role'] ?? null) && '' !== trim((string) $row['required_role'])) {
                ++$roleRestrictedCount;
            }
        }

        $syntheticCount = 0;
        $output->writeln(sprintf('<info>Legacy migration plan validated for %d navigation items.</info>', count($rawRows)));
        $output->writeln(sprintf(
            '<info>State summary: %d disabled, %d archived, %d role-restricted.</info>',
            $disabledCount,
            count($archivedItems),
            $roleRestrictedCount,
        ));
        $output->writeln(sprintf('<info>Future W31 menus: %d.</info>', count($groups)));

        foreach ($groups as $groupKey => $groupConfig) {
            if (!is_string($groupKey) || !is_array($groupConfig)) {
                continue;
            }

            $synthetic = str_starts_with($groupKey, 'legacy_');
            if ($synthetic) {
                ++$syntheticCount;
            }

            $location = is_string($groupConfig['location'] ?? null) ? $groupConfig['location'] : '(unknown)';
            $items = is_array($groupConfig['items'] ?? null) ? $groupConfig['items'] : [];
            $marker = $synthetic ? ' synthetic-legacy' : '';
            $output->writeln(sprintf(
                '  - <comment>%s</comment> @ %s: %d items%s',
                $groupKey,
                $location,
                count($items),
                $marker,
            ));
        }

        if ($syntheticCount > 0) {
            $output->writeln(sprintf(
                '<comment>%d future menu(s) are synthetic legacy groups created to preserve items not present in the canonical inventory.</comment>',
                $syntheticCount,
            ));
        } else {
            $output->writeln('<info>All legacy items mapped into canonical W31 menu groups.</info>');
        }
    }

    /** @param array<string, mixed> $payload */
    private function checksum(array $payload): string
    {
        unset($payload['sha256']);

        return hash('sha256', json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }
}
