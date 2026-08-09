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

    /**
     * Loads the complete enabled navigation inventory in one ORM query.
     *
     * Items are intentionally not filtered by enabled/archive state here: the runtime
     * projection needs the complete parent chain to determine effective visibility.
     * Parent associations are fetch-joined as well so ancestor inspection cannot
     * trigger one query per parent proxy on a cold cache.
     *
     * @return list<NavigationMenu>
     */
    public function findEnabled(): array
    {
        return $this->createQueryBuilder('menu')
            ->addSelect('item', 'parent')
            ->leftJoin('menu.items', 'item')
            ->leftJoin('item.parent', 'parent')
            ->andWhere('menu.enabled = :enabled')
            ->setParameter('enabled', true)
            ->orderBy('menu.priority', 'ASC')
            ->addOrderBy('menu.id', 'ASC')
            ->addOrderBy('item.position', 'ASC')
            ->addOrderBy('item.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }
}
