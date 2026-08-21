<?php

declare(strict_types=1);

namespace App\Navigating\Repository;

use App\Navigating\Entity\NavigationMenu;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
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

    public function existsOtherWithMenuKey(string $menuKey, ?int $excludeId = null): bool
    {
        return $this->existsOtherByField('menuKey', trim($menuKey), $excludeId);
    }

    public function existsOtherWithSlug(string $slug, ?int $excludeId = null): bool
    {
        return $this->existsOtherByField('slug', trim($slug), $excludeId);
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
        return $this->inventoryQueryBuilder()
            ->andWhere('menu.enabled = :enabled')
            ->setParameter('enabled', true)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Loads every menu/item/parent needed for a portable snapshot in one ORM query.
     * Disabled menus and disabled/archived items are intentionally retained.
     *
     * @return list<NavigationMenu>
     */
    public function findAllForSnapshot(): array
    {
        return $this->inventoryQueryBuilder()
            ->getQuery()
            ->getResult()
        ;
    }

    private function existsOtherByField(string $field, string $value, ?int $excludeId): bool
    {
        $queryBuilder = $this->createQueryBuilder('menu')
            ->select('COUNT(menu.id)')
            ->andWhere(sprintf('menu.%s = :value', $field))
            ->setParameter('value', $value)
        ;

        if (null !== $excludeId) {
            $queryBuilder
                ->andWhere('menu.id <> :excludeId')
                ->setParameter('excludeId', $excludeId)
            ;
        }

        return (int) $queryBuilder->getQuery()->getSingleScalarResult() > 0;
    }

    private function inventoryQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('menu')
            ->addSelect('item', 'parent')
            ->leftJoin('menu.items', 'item')
            ->leftJoin('item.parent', 'parent')
            ->orderBy('menu.priority', 'ASC')
            ->addOrderBy('menu.id', 'ASC')
            ->addOrderBy('item.position', 'ASC')
            ->addOrderBy('item.id', 'ASC')
        ;
    }
}
