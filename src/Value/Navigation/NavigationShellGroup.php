<?php

declare(strict_types=1);

namespace App\Navigating\Value\Navigation;

final readonly class NavigationShellGroup
{
    /**
     * @param list<string>              $visibleForRoles
     * @param list<string>              $visibleForScopes
     * @param list<string>              $visibleForEnvironments
     * @param list<NavigationShellItem> $items
     */
    public function __construct(
        public string $key,
        public string $label,
        public int $priority,
        public bool $enabled,
        public bool $visible,
        public array $visibleForRoles,
        public array $visibleForScopes,
        public array $visibleForEnvironments,
        public string $location,
        public string $type = 'menu',
        public array $items = [],
    ) {
    }
}
