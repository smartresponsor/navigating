<?php

declare(strict_types=1);

namespace App\Navigating\Service\Navigation\Provide;

use App\Navigating\Model\Navigation\View\NavigationGroupView;
use App\Navigating\Model\Navigation\View\NavigationShellView;
use App\Navigating\ServiceInterface\Navigation\Build\NavigationTreeBuildServiceInterface;
use App\Navigating\ServiceInterface\Navigation\Filter\NavigationVisibilityFilterServiceInterface;
use App\Navigating\ServiceInterface\Navigation\Normalize\NavigationConfigNormalizeServiceInterface;
use App\Navigating\ServiceInterface\Navigation\Provide\NavigationDatabaseConfigProvideServiceInterface;
use App\Navigating\ServiceInterface\Navigation\Provide\NavigationShellProvideServiceInterface;
use App\Navigating\ServiceInterface\Navigation\Validate\NavigationConfigValidateServiceInterface;
use App\Navigating\Value\Navigation\NavigationShellLocationRegistry;
use Symfony\Component\HttpFoundation\Request;

final readonly class NavigationShellProvideService implements NavigationShellProvideServiceInterface
{
    /** @param array<string, mixed> $navigationConfig */
    public function __construct(
        private NavigationConfigNormalizeServiceInterface $configNormalizeService,
        private NavigationConfigValidateServiceInterface $configValidateService,
        private NavigationVisibilityFilterServiceInterface $visibilityFilterService,
        private NavigationTreeBuildServiceInterface $treeBuildService,
        private NavigationDatabaseConfigProvideServiceInterface $databaseConfigProvider,
        private array $navigationConfig = [],
    ) {
    }

    public function provideShell(Request $request): NavigationShellView
    {
        $config = $this->runtimeConfig();
        $this->assertValidConfig($config);

        $groups = $this->visibilityFilterService->filterShellGroups(
            $this->configNormalizeService->normalizeShellGroups($config),
            $request,
        );

        $views = $this->emptyCanonicalGroups($config);

        foreach ($groups as $group) {
            $location = trim($group->location);
            $builtGroup = $this->treeBuildService->buildGroup($group, $request);

            $views[$location] = $this->mergeGroup($views[$location] ?? null, $builtGroup);
        }

        return new NavigationShellView($views);
    }

    public function provideActiveState(Request $request): array
    {
        foreach ($this->provideShell($request)->groups as $group) {
            foreach ($group->items as $item) {
                if (!$item->active) {
                    continue;
                }

                return [
                    'active_group' => is_string($item->metadata['group'] ?? null) ? $item->metadata['group'] : null,
                    'active_item' => $item->key,
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

    /** @return array<string, NavigationGroupView> */
    private function emptyCanonicalGroups(array $config): array
    {
        $groups = [];

        foreach (NavigationShellLocationRegistry::all($config) as $location) {
            $groups[$location] = new NavigationGroupView(location: $location, label: $location);
        }

        return $groups;
    }

    private function mergeGroup(?NavigationGroupView $current, NavigationGroupView $incoming): NavigationGroupView
    {
        if (!$current instanceof NavigationGroupView || [] === $current->items) {
            return $incoming;
        }

        return new NavigationGroupView(
            location: $incoming->location,
            label: $incoming->label,
            items: [...$current->items, ...$incoming->items],
            type: $incoming->type,
            metadata: array_replace($current->metadata, $incoming->metadata, [
                'item_count' => count($current->items) + count($incoming->items),
            ]),
        );
    }

    /** @return array<string, mixed> */
    private function runtimeConfig(): array
    {
        $databaseConfig = $this->databaseConfigProvider->provideConfig();

        return [] === $databaseConfig ? $this->navigationConfig : $databaseConfig;
    }

    /** @param array<string, mixed> $config */
    private function assertValidConfig(array $config): void
    {
        $result = $this->configValidateService->validate($config);

        if ($result->isValid()) {
            return;
        }

        throw new \InvalidArgumentException('Invalid navigation config: '.implode(' ', $result->errors));
    }
}
