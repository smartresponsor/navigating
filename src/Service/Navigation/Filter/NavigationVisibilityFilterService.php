<?php

declare(strict_types=1);

namespace App\Navigating\Service\Navigation\Filter;

use App\Navigating\ServiceInterface\Navigation\Provide\NavigationRequestRoleProvideServiceInterface;
use App\Navigating\ServiceInterface\Navigation\Provide\NavigationRuntimeActivationProvideServiceInterface;
use App\Navigating\Value\Navigation\NavigationShellGroup;
use App\Navigating\Value\Navigation\NavigationShellItem;
use Symfony\Component\HttpFoundation\Request;

final readonly class NavigationVisibilityFilterService implements \App\Navigating\ServiceInterface\Navigation\Filter\NavigationVisibilityFilterServiceInterface
{
    /** @param array<string, mixed> $navigationConfig */
    public function __construct(
        private NavigationRequestRoleProvideServiceInterface $roleProvider,
        private NavigationRuntimeActivationProvideServiceInterface $runtimeActivationProvider,
        private array $navigationConfig = [],
    ) {
    }

    /**
     * @param list<NavigationShellGroup> $groups
     *
     * @return list<NavigationShellGroup>
     */
    public function filterShellGroups(array $groups, Request $request): array
    {
        $roles = $this->roleProvider->provideRoles($request);
        $scopes = $this->provideScopes($request);
        $environment = $this->provideEnvironment($request);
        $runtimeActivation = $this->runtimeActivationProvider->provide();
        $visibleGroups = [];

        foreach ($groups as $group) {
            if (!$this->isShellGroupVisible($group, $roles, $scopes, $environment)) {
                continue;
            }

            $itemsByKey = [];
            $locallyVisibleItems = [];

            foreach ($group->items as $item) {
                $itemsByKey[$item->key] = $item;

                if (!$item->enabled || !$item->visible) {
                    continue;
                }

                if (null === $item->runtimeScope || !$runtimeActivation->allowsScope([$item->runtimeScope])) {
                    continue;
                }

                if (!$this->hasAnyRequiredRole($item->visibleForRoles, $roles)) {
                    continue;
                }

                if (!$this->hasAnyRequiredScope($item->visibleForScopes, $scopes)) {
                    continue;
                }

                if (!$this->matchesEnvironment($item->visibleForEnvironments, $environment)) {
                    continue;
                }

                $locallyVisibleItems[$item->key] = $item;
            }

            $visibleItems = [];
            foreach ($locallyVisibleItems as $item) {
                if ($this->hasVisibleAncestorChain($item, $itemsByKey, $locallyVisibleItems)) {
                    $visibleItems[] = $item;
                }
            }

            $visibleGroups[] = new NavigationShellGroup(
                key: $group->key,
                label: $group->label,
                priority: $group->priority,
                enabled: $group->enabled,
                visible: $group->visible,
                visibleForRoles: $group->visibleForRoles,
                visibleForScopes: $group->visibleForScopes,
                visibleForEnvironments: $group->visibleForEnvironments,
                location: $group->location,
                type: $group->type,
                items: $visibleItems,
            );
        }

        return $visibleGroups;
    }

    /**
     * @param array<string, NavigationShellItem> $itemsByKey
     * @param array<string, NavigationShellItem> $locallyVisibleItems
     */
    private function hasVisibleAncestorChain(NavigationShellItem $item, array $itemsByKey, array $locallyVisibleItems): bool
    {
        $visited = [$item->key => true];
        $cursor = $item;

        while (true) {
            $parentKey = $cursor->metadata['parent_key'] ?? null;
            if (null === $parentKey || '' === $parentKey) {
                return true;
            }

            if (!is_string($parentKey) || isset($visited[$parentKey])) {
                return false;
            }

            $parent = $itemsByKey[$parentKey] ?? null;
            if (!$parent instanceof NavigationShellItem || !isset($locallyVisibleItems[$parentKey])) {
                return false;
            }

            $visited[$parentKey] = true;
            $cursor = $parent;
        }
    }

    /**
     * @param list<string> $roles
     * @param list<string> $scopes
     */
    public function isShellGroupVisible(NavigationShellGroup $group, array $roles, array $scopes = [], ?string $environment = null): bool
    {
        return $group->enabled
            && $group->visible
            && $this->hasAnyRequiredRole($group->visibleForRoles, $roles)
            && $this->hasAnyRequiredScope($group->visibleForScopes, $scopes)
            && $this->matchesEnvironment($group->visibleForEnvironments, $environment);
    }

    /**
     * @param list<string> $requiredRoles
     * @param list<string> $roles
     */
    private function hasAnyRequiredRole(array $requiredRoles, array $roles): bool
    {
        if ([] === $requiredRoles) {
            return true;
        }

        $roleMap = array_fill_keys($roles, true);

        foreach ($requiredRoles as $requiredRole) {
            if (isset($roleMap[$requiredRole])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<string> $requiredScopes
     * @param list<string> $scopes
     */
    private function hasAnyRequiredScope(array $requiredScopes, array $scopes): bool
    {
        if ([] === $requiredScopes) {
            return true;
        }

        $scopeMap = array_fill_keys($scopes, true);

        foreach ($requiredScopes as $requiredScope) {
            if (isset($scopeMap[$requiredScope])) {
                return true;
            }
        }

        return false;
    }

    /** @param list<string> $requiredEnvironments */
    private function matchesEnvironment(array $requiredEnvironments, ?string $environment): bool
    {
        if ([] === $requiredEnvironments) {
            return true;
        }

        return null !== $environment && in_array($environment, $requiredEnvironments, true);
    }

    /** @return list<string> */
    private function provideScopes(Request $request): array
    {
        $requestScopes = $request->attributes->get('_navigation_scopes', $request->attributes->get('navigation_scopes'));

        if (is_string($requestScopes)) {
            $requestScopes = preg_split('/[,\s]+/', $requestScopes) ?: [];
        }

        if (!is_array($requestScopes) || [] === $requestScopes) {
            $requestScopes = $this->navigationConfig['runtime_scopes']['fallback_scopes'] ?? [];
        }

        $normalized = [];

        foreach ($requestScopes as $scope) {
            if (!is_string($scope)) {
                continue;
            }

            $scope = strtolower(trim($scope));

            if ('' !== $scope) {
                $normalized[$scope] = $scope;
            }
        }

        return array_values($normalized);
    }

    private function provideEnvironment(Request $request): ?string
    {
        $environment = $request->attributes->get('_navigation_environment', $request->attributes->get('navigation_environment'));

        if (!is_string($environment) || '' === trim($environment)) {
            $environment = $this->navigationConfig['runtime_environment']['fallback_environment'] ?? null;
        }

        if (!is_string($environment) || '' === trim($environment)) {
            return null;
        }

        return strtolower(trim($environment));
    }
}
