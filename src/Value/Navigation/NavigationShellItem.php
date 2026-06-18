<?php

declare(strict_types=1);

namespace App\Navigating\Value\Navigation;

final readonly class NavigationShellItem
{
    /**
     * @param list<string>         $visibleForRoles
     * @param list<string>         $visibleForScopes
     * @param list<string>         $visibleForEnvironments
     * @param array<string, mixed> $metadata
     * @param list<string>         $runtimeScopes
     * @param list<string>         $runtimeEntities
     */
    public function __construct(
        public string $key,
        public string $type,
        public string $label,
        public int $priority,
        public bool $enabled,
        public bool $visible,
        public array $visibleForRoles,
        public array $visibleForScopes = [],
        public array $visibleForEnvironments = [],
        public ?NavigationTarget $target = null,
        public ?string $action = null,
        public ?string $widget = null,
        public ?string $icon = null,
        public ?string $badge = null,
        public array $metadata = [],
        public array $runtimeScopes = [],
        public array $runtimeEntities = [],
        public ?string $namespaceProvider = null,
        public ?string $namespace = null,
        public ?string $runtimeScope = null,
    ) {
    }
}
