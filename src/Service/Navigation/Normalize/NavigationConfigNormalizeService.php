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
            /** @var array<string, mixed> $groupConfig */
            $items = [];
            $itemsConfig = $groupConfig['items'] ?? [];
            $groupMetadata = $this->objectValue($groupConfig['metadata'] ?? null, 'navigation group metadata');
            $groupNamespaceProvider = $this->firstString(
                $groupConfig['namespace_provider'] ?? null,
                $groupMetadata['namespace_provider'] ?? null,
            );
            $groupNamespace = $this->firstString(
                $groupConfig['namespace'] ?? null,
                $groupMetadata['namespace'] ?? null,
            );

            if (is_array($itemsConfig)) {
                foreach ($itemsConfig as $itemKey => $itemConfig) {
                    if (!is_string($itemKey) || !is_array($itemConfig)) {
                        continue;
                    }
                    /** @var array<string, mixed> $itemConfig */
                    $type = $this->stringValue($itemConfig['type'] ?? null, '');
                    $targetConfig = $this->targetConfig($itemConfig);
                    $metadata = $this->objectValue($itemConfig['metadata'] ?? null, 'navigation item metadata');
                    $namespaceProvider = $this->firstString(
                        $itemConfig['namespace_provider'] ?? null,
                        $metadata['namespace_provider'] ?? null,
                        $groupNamespaceProvider,
                    );
                    $namespace = $this->firstString(
                        $itemConfig['namespace'] ?? null,
                        $metadata['namespace'] ?? null,
                        $groupNamespace,
                    );

                    $items[] = new NavigationShellItem(
                        key: $itemKey,
                        type: $type,
                        label: $this->stringValue($itemConfig['label'] ?? null, $itemKey),
                        priority: $this->intValue($itemConfig['priority'] ?? null, 100),
                        enabled: (bool) ($itemConfig['enabled'] ?? true),
                        visible: (bool) ($itemConfig['visible'] ?? true),
                        visibleForRoles: $this->roleList($itemConfig['visible_for_roles'] ?? []),
                        visibleForScopes: $this->tokenList($itemConfig['visible_for_scopes'] ?? []),
                        visibleForEnvironments: $this->tokenList($itemConfig['visible_for_environments'] ?? []),
                        target: null === $targetConfig ? null : NavigationTarget::fromArray($targetConfig),
                        action: $this->nullableString($itemConfig['action'] ?? null),
                        widget: $this->nullableString($itemConfig['widget'] ?? null),
                        icon: $this->nullableString($itemConfig['icon'] ?? null),
                        badge: $this->nullableString($itemConfig['badge'] ?? null),
                        metadata: $metadata,
                        namespaceProvider: $namespaceProvider,
                        namespace: $namespace,
                        runtimeScope: $this->runtimeScope($namespaceProvider ?? $namespace),
                    );
                }
            }

            usort($items, static fn (NavigationShellItem $a, NavigationShellItem $b): int => $a->priority <=> $b->priority);

            $groups[] = new NavigationShellGroup(
                key: $key,
                label: $this->stringValue($groupConfig['label'] ?? null, $key),
                priority: $this->intValue($groupConfig['priority'] ?? null, 100),
                enabled: (bool) ($groupConfig['enabled'] ?? true),
                visible: (bool) ($groupConfig['visible'] ?? true),
                visibleForRoles: $this->roleList($groupConfig['visible_for_roles'] ?? []),
                visibleForScopes: $this->tokenList($groupConfig['visible_for_scopes'] ?? []),
                visibleForEnvironments: $this->tokenList($groupConfig['visible_for_environments'] ?? []),
                location: $this->stringValue($groupConfig['location'] ?? null, sprintf('shell.%s', str_replace('_', '.', $key))),
                type: $this->stringValue($groupConfig['type'] ?? null, 'navigation'),
                items: $items,
            );
        }

        usort($groups, static fn (NavigationShellGroup $a, NavigationShellGroup $b): int => $a->priority <=> $b->priority);

        return $groups;
    }

    /** @return list<string> */
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

    /** @return list<string> */
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

    private function runtimeScope(?string $namespace): ?string
    {
        if (null === $namespace) {
            return null;
        }

        $namespace = trim($namespace, ' \\t\\n\\r\\0\\x0B\\\\');
        if ('' === $namespace) {
            return null;
        }

        $segments = preg_split('/\\\\+/', $namespace) ?: [];
        $segments = array_values(array_filter(array_map('trim', $segments), static fn (string $segment): bool => '' !== $segment));

        if ([] === $segments) {
            return null;
        }

        if ('app' === strtolower($segments[0])) {
            return isset($segments[1]) ? strtolower($segments[1]) : null;
        }

        return strtolower($segments[0]);
    }

    private function firstString(mixed ...$candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (is_string($candidate) && '' !== trim($candidate)) {
                return trim($candidate);
            }
        }

        return null;
    }

    private function stringValue(mixed $value, string $fallback): string
    {
        return is_string($value) && '' !== trim($value) ? trim($value) : $fallback;
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) && '' !== trim($value) ? trim($value) : null;
    }

    private function intValue(mixed $value, int $fallback): int
    {
        if (null === $value) {
            return $fallback;
        }
        if (is_int($value)) {
            return $value;
        }
        if (is_string($value) && preg_match('/^-?\\d+$/', trim($value))) {
            return (int) trim($value);
        }

        throw new \InvalidArgumentException('Navigation numeric configuration values must be integers.');
    }

    /** @return array<string, mixed> */
    private function objectValue(mixed $value, string $field): array
    {
        if (null === $value) {
            return [];
        }
        if (!is_array($value) || ([] !== $value && array_is_list($value))) {
            throw new \InvalidArgumentException(sprintf('%s must be an object-shaped map.', ucfirst($field)));
        }

        /** @var array<string, mixed> $value */
        return $value;
    }

    /**
     * @param array<string, mixed> $nodeConfig
     *
     * @return array<string, mixed>|null
     */
    private function targetConfig(array $nodeConfig): ?array
    {
        $type = $this->stringValue($nodeConfig['type'] ?? null, '');
        $target = $this->objectValue($nodeConfig['target'] ?? null, 'navigation target');

        if ([] !== $target) {
            return $target;
        }

        if (NavigationShellItemTypeRegistry::LINK === $type) {
            if ($this->hasConfiguredValue($nodeConfig, 'route')) {
                $route = $this->nullableString($nodeConfig['route'] ?? null);
                if (null !== $route) {
                    return ['type' => 'route', 'route' => $route];
                }
            }

            if ($this->hasConfiguredValue($nodeConfig, 'path')) {
                $path = $this->nullableString($nodeConfig['path'] ?? null);
                if (null !== $path) {
                    return ['type' => 'path', 'path' => $path];
                }
            }
        }

        return null;
    }

    /** @param array<string, mixed> $config */
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
