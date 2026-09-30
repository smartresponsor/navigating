<?php

declare(strict_types=1);

namespace App\Navigating\Repository;

use App\Navigating\Entity\NavigationItemEntity;
use App\Navigating\Entity\NavigationMenuEntity;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools\SchemaTool;

final readonly class NavigationPersistenceRepository
{
    private const OWNED_TABLES = [
        'navigation_menu' => true,
        'navigation_item' => true,
    ];

    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function connection(): Connection
    {
        return $this->entityManager->getConnection();
    }

    public function persist(object $entity): void
    {
        $this->entityManager->persist($entity);
    }

    public function remove(object $entity): void
    {
        $this->entityManager->remove($entity);
    }

    public function flush(): void
    {
        $this->entityManager->flush();
    }

    public function clear(): void
    {
        $this->entityManager->clear();
    }

    public function hasOpenTransaction(): bool
    {
        return $this->connection()->getTransactionNestingLevel() > 0;
    }

    public function assertOptimisticVersion(object $entity, int $expectedVersion): void
    {
        $this->entityManager->lock($entity, \Doctrine\DBAL\LockMode::OPTIMISTIC, $expectedVersion);
    }

    /**
     * @template T
     *
     * @param callable(): T $operation
     *
     * @return T
     */
    public function transactional(callable $operation): mixed
    {
        return $this->entityManager->wrapInTransaction($operation);
    }

    public function synchronizeOwnedSchema(): bool
    {
        $connection = $this->connection();
        $configuration = $connection->getConfiguration();
        $previousFilter = $configuration->getSchemaAssetsFilter();

        $configuration->setSchemaAssetsFilter(
            static function (mixed $asset): bool {
                if (is_string($asset)) {
                    return isset(self::OWNED_TABLES[$asset]);
                }
                if (!is_object($asset)) {
                    return false;
                }

                $name = method_exists($asset, 'getName') ? $asset->getName() : null;

                return is_string($name) && isset(self::OWNED_TABLES[$name]);
            },
        );

        try {
            $schemaTool = new SchemaTool($this->entityManager);
            $metadata = $this->ownedMetadata();
            $sql = $schemaTool->getUpdateSchemaSql($metadata);
            if ([] === $sql) {
                return false;
            }

            $schemaTool->updateSchema($metadata);

            return true;
        } finally {
            $configuration->setSchemaAssetsFilter($previousFilter);
        }
    }

    public function rebuildOwnedSchema(): void
    {
        $schemaTool = new SchemaTool($this->entityManager);
        $metadata = $this->ownedMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    public function createOwnedSchema(): void
    {
        (new SchemaTool($this->entityManager))->createSchema($this->ownedMetadata());
    }

    public function dropOwnedSchema(): void
    {
        (new SchemaTool($this->entityManager))->dropSchema($this->ownedMetadata());
    }

    /** @return list<ClassMetadata<object>> */
    private function ownedMetadata(): array
    {
        return [
            $this->entityManager->getClassMetadata(NavigationMenuEntity::class),
            $this->entityManager->getClassMetadata(NavigationItemEntity::class),
        ];
    }
}
