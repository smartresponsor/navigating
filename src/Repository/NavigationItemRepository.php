<?php

declare(strict_types=1);

namespace App\Navigating\Repository;

use App\Navigating\Entity\NavigationItem;
use App\Navigating\Entity\NavigationMenu;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<NavigationItem> */
final class NavigationItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NavigationItem::class);
    }

    public function findOneBySlug(string $slug): ?NavigationItem
    {
        /** @var NavigationItem|null $item */
        $item = $this->findOneBy(['slug' => trim($slug)]);

        return $item;
    }

    public function findOneByIdOrSlug(int|string $identifier): ?NavigationItem
    {
        if (is_int($identifier) || ctype_digit($identifier)) {
            /** @var NavigationItem|null $item */
            $item = $this->find((int) $identifier);

            return $item;
        }

        return $this->findOneBySlug($identifier);
    }

    /** @return list<NavigationItem> */
    public function findEnabledByMenu(NavigationMenu $menu): array
    {
        return $this->createQueryBuilder('item')
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
    }

    /** @return list<NavigationItem> */
    public function findEnabledByLocation(string $location): array
    {
        return $this->createQueryBuilder('item')
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
    }
}
