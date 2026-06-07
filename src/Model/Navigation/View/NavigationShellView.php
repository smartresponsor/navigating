<?php

declare(strict_types=1);

namespace App\Navigating\Model\Navigation\View;

final readonly class NavigationShellView
{
    /**
     * @param array<string, NavigationGroupView> $groups
     */
    public function __construct(
        public array $groups,
    ) {
    }

    public function group(string $location): NavigationGroupView
    {
        return $this->groups[$location] ?? new NavigationGroupView(location: $location, label: $location);
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    public function toLocationsArray(): array
    {
        $locations = [];

        foreach ($this->groups as $location => $group) {
            $locations[$location] = array_map(static fn (NavigationItemView $item): array => $item->toArray(), $group->items);
        }

        return $locations;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $groups = [];

        foreach ($this->groups as $location => $group) {
            $groups[$location] = $group->toArray();
        }

        return [
            'groups' => $groups,
        ];
    }
}
