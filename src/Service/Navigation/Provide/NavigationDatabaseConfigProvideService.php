<?php

declare(strict_types=1);

namespace App\Navigating\Service\Navigation\Provide;

use App\Navigating\Entity\NavigationItem;
use App\Navigating\Repository\NavigationMenuRepository;
use App\Navigating\ServiceInterface\Navigation\Provide\NavigationDatabaseConfigProvideServiceInterface;

final readonly class NavigationDatabaseConfigProvideService implements NavigationDatabaseConfigProvideServiceInterface
{
    public function __construct(
        private NavigationMenuRepository $menuRepository,
    ) {
    }

    public function provideConfig(): array
    {
        $groups = [];

        foreach ($this->menuRepository->findEnabled() as $menu) {
            $items = [];

            foreach ($menu->getItems() as $item) {
                if (!$item->isEnabled() || $item->isArchived()) {
                    continue;
                }

                $items[$item->getNavigationKey()] = $this->itemConfig($item);
            }

            $groups[$menu->getMenuKey()] = [
                'label' => $menu->getLabel(),
                'location' => $menu->getLocation(),
                'type' => $menu->getType(),
                'priority' => $menu->getPriority(),
                'enabled' => $menu->isEnabled(),
                'visible' => true,
                'items' => $items,
            ];
        }

        return [] === $groups ? [] : ['shell_groups' => $groups];
    }

    /** @return array<string, mixed> */
    private function itemConfig(NavigationItem $item): array
    {
        $metadata = $item->getMetadata();

        if (null !== $item->getParent()) {
            $metadata['parent_key'] = $item->getParent()?->getNavigationKey();
        }

        $config = [
            'type' => $item->getType(),
            'label' => $item->getLabel(),
            'priority' => $item->getPosition(),
            'enabled' => $item->isEnabled(),
            'visible' => true,
            'visible_for_roles' => $item->getVisibleForRoles(),
            'visible_for_scopes' => $item->getVisibleForScopes(),
            'visible_for_environments' => $item->getVisibleForEnvironments(),
            'metadata' => $metadata,
        ];

        if (null !== $item->getIcon()) {
            $config['icon'] = $item->getIcon();
        }

        if (null !== $item->getBadge()) {
            $config['badge'] = $item->getBadge();
        }

        if (null !== $item->getRouteName()) {
            $config['target'] = [
                'type' => 'route',
                'route' => $item->getRouteName(),
                'params' => $item->getRouteParameters(),
            ];
        } elseif (null !== $item->getPath()) {
            $config['target'] = [
                'type' => 'path',
                'path' => $item->getPath(),
            ];
        }

        return $config;
    }
}
