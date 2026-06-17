<?php

declare(strict_types=1);

namespace App\Navigating\Tests;

use App\Navigating\Service\Navigation\Filter\NavigationVisibilityFilterService;
use App\Navigating\Service\Navigation\Normalize\NavigationConfigNormalizeService;
use App\Navigating\Service\Navigation\Provide\NavigationRuntimeActivationProvideService;
use App\Navigating\ServiceInterface\Navigation\Provide\NavigationRequestRoleProvideServiceInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class NavigationRuntimeActivationTest extends TestCase
{
    public function testEntityMappedBusinessItemDoesNotRequireComponentScope(): void
    {
        $items = $this->filter(runtimeScope: 'cruding,viewing', runtimeEntity: 'vendor');

        self::assertSame(['vendor', 'help'], array_column($items, 'key'));
    }

    public function testMissingRuntimeEntityRemovesBusinessMenuItem(): void
    {
        $items = $this->filter(runtimeScope: 'vendoring', runtimeEntity: 'order');

        self::assertSame(['help'], array_column($items, 'key'));
    }

    public function testRequestScopeCannotExpandDeploymentActivation(): void
    {
        $request = new Request(attributes: ['_navigation_scopes' => ['business', 'vendoring']]);
        $items = $this->filter(runtimeScope: 'vendoring', runtimeEntity: 'order', request: $request);

        self::assertSame(['help'], array_column($items, 'key'));
    }

    public function testUnmappedDomainFallsBackToItsOwnRuntimeEntityToken(): void
    {
        $config = [
            'runtime_activation' => [
                'scope_by_domain' => [],
                'entity_by_domain' => [],
            ],
            'shell_groups' => [
                'business' => [
                    'location' => 'shell.left.middle',
                    'items' => [
                        'inventory' => [
                            'type' => 'link',
                            'path' => '/inventory/index',
                            'metadata' => ['domain' => 'inventory'],
                        ],
                    ],
                ],
            ],
        ];

        $groups = (new NavigationConfigNormalizeService())->normalizeShellGroups($config);
        $item = $groups[0]->items[0];

        self::assertSame([], $item->runtimeScopes);
        self::assertSame(['inventory'], $item->runtimeEntities);
    }

    public function testExplicitSystemDomainUsesRuntimeScope(): void
    {
        $config = [
            'runtime_activation' => [
                'scope_by_domain' => ['interface' => ['interfacing']],
                'entity_by_domain' => [],
            ],
            'shell_groups' => [
                'system' => [
                    'location' => 'shell.left.bottom',
                    'items' => [
                        'interface' => [
                            'type' => 'link',
                            'path' => '/interface/index',
                            'metadata' => ['domain' => 'interface'],
                        ],
                    ],
                ],
            ],
        ];

        $groups = (new NavigationConfigNormalizeService())->normalizeShellGroups($config);
        $item = $groups[0]->items[0];

        self::assertSame(['interfacing'], $item->runtimeScopes);
        self::assertSame([], $item->runtimeEntities);
    }

    public function testEntityMappingTakesPrecedenceOverScopeAlias(): void
    {
        $config = [
            'runtime_activation' => [
                'scope_by_domain' => ['vendor' => ['vendoring']],
                'entity_by_domain' => ['vendor' => ['vendor']],
            ],
            'shell_groups' => [
                'business' => [
                    'location' => 'shell.left.middle',
                    'items' => [
                        'vendor' => [
                            'type' => 'link',
                            'path' => '/vendor/index',
                            'metadata' => ['domain' => 'vendor'],
                        ],
                    ],
                ],
            ],
        ];

        $groups = (new NavigationConfigNormalizeService())->normalizeShellGroups($config);
        $item = $groups[0]->items[0];

        self::assertSame([], $item->runtimeScopes);
        self::assertSame(['vendor'], $item->runtimeEntities);
    }

    /** @return list<array<string, mixed>> */
    private function filter(string $runtimeScope, string $runtimeEntity, ?Request $request = null): array
    {
        $config = [
            'runtime_scopes' => ['fallback_scopes' => ['business']],
            'runtime_environment' => ['fallback_environment' => 'prod'],
            'runtime_activation' => [
                'scope_by_domain' => ['vendor' => ['vendoring']],
                'entity_by_domain' => ['vendor' => ['vendor']],
            ],
            'shell_groups' => [
                'business' => [
                    'label' => 'Business',
                    'location' => 'shell.left.middle',
                    'type' => 'navigation',
                    'items' => [
                        'vendor' => [
                            'type' => 'link',
                            'label' => 'Vendor',
                            'path' => '/vendor/index',
                            'metadata' => ['domain' => 'vendor'],
                        ],
                        'help' => [
                            'type' => 'link',
                            'label' => 'Help',
                            'path' => '/help',
                            'metadata' => ['static_path' => true],
                        ],
                    ],
                ],
            ],
        ];

        $groups = (new NavigationConfigNormalizeService())->normalizeShellGroups($config);
        $roleProvider = new class implements NavigationRequestRoleProvideServiceInterface {
            public function provideRoles(Request $request): array
            {
                return ['ROLE_USER'];
            }
        };
        $activationProvider = new NavigationRuntimeActivationProvideService(
            runtimeScope: $runtimeScope,
            runtimeEntity: $runtimeEntity,
            runtimeActivationStrict: true,
        );
        $filter = new NavigationVisibilityFilterService($roleProvider, $activationProvider, $config);
        $visibleGroups = $filter->filterShellGroups($groups, $request ?? new Request());

        return array_map(
            static fn ($item): array => ['key' => $item->key],
            $visibleGroups[0]->items,
        );
    }
}
