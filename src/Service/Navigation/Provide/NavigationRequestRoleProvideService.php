<?php

declare(strict_types=1);

namespace App\Navigating\Service\Navigation\Provide;

use App\Navigating\ServiceInterface\Navigation\Provide\NavigationRequestRoleProvideServiceInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class NavigationRequestRoleProvideService implements NavigationRequestRoleProvideServiceInterface
{
    /**
     * @param array<string, mixed> $navigationConfig
     */
    public function __construct(
        private array $navigationConfig = [],
    ) {
    }

    /**
     * @return list<string>
     */
    public function provideRoles(Request $request): array
    {
        foreach (['_navigation_roles', 'navigation_roles', '_roles', 'roles'] as $attributeName) {
            $roles = $request->attributes->get($attributeName);

            if (is_array($roles)) {
                return $this->normalizeRoles($roles);
            }
        }

        return $this->fallbackRoles();
    }

    /**
     * @return list<string>
     */
    private function fallbackRoles(): array
    {
        $runtimeRoles = $this->navigationConfig['runtime_roles'] ?? [];

        if (!is_array($runtimeRoles)) {
            return [];
        }

        $fallbackRoles = $runtimeRoles['fallback_roles'] ?? [];

        if (!is_array($fallbackRoles)) {
            return [];
        }

        return $this->normalizeRoles($fallbackRoles);
    }

    /**
     * @param array<int|string, mixed> $roles
     *
     * @return list<string>
     */
    private function normalizeRoles(array $roles): array
    {
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
}
