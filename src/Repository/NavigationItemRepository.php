<?php

declare(strict_types=1);

namespace App\Navigating\Repository;

use App\Navigating\Entity\NavigationItem;
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
        $item = $this->findOneBy(['slug' => $slug]);

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
    public function findEnabledByLocation(string $location): array
    {
        return $this->createQueryBuilder('item')
            ->andWhere('item.enabled = :enabled')
            ->andWhere('item.location = :location')
            ->setParameter('enabled', true)
            ->setParameter('location', $location)
            ->orderBy('item.position', 'ASC')
            ->addOrderBy('item.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
