<?php

declare(strict_types=1);

namespace App\Navigating\Command;

use App\Navigating\Entity\NavigationItem;
use App\Navigating\Entity\NavigationMenu;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'navigation:database:update',
    description: 'Apply additive-only Doctrine schema changes for Navigating entities without touching unrelated host tables.',
)]
final class NavigationDatabaseUpdateCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $metadata = [
            $this->entityManager->getClassMetadata(NavigationMenu::class),
            $this->entityManager->getClassMetadata(NavigationItem::class),
        ];

        try {
            $schemaTool = new SchemaTool($this->entityManager);
            $sql = $schemaTool->getUpdateSchemaSql($metadata, true);

            if ([] === $sql) {
                $output->writeln('<info>Navigating schema is already up to date.</info>');

                return Command::SUCCESS;
            }

            $schemaTool->updateSchema($metadata, true);
        } catch (\Throwable $exception) {
            $output->writeln('<error>Navigating additive schema update failed: '.$exception->getMessage().'</error>');

            return Command::FAILURE;
        }

        $output->writeln('<info>Applied additive-only Navigating schema changes.</info>');
        $output->writeln('<comment>Destructive Navigating schema changes require navigation:database:rebuild --force so configuration is snapshotted and restored.</comment>');

        return Command::SUCCESS;
    }
}
