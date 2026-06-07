<?php

declare(strict_types=1);

namespace App\Navigating\Model\Navigation\View;

final readonly class NavigationItemView
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $key,
        public string $type,
        public string $label,
        public NavigationTargetView $target,
        public bool $active = false,
        public bool $disabled = false,
        public bool $visible = true,
        public int $priority = 100,
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
            'href' => $this->target->href,
            'target' => $this->target->toArray(),
            'active' => $this->active,
            'disabled' => $this->disabled,
            'visible' => $this->visible,
            'priority' => $this->priority,
            'icon' => $this->icon,
            'badge' => $this->badge,
            'metadata' => $this->metadata,
        ];
    }
}
