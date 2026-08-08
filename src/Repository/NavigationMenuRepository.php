<?php

declare(strict_types=1);

namespace App\Navigating\Repository;

use App\Navigating\Entity\NavigationMenu;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<NavigationMenu> */
final class NavigationMenuRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NavigationMenu::class);
    }

    public function findOneBySlug(string $slug): ?NavigationMenu
    {
        /** @var NavigationMenu|null $menu */
        $menu = $this->findOneBy(['slug' => trim($slug)]);

        return $menu;
    }

    /** @return list<NavigationMenu> */
    public function findEnabled(): array
    {
        return $this->createQueryBuilder('menu')
            ->andWhere('menu.enabled = :enabled')
            ->setParameter('enabled', true)
            ->orderBy('menu.priority', 'ASC')
            ->addOrderBy('menu.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
