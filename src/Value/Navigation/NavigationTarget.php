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
        $typeValue = $data['type'] ?? null;
        $type = is_string($typeValue) && '' !== trim($typeValue) ? trim($typeValue) : 'path';
        $pathValue = $data['path'] ?? null;
        $routeValue = $data['route'] ?? $data['nameEntity'] ?? null;

        return new self(
            type: $type,
            path: is_string($pathValue) && '' !== trim($pathValue) ? trim($pathValue) : null,
            route: is_string($routeValue) && '' !== trim($routeValue) ? trim($routeValue) : null,
            params: self::parametersFromArray($data),
            query: self::objectMap($data['query'] ?? null),
        );
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private static function parametersFromArray(array $data): array
    {
        if (array_key_exists('params', $data)) {
            return self::objectMap($data['params']);
        }

        if (array_key_exists('parameters', $data)) {
            return self::objectMap($data['parameters']);
        }

        return [];
    }

    /** @return array<string, mixed> */
    private static function objectMap(mixed $value): array
    {
        if (!is_array($value) || ([] !== $value && array_is_list($value))) {
            return [];
        }

        $map = [];
        foreach ($value as $key => $item) {
            if (!is_string($key)) {
                return [];
            }
            $map[$key] = $item;
        }

        return $map;
    }
}
