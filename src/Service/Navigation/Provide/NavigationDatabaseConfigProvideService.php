<?php

declare(strict_types=1);

namespace App\Navigating\Service\Navigation\Provide;

use App\Navigating\Entity\NavigationItem;
use App\Navigating\Repository\NavigationMenuRepository;
use App\Navigating\Service\Navigation\Cache\NavigationConfigCacheService;
use App\Navigating\ServiceInterface\Navigation\Provide\NavigationDatabaseConfigProvideServiceInterface;

final readonly class NavigationDatabaseConfigProvideService implements NavigationDatabaseConfigProvideServiceInterface
{
    public function __construct(
        private NavigationMenuRepository $menuRepository,
        private NavigationConfigCacheService $cache,
    ) {
    }

    public function provideConfig(): array
    {
        return $this->cache->remember(fn (): array => $this->loadConfig());
    }

    /** @return array<string, mixed> */
    private function loadConfig(): array
    {
        $groups = [];

        foreach ($this->menuRepository->findEnabled() as $menu) {
            $items = [];

            foreach ($menu->getItems() as $item) {
                if (!$this->isEffectivelyEnabled($item)) {
                    continue;
                }

                $items[$item->getNavigationKey()] = $this->itemConfig($item);
            }

            $groups[$menu->getMenuKey()] = [
                'label' => $menu->getLabel(),
                'slug' => $menu->getSlug(),
                'location' => $menu->getLocation(),
                'type' => $menu->getType(),
                'priority' => $menu->getPriority(),
                'enabled' => $menu->isEnabled(),
                'visible' => true,
                'visible_for_roles' => $menu->getVisibleForRoles(),
                'visible_for_scopes' => $menu->getVisibleForScopes(),
                'visible_for_environments' => $menu->getVisibleForEnvironments(),
                'metadata' => $menu->getMetadata(),
                'items' => $items,
            ];
        }

        return [] === $groups ? [] : ['shell_groups' => $groups];
    }

    private function isEffectivelyEnabled(NavigationItem $item): bool
    {
        $cursor = $item;
        $visited = [];

        while (null !== $cursor) {
            $objectId = spl_object_id($cursor);
            if (isset($visited[$objectId])) {
                throw new \LogicException('Navigation item hierarchy contains a cycle.');
            }
            $visited[$objectId] = true;

            if (!$cursor->isEnabled() || $cursor->isArchived()) {
                return false;
            }

            $cursor = $cursor->getParent();
        }

        return true;
    }

    /** @return array<string, mixed> */
    private function itemConfig(NavigationItem $item): array
    {
        $metadata = $item->getMetadata();
        unset($metadata['parent_key']);

        if (null !== $item->getSlug()) {
            $metadata['slug'] ??= $item->getSlug();
        }
        $metadata['operation'] ??= $item->getOperation();

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

        if ('action' === $item->getType()) {
            $config['action'] = $this->actionToken($item, $metadata);
        }

        if ('widget' === $item->getType()) {
            $config['widget'] = $this->widgetToken($item, $metadata);
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

    /** @param array<string, mixed> $metadata */
    private function actionToken(NavigationItem $item, array $metadata): string
    {
        $toggle = $metadata['toggle'] ?? null;
        if (is_string($toggle) && '' !== trim($toggle)) {
            return 'navigation.toggle.'.trim($toggle);
        }

        $filter = $metadata['filter'] ?? null;
        if (is_string($filter) && '' !== trim($filter)) {
            return 'filter.'.trim($filter);
        }

        if ('index' !== $item->getOperation()) {
            return 'navigation.'.$item->getOperation();
        }

        return 'navigation.'.$item->getNavigationKey();
    }

    /** @param array<string, mixed> $metadata */
    private function widgetToken(NavigationItem $item, array $metadata): string
    {
        $tool = $metadata['tool'] ?? null;
        if (is_string($tool) && '' !== trim($tool)) {
            return in_array($tool, ['route_map', 'cruding_grammar'], true)
                ? 'platform.'.trim($tool)
                : 'shell.'.trim($tool);
        }

        return 'shell.'.$item->getNavigationKey();
    }
}
