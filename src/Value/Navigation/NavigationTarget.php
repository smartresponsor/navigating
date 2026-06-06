<?php

declare(strict_types=1);

namespace App\Navigating\Value\Navigation;

final readonly class NavigationTarget
{
    /**
     * @param array<string, mixed> $params
     * @param array<string, mixed> $query
     */
    public function __construct(
        public string $type,
        public ?string $path = null,
        public ?string $route = null,
        public array $params = [],
        public array $query = [],
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $type = (string) ($data['type'] ?? 'path');

        return new self(
            type: $type,
            path: isset($data['path']) ? (string) $data['path'] : null,
            route: isset($data['route']) ? (string) $data['route'] : (isset($data['name']) ? (string) $data['name'] : null),
            params: is_array($data['params'] ?? null) ? $data['params'] : [],
            query: is_array($data['query'] ?? null) ? $data['query'] : [],
        );
    }
}
