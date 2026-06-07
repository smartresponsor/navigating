<?php

declare(strict_types=1);

namespace App\Navigating\Service\Navigation\Normalize;

use App\Navigating\Value\Navigation\NavigationShellGroup;
use App\Navigating\Value\Navigation\NavigationShellItem;
use App\Navigating\Value\Navigation\NavigationShellItemTypeRegistry;
use App\Navigating\Value\Navigation\NavigationTarget;

final class NavigationConfigNormalizeService implements \App\Navigating\ServiceInterface\Navigation\Normalize\NavigationConfigNormalizeServiceInterface
{
    /**
     * @param array<string, mixed> $config
     *
     * @return list<NavigationShellGroup>
     */
    public function normalizeShellGroups(array $config): array
    {
        $groupsConfig = $config['shell_groups'] ?? [];

        if (!is_array($groupsConfig)) {
            throw new \InvalidArgumentException('Navigation config key "shell_groups" must be an array when configured.');
        }

        $groups = [];

        foreach ($groupsConfig as $key => $groupConfig) {
            if (!is_string($key) || !is_array($groupConfig)) {
                continue;
            }

            $items = [];
            $itemsConfig = $groupConfig['items'] ?? [];

            if (is_array($itemsConfig)) {
                foreach ($itemsConfig as $itemKey => $itemConfig) {
                    if (!is_string($itemKey) || !is_array($itemConfig)) {
                        continue;
                    }

                    $type = (string) ($itemConfig['type'] ?? '');
                    $targetConfig = $this->targetConfig($itemConfig);

                    $items[] = new NavigationShellItem(
                        key: $itemKey,
                        type: $type,
                        label: (string) ($itemConfig['label'] ?? $itemKey),
                        priority: (int) ($itemConfig['priority'] ?? 100),
                        enabled: (bool) ($itemConfig['enabled'] ?? true),
                        visible: (bool) ($itemConfig['visible'] ?? true),
                        visibleForRoles: $this->roleList($itemConfig['visible_for_roles'] ?? []),
                        visibleForScopes: $this->tokenList($itemConfig['visible_for_scopes'] ?? []),
                        visibleForEnvironments: $this->tokenList($itemConfig['visible_for_environments'] ?? []),
                        target: null === $targetConfig ? null : NavigationTarget::fromArray($targetConfig),
                        action: isset($itemConfig['action']) ? (string) $itemConfig['action'] : null,
                        widget: isset($itemConfig['widget']) ? (string) $itemConfig['widget'] : null,
                        icon: isset($itemConfig['icon']) ? (string) $itemConfig['icon'] : null,
                        badge: isset($itemConfig['badge']) ? (string) $itemConfig['badge'] : null,
                        metadata: is_array($itemConfig['metadata'] ?? null) ? $itemConfig['metadata'] : [],
                    );
                }
            }

            usort($items, static fn (NavigationShellItem $a, NavigationShellItem $b): int => $a->priority <=> $b->priority);

            $groups[] = new NavigationShellGroup(
                key: $key,
                label: (string) ($groupConfig['label'] ?? $key),
                priority: (int) ($groupConfig['priority'] ?? 100),
                enabled: (bool) ($groupConfig['enabled'] ?? true),
                visible: (bool) ($groupConfig['visible'] ?? true),
                visibleForRoles: $this->roleList($groupConfig['visible_for_roles'] ?? []),
                visibleForScopes: $this->tokenList($groupConfig['visible_for_scopes'] ?? []),
                visibleForEnvironments: $this->tokenList($groupConfig['visible_for_environments'] ?? []),
                location: (string) ($groupConfig['location'] ?? sprintf('shell.%s', str_replace('_', '.', $key))),
                type: (string) ($groupConfig['type'] ?? 'navigation'),
                items: $items,
            );
        }

        usort($groups, static fn (NavigationShellGroup $a, NavigationShellGroup $b): int => $a->priority <=> $b->priority);

        return $groups;
    }

    /**
     * @return list<string>
     */
    private function roleList(mixed $roles): array
    {
        if (!is_array($roles)) {
            return [];
        }

        $normalized = [];

        foreach ($roles as $role) {
            if (!is_string($role)) {
                continue;
            }

            $role = strtoupper(trim($role));

            if ('' !== $role) {
                $normalized[$role] = $role;
            }
        }

        return array_values($normalized);
    }

    /**
     * @return list<string>
     */
    private function tokenList(mixed $tokens): array
    {
        if (!is_array($tokens)) {
            return [];
        }

        $normalized = [];

        foreach ($tokens as $token) {
            if (!is_string($token)) {
                continue;
            }

            $token = strtolower(trim($token));

            if ('' !== $token) {
                $normalized[$token] = $token;
            }
        }

        return array_values($normalized);
    }

    /**
     * @param array<string, mixed> $nodeConfig
     *
     * @return array<string, mixed>|null
     */
    private function targetConfig(array $nodeConfig): ?array
    {
        $type = (string) ($nodeConfig['type'] ?? '');
        $target = $nodeConfig['target'] ?? null;

        if (is_array($target) && [] !== $target) {
            return $target;
        }

        if (NavigationShellItemTypeRegistry::LINK === $type) {
            if ($this->hasConfiguredValue($nodeConfig, 'route')) {
                return ['type' => 'route', 'route' => (string) $nodeConfig['route']];
            }

            if ($this->hasConfiguredValue($nodeConfig, 'path')) {
                return ['type' => 'path', 'path' => (string) $nodeConfig['path']];
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $config
     */
    private function hasConfiguredValue(array $config, string $key): bool
    {
        if (!array_key_exists($key, $config)) {
            return false;
        }

        $value = $config[$key];

        if (null === $value) {
            return false;
        }

        if (is_string($value)) {
            return '' !== trim($value);
        }

        if (is_array($value)) {
            return [] !== $value;
        }

        return true;
    }
}
