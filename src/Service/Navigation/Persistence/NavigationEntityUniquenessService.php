<?php

declare(strict_types=1);

namespace App\Navigating\Service\Navigation\Persistence;

use App\Navigating\Entity\NavigationItem;
use App\Navigating\Entity\NavigationMenu;
use App\Navigating\Repository\NavigationItemRepository;
use App\Navigating\Repository\NavigationMenuRepository;

final readonly class NavigationEntityUniquenessService
{
    public function __construct(
        private NavigationMenuRepository $menuRepository,
        private NavigationItemRepository $itemRepository,
    ) {
    }

    public function validate(object $entity): void
    {
        if ($entity instanceof NavigationMenu) {
            if ($this->menuRepository->existsOtherWithMenuKey($entity->getMenuKey(), $entity->getId())) {
                throw new \DomainException(sprintf('Navigation menu key "%s" is already in use.', $entity->getMenuKey()));
            }

            if ($this->menuRepository->existsOtherWithSlug($entity->getSlug(), $entity->getId())) {
                throw new \DomainException(sprintf('Navigation menu slug "%s" is already in use.', $entity->getSlug()));
            }

            return;
        }

        if (!$entity instanceof NavigationItem) {
            return;
        }

        $menu = $entity->getMenu();
        if (null === $menu) {
            return;
        }

        if ($this->itemRepository->existsOtherWithNavigationKey($menu, $entity->getNavigationKey(), $entity->getId())) {
            throw new \DomainException(sprintf(
                'Navigation item key "%s" is already in use in menu "%s".',
                $entity->getNavigationKey(),
                $menu->getMenuKey(),
            ));
        }

        $slug = $entity->getSlug();
        if (null !== $slug && $this->itemRepository->existsOtherWithSlug($menu, $slug, $entity->getId())) {
            throw new \DomainException(sprintf(
                'Navigation item slug "%s" is already in use in menu "%s".',
                $slug,
                $menu->getMenuKey(),
            ));
        }
    }
}
