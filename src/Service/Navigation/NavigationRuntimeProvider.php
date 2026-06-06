<?php

declare(strict_types=1);

namespace App\Navigating\Service\Navigation;

use App\Navigating\ServiceInterface\Navigation\NavigationRuntimeProviderInterface;
use App\Navigating\Value\Navigation\NavigationShellLocationRegistry;
use Symfony\Component\HttpFoundation\Request;

final readonly class NavigationRuntimeProvider implements NavigationRuntimeProviderInterface
{
    /**
     * @param array<string, mixed> $navigationConfig
     */
    public function __construct(
        private NavigationConfigNormalizer $normalizer,
        private NavigationTreeBuilder $treeBuilder,
        private NavigationConfigValidator $validator,
        private NavigationRoleVisibilityFilter $visibilityFilter,
        private array $navigationConfig = [],
    ) {
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    public function provideLocations(Request $request): array
    {
        $this->assertValidConfig();

        $locations = $this->emptyCanonicalLocations();
        $shellGroups = $this->visibilityFilter->filterShellGroups(
            $this->normalizer->normalizeShellGroups($this->navigationConfig),
            $request,
        );

        foreach ($shellGroups as $group) {
            $location = $this->normalizeLocation($group->location);
            $locations[$location] = array_merge(
                $locations[$location] ?? [],
                $this->treeBuilder->buildShellGroupItems($group, $request),
            );
        }

        return $locations;
    }

    /**
     * @return array{active_group: string|null, active_item: string|null, active_root: string|null, active_section: string|null}
     */
    public function provideActiveState(Request $request): array
    {
        $this->assertValidConfig();

        $shellGroups = $this->visibilityFilter->filterShellGroups(
            $this->normalizer->normalizeShellGroups($this->navigationConfig),
            $request,
        );

        foreach ($shellGroups as $group) {
            foreach ($this->treeBuilder->buildShellGroupItems($group, $request) as $item) {
                if (true !== ($item['active'] ?? false)) {
                    continue;
                }

                return [
                    'active_group' => $group->key,
                    'active_item' => is_string($item['key'] ?? null) ? $item['key'] : null,
                    'active_root' => null,
                    'active_section' => null,
                ];
            }
        }

        return [
            'active_group' => null,
            'active_item' => null,
            'active_root' => null,
            'active_section' => null,
        ];
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    private function emptyCanonicalLocations(): array
    {
        $locations = [];

        foreach (NavigationShellLocationRegistry::all() as $slot) {
            $locations[$slot] = [];
        }

        return $locations;
    }

    private function normalizeLocation(string $location): string
    {
        return trim($location);
    }

    private function assertValidConfig(): void
    {
        $result = $this->validator->validate($this->navigationConfig);

        if ($result->isValid()) {
            return;
        }

        throw new \InvalidArgumentException('Invalid navigation config: '.implode(' ', $result->errors));
    }
}
