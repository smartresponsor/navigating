<?php

declare(strict_types=1);

namespace App\Navigating\ServiceInterface\Navigation\Filter;

use App\Navigating\Value\Navigation\NavigationShellGroup;
use Symfony\Component\HttpFoundation\Request;

interface NavigationVisibilityFilterServiceInterface
{
    /**
     * @param list<NavigationShellGroup> $groups
     *
     * @return list<NavigationShellGroup>
     */
    public function filterShellGroups(array $groups, Request $request): array;

    /**
     * @param list<string> $roles
     * @param list<string> $scopes
     */
    public function isShellGroupVisible(NavigationShellGroup $group, array $roles, array $scopes = [], ?string $environment = null): bool;
}
