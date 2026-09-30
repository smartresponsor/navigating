<?php

declare(strict_types=1);

namespace App\Navigating\Repository;

use App\Navigating\Entity\NavigationItemEntity;
use App\Navigating\Entity\NavigationMenuEntity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<NavigationItemEntity> */
final class NavigationItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NavigationItemEntity::class);
    }

    public function findOneByMenuAndSlug(NavigationMenuEntity $menu, string $slug): ?NavigationItemEntity
    {
        /** @var NavigationItemEntity|null $item */
        $item = $this->findOneBy([
            'menu' => $menu,
            'slug' => trim($slug),
        ]);

        return $item;
    }

    public function findOneByMenuIdOrSlug(NavigationMenuEntity $menu, int|string $identifier): ?NavigationItemEntity
    {
        if (is_int($identifier) || ctype_digit($identifier)) {
            /** @var NavigationItemEntity|null $item */
            $item = $this->findOneBy([
                'id' => (int) $identifier,
                'menu' => $menu,
            ]);

            return $item;
        }

        return $this->findOneByMenuAndSlug($menu, $identifier);
    }

    public function existsOtherWithNavigationKey(NavigationMenuEntity $menu, string $navigationKey, ?int $excludeId = null): bool
    {
        return $this->existsOtherByField($menu, 'navigationKey', trim($navigationKey), $excludeId);
    }

    public function existsOtherWithSlug(NavigationMenuEntity $menu, string $slug, ?int $excludeId = null): bool
    {
        return $this->existsOtherByField($menu, 'slug', trim($slug), $excludeId);
    }

    /** @return list<NavigationItemEntity> */
    public function findEnabledByMenu(NavigationMenuEntity $menu): array
    {
        /** @var list<NavigationItemEntity> $items */
        $items = $this->createQueryBuilder('item')
            ->andWhere('item.menu = :menu')
            ->andWhere('item.enabled = :enabled')
            ->andWhere('item.archivedAt IS NULL')
            ->setParameter('menu', $menu)
            ->setParameter('enabled', true)
            ->orderBy('item.position', 'ASC')
            ->addOrderBy('item.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return $items;
    }

    /** @return list<NavigationItemEntity> */
    public function findEnabledByLocation(string $location): array
    {
        /** @var list<NavigationItemEntity> $items */
        $items = $this->createQueryBuilder('item')
            ->innerJoin('item.menu', 'menu')
            ->andWhere('menu.enabled = :enabled')
            ->andWhere('menu.location = :location')
            ->andWhere('item.enabled = :enabled')
            ->andWhere('item.archivedAt IS NULL')
            ->setParameter('enabled', true)
            ->setParameter('location', trim($location))
            ->orderBy('menu.priority', 'ASC')
            ->addOrderBy('item.position', 'ASC')
            ->addOrderBy('item.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return $items;
    }

    private function existsOtherByField(NavigationMenuEntity $menu, string $field, string $value, ?int $excludeId): bool
    {
        $queryBuilder = $this->createQueryBuilder('item')
            ->select('COUNT(item.id)')
            ->andWhere('item.menu = :menu')
            ->andWhere(sprintf('item.%s = :value', $field))
            ->setParameter('menu', $menu)
            ->setParameter('value', $value)
        ;

        if (null !== $excludeId) {
            $queryBuilder
                ->andWhere('item.id <> :excludeId')
                ->setParameter('excludeId', $excludeId)
            ;
        }

        return (int) $queryBuilder->getQuery()->getSingleScalarResult() > 0;
    }
}
