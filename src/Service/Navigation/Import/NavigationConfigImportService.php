<?php

declare(strict_types=1);

namespace App\Navigating\Service\Navigation\Import;

use App\Navigating\Entity\NavigationItem;
use App\Navigating\Entity\NavigationMenu;
use App\Navigating\Repository\NavigationMenuRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class NavigationConfigImportService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private NavigationMenuRepository $menuRepository,
    ) {
    }

    /** @param array<string, mixed> $config */
    public function replaceFromConfig(array $config, bool $requireEmpty = false): int
    {
        $groups = $config['shell_groups'] ?? null;
        if (!is_array($groups) || [] === $groups) {
            throw new \InvalidArgumentException('No shell_groups are available in navigation configuration.');
        }

        if ($requireEmpty && [] !== $this->menuRepository->findAll()) {
            throw new \RuntimeException('Navigation database is not empty.');
        }

        $this->entityManager->wrapInTransaction(function () use ($groups): void {
            foreach ($this->menuRepository->findAll() as $existingMenu) {
                $this->entityManager->remove($existingMenu);
            }
            $this->entityManager->flush();

            foreach ($groups as $menuKey => $groupConfig) {
                if (is_string($menuKey) && is_array($groupConfig)) {
                    $this->importGroup($menuKey, $groupConfig);
                }
            }

            $this->entityManager->flush();
        });

        return count($groups);
    }

    /** @param array<string, mixed> $groupConfig */
    private function importGroup(string $menuKey, array $groupConfig): void
    {
        $menuSlug = $this->stringValue($groupConfig['slug'] ?? null, $this->slugify($menuKey));
        $menu = (new NavigationMenu())
            ->setMenuKey($menuKey)
            ->setSlug($menuSlug)
            ->setLabel($this->stringValue($groupConfig['label'] ?? null, $menuKey))
            ->setLocation($this->stringValue($groupConfig['location'] ?? null, 'shell.context.middle'))
            ->setType($this->stringValue($groupConfig['type'] ?? null, 'navigation'))
            ->setVisibleForRoles($this->stringList($groupConfig['visible_for_roles'] ?? []))
            ->setVisibleForScopes($this->stringList($groupConfig['visible_for_scopes'] ?? []))
            ->setVisibleForEnvironments($this->stringList($groupConfig['visible_for_environments'] ?? []))
            ->setPriority((int) ($groupConfig['priority'] ?? 100))
            ->setEnabled((bool) ($groupConfig['enabled'] ?? true))
            ->setMetadata(is_array($groupConfig['metadata'] ?? null) ? $groupConfig['metadata'] : []);

        $this->entityManager->persist($menu);

        $itemsConfig = $groupConfig['items'] ?? [];
        if (!is_array($itemsConfig)) {
            return;
        }

        $items = [];
        $parentKeys = [];

        foreach ($itemsConfig as $itemKey => $itemConfig) {
            if (!is_string($itemKey) || !is_array($itemConfig)) {
                continue;
            }

            $metadata = is_array($itemConfig['metadata'] ?? null) ? $itemConfig['metadata'] : [];
            $item = (new NavigationItem())
                ->setNavigationKey($itemKey)
                ->setSlug($this->nullableString($itemConfig['slug'] ?? null) ?? $menuSlug.'-'.$this->slugify($itemKey))
                ->setLabel($this->stringValue($itemConfig['label'] ?? null, $itemKey))
                ->setType($this->stringValue($itemConfig['type'] ?? null, 'link'))
                ->setOperation($this->stringValue($metadata['operation'] ?? $itemConfig['operation'] ?? null, 'index'))
                ->setIcon($this->nullableString($itemConfig['icon'] ?? null))
                ->setBadge($this->nullableString($itemConfig['badge'] ?? null))
                ->setVisibleForRoles($this->stringList($itemConfig['visible_for_roles'] ?? []))
                ->setVisibleForScopes($this->stringList($itemConfig['visible_for_scopes'] ?? []))
                ->setVisibleForEnvironments($this->stringList($itemConfig['visible_for_environments'] ?? []))
                ->setPosition((int) ($itemConfig['priority'] ?? 100))
                ->setEnabled((bool) ($itemConfig['enabled'] ?? true))
                ->setMetadata($metadata);

            $this->applyTarget($item, $itemConfig);
            $menu->addItem($item);
            $this->entityManager->persist($item);
            $items[$itemKey] = $item;

            $parentKey = $metadata['parent_key'] ?? $itemConfig['parent_key'] ?? null;
            if (is_string($parentKey) && '' !== trim($parentKey)) {
                $parentKeys[$itemKey] = trim($parentKey);
            }
        }

        foreach ($parentKeys as $itemKey => $parentKey) {
            $parent = $items[$parentKey] ?? null;
            if (!$parent instanceof NavigationItem) {
                throw new \InvalidArgumentException(sprintf('Navigation item "%s" in menu "%s" references missing parent "%s".', $itemKey, $menuKey, $parentKey));
            }
            $items[$itemKey]->setParent($parent);
        }
    }

    /** @param array<string, mixed> $itemConfig */
    private function applyTarget(NavigationItem $item, array $itemConfig): void
    {
        $target = is_array($itemConfig['target'] ?? null) ? $itemConfig['target'] : [];
        $targetType = $this->nullableString($target['type'] ?? null);

        if ('route' === $targetType) {
            $item->setRouteName($this->nullableString($target['route'] ?? null));
            $item->setRouteParameters(is_array($target['params'] ?? null) ? $target['params'] : []);
            $item->setPath(null);
            return;
        }
        if ('path' === $targetType) {
            $item->setRouteName(null)->setRouteParameters([])->setPath($this->nullableString($target['path'] ?? null));
            return;
        }

        $route = $this->nullableString($itemConfig['route'] ?? null);
        if (null !== $route) {
            $item->setRouteName($route)
                ->setRouteParameters(is_array($itemConfig['params'] ?? null) ? $itemConfig['params'] : [])
                ->setPath(null);
            return;
        }

        $item->setRouteName(null)->setRouteParameters([])->setPath($this->nullableString($itemConfig['path'] ?? null));
    }

    private function slugify(string $value): string
    {
        $value = preg_replace('/[^a-z0-9]+/', '-', strtolower(trim($value))) ?? '';
        return trim($value, '-');
    }

    private function stringValue(mixed $value, string $fallback): string
    {
        return is_string($value) && '' !== trim($value) ? trim($value) : $fallback;
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) && '' !== trim($value) ? trim($value) : null;
    }

    /** @return list<string> */
    private function stringList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }
        $values = [];
        foreach ($value as $item) {
            if (is_string($item) && '' !== trim($item)) {
                $values[] = trim($item);
            }
        }
        return $values;
    }
}
