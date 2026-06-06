<?php

declare(strict_types=1);

namespace App\Navigating\Value\Navigation;

final readonly class NavigationItemView
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $key,
        public string $type,
        public string $label,
        public string $url,
        public bool $active,
        public int $priority,
        public ?string $action = null,
        public ?string $widget = null,
        public ?string $icon = null,
        public ?string $badge = null,
        public array $metadata = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'type' => $this->type,
            'label' => $this->label,
            'url' => $this->url,
            'active' => $this->active,
            'priority' => $this->priority,
            'action' => $this->action,
            'widget' => $this->widget,
            'icon' => $this->icon,
            'badge' => $this->badge,
            'metadata' => $this->metadata,
        ];
    }
}
