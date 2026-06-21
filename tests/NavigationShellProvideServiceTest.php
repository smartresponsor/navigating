<?php

declare(strict_types=1);

namespace App\Navigating\Tests;

use App\Navigating\Service\Navigation\Build\NavigationTreeBuildService;
use App\Navigating\Service\Navigation\Filter\NavigationVisibilityFilterService;
use App\Navigating\Service\Navigation\Normalize\NavigationConfigNormalizeService;
use App\Navigating\Service\Navigation\Provide\NavigationRequestRoleProvideService;
use App\Navigating\Service\Navigation\Provide\NavigationRuntimeActivationProvideService;
use App\Navigating\Service\Navigation\Provide\NavigationShellProvideService;
use App\Navigating\Service\Navigation\Resolve\NavigationTargetResolveService;
use App\Navigating\Service\Navigation\Validate\NavigationConfigValidateService;
use App\Navigating\Value\Navigation\NavigationShellLocationRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class NavigationShellProvideServiceTest extends TestCase
{
    public function testShellProviderAlwaysReturnsFullCanonicalShellMap(): void
    {
        $locations = $this->provider([
            'schema' => 3,
            'shell_locations' => [
                'shell.left.middle' => [
                    'label' => 'Left middle',
                    'region' => 'left',
                    'slot' => 'middle',
                    'type' => 'navigation',
                ],
                'shell.main.toolbar' => [
                    'label' => 'Main toolbar',
                    'region' => 'main',
                    'slot' => 'toolbar',
                    'type' => 'toolbar',
                ],
            ],
            'shell_groups' => [],
        ])->provideShell(Request::create('/'))->toLocationsArray();

        self::assertSame(['shell.left.middle', 'shell.main.toolbar'], array_keys($locations));

        foreach (NavigationShellLocationRegistry::all([
            'shell_locations' => [
                'shell.left.middle' => [],
                'shell.main.toolbar' => [],
            ],
        ]) as $slot) {
            self::assertArrayHasKey($slot, $locations);
            self::assertIsArray($locations[$slot]);
            self::assertSame([], $locations[$slot]);
        }
    }

    public function testShellProviderProjectsShellGroupsIntoCanonicalLocations(): void
    {
        $locations = $this->provider([
            'schema' => 3,
            'shell_groups' => [
                'left_middle_primary' => [
                    'location' => 'shell.left.middle',
                    'items' => [
                        'dashboard' => [
                            'type' => 'link',
                            'label' => 'Dashboard',
                            'path' => '/',
                        ],
                    ],
                ],
            ],
        ])->provideShell(Request::create('/'))->toLocationsArray();

        self::assertSame('dashboard', $locations['shell.left.middle'][0]['key']);
        self::assertSame('shell_item', $locations['shell.left.middle'][0]['metadata']['level']);
    }

    public function testShellProviderRejectsUnsupportedShellLocation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('shell.header.left.logo');

        $this->provider([
            'schema' => 3,
            'shell_groups' => [
                'legacy_logo' => [
                    'location' => 'shell.header.left.logo',
                    'items' => [],
                ],
            ],
        ])->provideShell(Request::create('/'));
    }

    public function testShellProviderProjectsRealViewPrimaryNavigation(): void
    {
        $locations = $this->provider([
            'schema' => 3,
            'shell_groups' => [
                'left_middle_primary' => [
                    'label' => 'Primary navigation',
                    'location' => 'shell.left.middle',
                    'items' => [
                        'dashboard' => [
                            'type' => 'link',
                            'label' => 'Dashboard',
                            'priority' => 10,
                            'path' => '/',
                        ],
                        'catalog' => [
                            'type' => 'link',
                            'label' => 'Catalog',
                            'priority' => 20,
                            'path' => '/catalog',
                        ],
                        'order' => [
                            'type' => 'link',
                            'label' => 'Orders',
                            'priority' => 30,
                            'path' => '/order',
                        ],
                    ],
                ],
            ],
        ])->provideShell(Request::create('/catalog'))->toLocationsArray();

        self::assertSame(['dashboard', 'catalog', 'order'], array_column($locations['shell.left.middle'], 'key'));
        self::assertTrue($locations['shell.left.middle'][1]['active']);
    }

    public function testShellProviderProjectsTypedActionAndWidgetItems(): void
    {
        $locations = $this->provider([
            'schema' => 3,
            'shell_groups' => [
                'main_toolbar_actions' => [
                    'location' => 'shell.main.toolbar',
                    'items' => [
                        'refresh' => [
                            'type' => 'action',
                            'label' => 'Refresh',
                            'action' => 'navigation.refresh',
                        ],
                    ],
                ],
                'right_tool_panel' => [
                    'location' => 'shell.right.tool',
                    'items' => [
                        'inspector' => [
                            'type' => 'widget',
                            'label' => 'Inspector',
                            'widget' => 'shell.inspector',
                        ],
                    ],
                ],
            ],
        ])->provideShell(Request::create('/catalog'))->toLocationsArray();

        self::assertSame('action', $locations['shell.main.toolbar'][0]['type']);
        self::assertSame('', $locations['shell.main.toolbar'][0]['href']);
        self::assertFalse($locations['shell.main.toolbar'][0]['active']);
        self::assertSame('navigation.refresh', $locations['shell.main.toolbar'][0]['target']['action']);
        self::assertSame('widget', $locations['shell.right.tool'][0]['type']);
        self::assertSame('shell.inspector', $locations['shell.right.tool'][0]['target']['widget']);
    }

    public function testShellProviderFiltersItemsByScopeAndEnvironment(): void
    {
        $request = Request::create('/');
        $request->attributes->set('_navigation_scopes', ['user']);
        $request->attributes->set('_navigation_environment', 'prod');

        $locations = $this->provider([
            'schema' => 3,
            'shell_groups' => [
                'right_tool_panel' => [
                    'location' => 'shell.right.tool',
                    'items' => [
                        'visible_tool' => [
                            'type' => 'widget',
                            'label' => 'Visible Tool',
                            'widget' => 'shell.visible_tool',
                            'visible_for_scopes' => ['user'],
                            'visible_for_environments' => ['prod'],
                        ],
                        'dev_only_tool' => [
                            'type' => 'widget',
                            'label' => 'Dev Tool',
                            'widget' => 'shell.dev_tool',
                            'visible_for_scopes' => ['system'],
                            'visible_for_environments' => ['dev'],
                        ],
                    ],
                ],
            ],
        ])->provideShell($request)->toLocationsArray();

        self::assertSame(['visible_tool'], array_column($locations['shell.right.tool'], 'key'));
    }

    public function testShellProviderUsesFallbackScopeAndEnvironment(): void
    {
        $locations = $this->provider([
            'schema' => 3,
            'runtime_scopes' => [
                'fallback_scopes' => ['system'],
            ],
            'runtime_environment' => [
                'fallback_environment' => 'dev',
            ],
            'shell_groups' => [
                'footer_context_environment' => [
                    'location' => 'shell.footer.context',
                    'visible_for_scopes' => ['system'],
                    'items' => [
                        'environment' => [
                            'type' => 'badge',
                            'label' => 'Environment',
                            'badge' => 'dev',
                            'visible_for_environments' => ['dev'],
                        ],
                    ],
                ],
            ],
        ])->provideShell(Request::create('/'))->toLocationsArray();

        self::assertSame('environment', $locations['shell.footer.context'][0]['key']);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function provider(array $config): NavigationShellProvideService
    {
        $config += [
            'schema' => 3,
            'shell_groups' => [],
            'shell_locations' => [
                'shell.left.middle' => [
                    'label' => 'Primary navigation',
                    'region' => 'left',
                    'slot' => 'middle',
                    'type' => 'navigation',
                    'priority' => 50,
                    'metadata' => ['interface_location' => true],
                ],
                'shell.main.toolbar' => [
                    'label' => 'Main toolbar',
                    'region' => 'main',
                    'slot' => 'toolbar',
                    'type' => 'toolbar',
                    'priority' => 110,
                    'metadata' => ['interface_location' => true],
                ],
                'shell.right.tool' => [
                    'label' => 'Right tool',
                    'region' => 'right',
                    'slot' => 'tool',
                    'type' => 'tool',
                    'priority' => 150,
                    'metadata' => ['interface_location' => true],
                ],
                'shell.footer.context' => [
                    'label' => 'Footer context',
                    'region' => 'footer',
                    'slot' => 'context',
                    'type' => 'footer',
                    'priority' => 210,
                    'metadata' => ['interface_location' => true],
                ],
            ],
        ];

        foreach ($config['shell_groups'] as &$group) {
            if (!isset($group['namespace_provider']) && !isset($group['namespace']) && !isset($group['metadata']['namespace_provider'])) {
                $group['namespace_provider'] = 'App\\User\\Navigation';
            }
        }

        unset($group);

        return new NavigationShellProvideService(
            new NavigationConfigNormalizeService(),
            new NavigationConfigValidateService(),
            new NavigationVisibilityFilterService(
                new NavigationRequestRoleProvideService($config),
                new NavigationRuntimeActivationProvideService(
                    runtimeScope: ['user', 'system'],
                    runtimeEntity: [],
                    runtimeActivationStrict: false,
                ),
                $config,
            ),
            new NavigationTreeBuildService(new NavigationTargetResolveService()),
            $config,
        );
    }
}
