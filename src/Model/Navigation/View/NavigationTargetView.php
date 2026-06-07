<?php

declare(strict_types=1);

namespace App\Navigating\Model\Navigation\View;

final readonly class NavigationTargetView
{
    /**
     * @param array<string, mixed> $parameters
     * @param array<string, mixed> $query
     */
    public function __construct(
        public string $type,
        public string $href,
        public ?string $route = null,
        public ?string $path = null,
        public ?string $action = null,
        public ?string $widget = null,
        public array $parameters = [],
        public array $query = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'href' => $this->href,
            'route' => $this->route,
            'path' => $this->path,
            'action' => $this->action,
            'widget' => $this->widget,
            'parameters' => $this->parameters,
            'query' => $this->query,
        ];
    }
}
