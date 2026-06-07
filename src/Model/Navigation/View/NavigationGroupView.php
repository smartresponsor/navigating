<?php

declare(strict_types=1);

namespace App\Navigating\Model\Navigation\View;

final readonly class NavigationGroupView
{
    /**
     * @param list<NavigationItemView> $items
     * @param array<string, mixed>     $metadata
     */
    public function __construct(
        public string $location,
        public string $label,
        public array $items = [],
        public string $type = 'navigation',
        public array $metadata = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'location' => $this->location,
            'label' => $this->label,
            'type' => $this->type,
            'items' => array_map(static fn (NavigationItemView $item): array => $item->toArray(), $this->items),
            'metadata' => $this->metadata,
        ];
    }
}
