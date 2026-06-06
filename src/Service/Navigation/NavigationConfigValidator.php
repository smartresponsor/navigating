<?php

declare(strict_types=1);

namespace App\Navigating\Service\Navigation;

use App\Navigating\Value\Navigation\NavigationShellItemTypeRegistry;
use App\Navigating\Value\Navigation\NavigationShellLocationRegistry;
use App\Navigating\Value\Navigation\NavigationValidationResult;

final class NavigationConfigValidator
{
    /**
     * @param array<string, mixed> $config
     */
    public function validate(array $config): NavigationValidationResult
    {
        $errors = [];
        $warnings = [];

        if (($config['schema'] ?? null) !== 3) {
            $errors[] = 'navigation.schema must be 3.';
        }

        $this->validateRemovedKeys($config, $errors);
        $this->validateRuntimeRoles($config, $errors);
        $this->validateRuntimeScopes($config, $errors);
        $this->validateRuntimeEnvironment($config, $errors);
        $this->validateShellGroups($config, $errors);

        return new NavigationValidationResult($errors, $warnings);
    }

    /**
     * @param array<string, mixed> $config
     * @param list<string>         $errors
     */
    private function validateRemovedKeys(array $config, array &$errors): void
    {
        foreach ([
            'locations',
            'slots',
            'roots',
            'footer',
            'template',
            'templates',
            'template_path',
            'crud',
            'resources',
        ] as $removedKey) {
            if (array_key_exists($removedKey, $config)) {
                $errors[] = sprintf('navigation.%s has been removed; use navigation.shell_groups with canonical shell locations only.', $removedKey);
            }
        }
    }

    /**
     * @param array<string, mixed> $config
     * @param list<string>         $errors
     */
    private function validateShellGroups(array $config, array &$errors): void
    {
        $groups = $config['shell_groups'] ?? [];

        if (!is_array($groups)) {
            $errors[] = 'navigation.shell_groups must be a map when configured.';

            return;
        }

        foreach ($groups as $groupKey => $groupConfig) {
            if (!is_string($groupKey) || '' === trim($groupKey)) {
                $errors[] = 'navigation.shell_groups contains an empty or non-string group key.';
                continue;
            }

            if (!is_array($groupConfig)) {
                $errors[] = sprintf('navigation.shell_groups.%s must be a map.', $groupKey);
                continue;
            }

            $this->validateVisibilityNode(sprintf('navigation.shell_groups.%s', $groupKey), $groupConfig, $errors);

            $location = $groupConfig['location'] ?? null;
            if (!is_string($location) || '' === trim($location)) {
                $errors[] = sprintf('navigation.shell_groups.%s.location must be configured as a non-empty shell location string.', $groupKey);
            } else {
                $this->validateShellLocationValue(sprintf('navigation.shell_groups.%s.location', $groupKey), $location, $errors);
            }

            if (isset($groupConfig['type']) && (!is_string($groupConfig['type']) || '' === trim($groupConfig['type']))) {
                $errors[] = sprintf('navigation.shell_groups.%s.type must be a non-empty string when configured.', $groupKey);
            }

            $items = $groupConfig['items'] ?? [];
            if (!is_array($items)) {
                $errors[] = sprintf('navigation.shell_groups.%s.items must be a map when configured.', $groupKey);
                continue;
            }

            foreach ($items as $itemKey => $itemConfig) {
                if (!is_string($itemKey) || '' === trim($itemKey)) {
                    $errors[] = sprintf('navigation.shell_groups.%s.items contains an empty or non-string item key.', $groupKey);
                    continue;
                }

                if (!is_array($itemConfig)) {
                    $errors[] = sprintf('navigation.shell_groups.%s.items.%s must be a map.', $groupKey, $itemKey);
                    continue;
                }

                if (isset($itemConfig['items'], $itemConfig['sections'], $itemConfig['children'])) {
                    $errors[] = sprintf('navigation.shell_groups.%s.items.%s must not contain nested items/sections/children.', $groupKey, $itemKey);
                }

                $itemPath = sprintf('navigation.shell_groups.%s.items.%s', $groupKey, $itemKey);

                $this->validateVisibilityNode($itemPath, $itemConfig, $errors);
                $this->validateShellItemType($itemPath, $itemConfig, $errors);

                if (isset($itemConfig['metadata']) && !is_array($itemConfig['metadata'])) {
                    $errors[] = sprintf('%s.metadata must be a map when configured.', $itemPath);
                }
            }
        }
    }

    /**
     * @param list<string> $errors
     */
    private function validateShellLocationValue(string $path, string $location, array &$errors): void
    {
        $normalizedLocation = trim($location);

        if (!NavigationShellLocationRegistry::isCanonical($normalizedLocation)) {
            $errors[] = sprintf('%s must use a canonical shell location; got "%s". Allowed locations: %s.', $path, $normalizedLocation, NavigationShellLocationRegistry::canonicalListForMessage());
        }
    }

    /**
     * @param array<string, mixed> $itemConfig
     * @param list<string>         $errors
     */
    private function validateShellItemType(string $path, array $itemConfig, array &$errors): void
    {
        $type = $itemConfig['type'] ?? null;

        if (!is_string($type) || '' === trim($type)) {
            $errors[] = sprintf('%s.type must be configured as one of: %s.', $path, NavigationShellItemTypeRegistry::canonicalListForMessage());

            return;
        }

        $type = trim($type);

        if (!NavigationShellItemTypeRegistry::isCanonical($type)) {
            $errors[] = sprintf('%s.type must be one of: %s; got "%s".', $path, NavigationShellItemTypeRegistry::canonicalListForMessage(), $type);

            return;
        }

        foreach (array_keys($itemConfig) as $itemConfigKey) {
            if (!is_string($itemConfigKey)) {
                continue;
            }

            if (!in_array($itemConfigKey, ['type', 'label', 'priority', 'enabled', 'visible', 'visible_for_roles', 'visible_for_scopes', 'visible_for_environments', 'target', 'route', 'path', 'action', 'widget', 'icon', 'badge', 'metadata'], true)) {
                $errors[] = sprintf('%s.%s is not supported.', $path, $itemConfigKey);
            }
        }

        match ($type) {
            NavigationShellItemTypeRegistry::LINK => $this->validateLinkItem($path, $itemConfig, $errors),
            NavigationShellItemTypeRegistry::ACTION => $this->validateActionItem($path, $itemConfig, $errors),
            NavigationShellItemTypeRegistry::WIDGET => $this->validateWidgetItem($path, $itemConfig, $errors),
            NavigationShellItemTypeRegistry::BADGE => $this->validateBadgeItem($path, $itemConfig, $errors),
            NavigationShellItemTypeRegistry::HEADING,
            NavigationShellItemTypeRegistry::SEPARATOR => $this->validateTargetlessItem($path, $itemConfig, $errors),
            default => null,
        };
    }

    /**
     * @param array<string, mixed> $itemConfig
     * @param list<string>         $errors
     */
    private function validateLinkItem(string $path, array $itemConfig, array &$errors): void
    {
        $target = $this->extractTargetConfig($itemConfig);

        if (null === $target) {
            $errors[] = sprintf('%s link item must configure target, route, or path.', $path);

            return;
        }

        $this->validateNavigationTarget(sprintf('%s.target', $path), $target, $errors);
    }

    /**
     * @param array<string, mixed> $itemConfig
     * @param list<string>         $errors
     */
    private function validateActionItem(string $path, array $itemConfig, array &$errors): void
    {
        if (!isset($itemConfig['action']) || !is_string($itemConfig['action']) || '' === trim($itemConfig['action'])) {
            $errors[] = sprintf('%s action item must configure a non-empty action token.', $path);
        }

        $this->validateTargetlessItem($path, $itemConfig, $errors);
    }

    /**
     * @param array<string, mixed> $itemConfig
     * @param list<string>         $errors
     */
    private function validateWidgetItem(string $path, array $itemConfig, array &$errors): void
    {
        if (!isset($itemConfig['widget']) || !is_string($itemConfig['widget']) || '' === trim($itemConfig['widget'])) {
            $errors[] = sprintf('%s widget item must configure a non-empty widget token.', $path);
        }

        $this->validateTargetlessItem($path, $itemConfig, $errors);
    }

    /**
     * @param array<string, mixed> $itemConfig
     * @param list<string>         $errors
     */
    private function validateBadgeItem(string $path, array $itemConfig, array &$errors): void
    {
        if (!isset($itemConfig['badge']) || !is_string($itemConfig['badge']) || '' === trim($itemConfig['badge'])) {
            $errors[] = sprintf('%s badge item must configure a non-empty badge token.', $path);
        }

        $this->validateTargetlessItem($path, $itemConfig, $errors);
    }

    /**
     * @param array<string, mixed> $itemConfig
     * @param list<string>         $errors
     */
    private function validateTargetlessItem(string $path, array $itemConfig, array &$errors): void
    {
        foreach (['target', 'route', 'path'] as $targetKey) {
            if (!$this->hasConfiguredValue($itemConfig, $targetKey)) {
                continue;
            }

            $errors[] = sprintf('%s.%s is only supported for link items.', $path, $targetKey);
        }
    }

    /**
     * @param array<string, mixed> $itemConfig
     *
     * @return array<string, mixed>|null
     */
    private function extractTargetConfig(array $itemConfig): ?array
    {
        $target = $itemConfig['target'] ?? null;

        if (is_array($target) && [] !== $target) {
            return $target;
        }

        if ($this->hasConfiguredValue($itemConfig, 'route')) {
            return ['type' => 'route', 'route' => $itemConfig['route']];
        }

        if ($this->hasConfiguredValue($itemConfig, 'path')) {
            return ['type' => 'path', 'path' => $itemConfig['path']];
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

    /**
     * @param array<string, mixed> $config
     * @param list<string>         $errors
     */
    private function validateRuntimeRoles(array $config, array &$errors): void
    {
        $runtimeRoles = $config['runtime_roles'] ?? [];

        if (!is_array($runtimeRoles)) {
            $errors[] = 'navigation.runtime_roles must be a map when configured.';

            return;
        }

        if (!isset($runtimeRoles['fallback_roles'])) {
            return;
        }

        if (!is_array($runtimeRoles['fallback_roles'])) {
            $errors[] = 'navigation.runtime_roles.fallback_roles must be a list of role strings.';

            return;
        }

        foreach ($runtimeRoles['fallback_roles'] as $role) {
            if (!is_string($role) || '' === trim($role)) {
                $errors[] = 'navigation.runtime_roles.fallback_roles must contain only non-empty role strings.';

                return;
            }
        }
    }

    /**
     * @param array<string, mixed> $config
     * @param list<string>         $errors
     */
    private function validateRuntimeScopes(array $config, array &$errors): void
    {
        $runtimeScopes = $config['runtime_scopes'] ?? [];

        if (!is_array($runtimeScopes)) {
            $errors[] = 'navigation.runtime_scopes must be a map when configured.';

            return;
        }

        if (!isset($runtimeScopes['fallback_scopes'])) {
            return;
        }

        if (!is_array($runtimeScopes['fallback_scopes'])) {
            $errors[] = 'navigation.runtime_scopes.fallback_scopes must be a list of scope strings.';

            return;
        }

        foreach ($runtimeScopes['fallback_scopes'] as $scope) {
            if (!is_string($scope) || '' === trim($scope)) {
                $errors[] = 'navigation.runtime_scopes.fallback_scopes must contain only non-empty scope strings.';

                return;
            }
        }
    }

    /**
     * @param array<string, mixed> $config
     * @param list<string>         $errors
     */
    private function validateRuntimeEnvironment(array $config, array &$errors): void
    {
        $runtimeEnvironment = $config['runtime_environment'] ?? [];

        if (!is_array($runtimeEnvironment)) {
            $errors[] = 'navigation.runtime_environment must be a map when configured.';

            return;
        }

        if (isset($runtimeEnvironment['fallback_environment']) && (!is_string($runtimeEnvironment['fallback_environment']) || '' === trim($runtimeEnvironment['fallback_environment']))) {
            $errors[] = 'navigation.runtime_environment.fallback_environment must be a non-empty string when configured.';
        }
    }

    /**
     * @param array<string, mixed> $nodeConfig
     * @param list<string>         $errors
     */
    private function validateVisibilityNode(string $path, array $nodeConfig, array &$errors): void
    {
        if (isset($nodeConfig['visible']) && !is_bool($nodeConfig['visible'])) {
            $errors[] = sprintf('%s.visible must be boolean when configured.', $path);
        }

        $this->validateStringList($path, $nodeConfig, 'visible_for_roles', 'role', $errors);
        $this->validateStringList($path, $nodeConfig, 'visible_for_scopes', 'scope', $errors);
        $this->validateStringList($path, $nodeConfig, 'visible_for_environments', 'environment', $errors);
    }

    /**
     * @param array<string, mixed> $nodeConfig
     * @param list<string>         $errors
     */
    private function validateStringList(string $path, array $nodeConfig, string $key, string $label, array &$errors): void
    {
        if (!isset($nodeConfig[$key])) {
            return;
        }

        if (!is_array($nodeConfig[$key])) {
            $errors[] = sprintf('%s.%s must be a list of %s strings.', $path, $key, $label);

            return;
        }

        foreach ($nodeConfig[$key] as $value) {
            if (!is_string($value) || '' === trim($value)) {
                $errors[] = sprintf('%s.%s must contain only non-empty %s strings.', $path, $key, $label);

                return;
            }
        }
    }

    /**
     * @param array<string, mixed> $target
     * @param list<string>         $errors
     */
    private function validateNavigationTarget(string $path, array $target, array &$errors): void
    {
        $type = $target['type'] ?? null;

        if (!in_array($type, ['path', 'route'], true)) {
            $errors[] = sprintf('%s.type must be one of: path, route.', $path);

            return;
        }

        foreach (array_keys($target) as $targetKey) {
            if (!is_string($targetKey)) {
                continue;
            }

            if (!in_array($targetKey, ['type', 'path', 'route', 'name', 'params', 'query'], true)) {
                $errors[] = sprintf('%s.%s is not supported.', $path, $targetKey);
            }
        }

        if ('path' === $type && (!isset($target['path']) || !is_string($target['path']) || '' === trim($target['path']))) {
            $errors[] = sprintf('%s.path must be configured as a non-empty string.', $path);
        }

        if ('route' === $type) {
            foreach (['route', 'name'] as $routeKey) {
                if (isset($target[$routeKey]) && (!is_string($target[$routeKey]) || '' === trim($target[$routeKey]))) {
                    $errors[] = sprintf('%s.%s must be a non-empty string when configured.', $path, $routeKey);
                }
            }
        }

        foreach (['params', 'query'] as $mapKey) {
            if (isset($target[$mapKey]) && !is_array($target[$mapKey])) {
                $errors[] = sprintf('%s.%s must be a map when configured.', $path, $mapKey);
            }
        }

        foreach (['resource', 'action', 'target_resource', 'target_action'] as $forbiddenKey) {
            if (array_key_exists($forbiddenKey, $target)) {
                $errors[] = sprintf('%s.%s is forbidden; CRUD routing belongs to the Cruding component.', $path, $forbiddenKey);
            }
        }

        if ('route' === $type && !isset($target['route']) && !isset($target['name'])) {
            $errors[] = sprintf('%s route target must configure route or name.', $path);
        }
    }
}
