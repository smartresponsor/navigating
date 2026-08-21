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
    description: 'Synchronize only Navigating Doctrine tables without treating unrelated host tables as schema targets.',
)]
final class NavigationDatabaseUpdateCommand extends Command
{
    private const OWNED_TABLES = [
        'navigation_menu' => true,
        'navigation_item' => true,
    ];

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

        $connection = $this->entityManager->getConnection();
        $configuration = $connection->getConfiguration();
        $previousFilter = $configuration->getSchemaAssetsFilter();

        $configuration->setSchemaAssetsFilter(
            static function (mixed $asset): bool {
                $name = is_string($asset)
                    ? $asset
                    : (method_exists($asset, 'getObjectName')
                        ? $asset->getObjectName()->toString()
                        : (method_exists($asset, 'getName') ? $asset->getName() : ''));

                return isset(self::OWNED_TABLES[$name]);
            },
        );

        try {
            $schemaTool = new SchemaTool($this->entityManager);
            $sql = $schemaTool->getUpdateSchemaSql($metadata);

            if ([] === $sql) {
                $output->writeln('<info>Navigating schema is already up to date.</info>');

                return Command::SUCCESS;
            }

            $schemaTool->updateSchema($metadata);
        } catch (\Throwable $exception) {
            $output->writeln('<error>Navigating schema update failed: '.$exception->getMessage().'</error>');

            return Command::FAILURE;
        } finally {
            $configuration->setSchemaAssetsFilter($previousFilter);
        }

        $output->writeln('<info>Navigating schema synchronized successfully.</info>');
        $output->writeln('<comment>Only navigation_menu and navigation_item were visible to Doctrine schema comparison.</comment>');

        return Command::SUCCESS;
    }
}
