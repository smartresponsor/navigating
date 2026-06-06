<?php

declare(strict_types=1);

namespace App\Navigating\Tests;

use App\Navigating\Service\Navigation\NavigationConfigNormalizer;
use App\Navigating\Service\Navigation\NavigationConfigValidator;
use App\Navigating\Service\Navigation\NavigationRoleVisibilityFilter;
use App\Navigating\Service\Navigation\NavigationRuntimeProvider;
use App\Navigating\Service\Navigation\NavigationTargetResolver;
use App\Navigating\Service\Navigation\NavigationTreeBuilder;
use App\Navigating\Service\Navigation\RequestAttributeNavigationRoleProvider;
use App\Navigating\Value\Navigation\NavigationShellLocationRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class NavigationRuntimeProviderTest extends TestCase
{
    public function testRuntimeAlwaysReturnsFullCanonicalShellMap(): void
    {
        $locations = $this->provider([])->provideLocations(Request::create('/'));

        foreach (NavigationShellLocationRegistry::all() as $slot) {
            self::assertArrayHasKey($slot, $locations);
            self::assertIsArray($locations[$slot]);
        }
    }

    public function testRuntimeProjectsShellGroupsIntoCanonicalLocations(): void
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
        ])->provideLocations(Request::create('/'));

        self::assertSame('dashboard', $locations['shell.left.middle'][0]['key']);
        self::assertSame('shell_item', $locations['shell.left.middle'][0]['metadata']['level']);
    }

    public function testRuntimeRejectsLegacyShellLocation(): void
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
        ])->provideLocations(Request::create('/'));
    }

    public function testRuntimeProjectsRealViewPrimaryMenu(): void
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
        ])->provideLocations(Request::create('/catalog'));

        self::assertSame(['dashboard', 'catalog', 'order'], array_column($locations['shell.left.middle'], 'key'));
        self::assertTrue($locations['shell.left.middle'][1]['active']);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function provider(array $config): NavigationRuntimeProvider
    {
        $config += ['schema' => 3, 'shell_groups' => []];

        return new NavigationRuntimeProvider(
            new NavigationConfigNormalizer(),
            new NavigationTreeBuilder(new NavigationTargetResolver()),
            new NavigationConfigValidator(),
            new NavigationRoleVisibilityFilter(new RequestAttributeNavigationRoleProvider(['ROLE_USER']), $config),
            $config,
        );
    }

    public function testRuntimeProjectsTypedActionAndWidgetItems(): void
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
        ])->provideLocations(Request::create('/catalog'));

        self::assertSame('action', $locations['shell.main.toolbar'][0]['type']);
        self::assertSame('', $locations['shell.main.toolbar'][0]['url']);
        self::assertFalse($locations['shell.main.toolbar'][0]['active']);
        self::assertSame('navigation.refresh', $locations['shell.main.toolbar'][0]['action']);
        self::assertSame('widget', $locations['shell.right.tool'][0]['type']);
        self::assertSame('shell.inspector', $locations['shell.right.tool'][0]['widget']);
    }

    public function testRuntimeFiltersItemsByScopeAndEnvironment(): void
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
        ])->provideLocations($request);

        self::assertSame(['visible_tool'], array_column($locations['shell.right.tool'], 'key'));
    }

    public function testRuntimeUsesFallbackScopeAndEnvironment(): void
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
        ])->provideLocations(Request::create('/'));

        self::assertSame('environment', $locations['shell.footer.context'][0]['key']);
    }
}
