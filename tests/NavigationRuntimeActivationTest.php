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
    public function testMissingRuntimeScopeRemovesComponentMenuItem(): void
    {
        $items = $this->filter(runtimeScope: 'cruding,viewing', runtimeEntity: 'vendor');

        self::assertSame(['help'], array_column($items, 'key'));
    }

    public function testRuntimeScopeAndEntityPublishComponentMenuItem(): void
    {
        $items = $this->filter(runtimeScope: 'cruding,vendoring', runtimeEntity: 'vendor');

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
        $items = $this->filter(runtimeScope: 'cruding', runtimeEntity: 'vendor', request: $request);

        self::assertSame(['help'], array_column($items, 'key'));
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
