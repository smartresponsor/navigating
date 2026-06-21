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
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml\Yaml;

final class NavigationBusinessVisibleMenuSurfaceTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function configuration(): array
    {
        $file = dirname(__DIR__).'/config/navigation.yaml';
        $data = Yaml::parseFile($file);

        self::assertIsArray($data);
        self::assertArrayHasKey('navigation', $data);
        self::assertIsArray($data['navigation']);

        return $data['navigation'];
    }

    public function testBusinessVisibleMenuConfigurationIsValid(): void
    {
        $result = (new NavigationConfigValidateService())->validate($this->configuration());

        self::assertTrue($result->isValid(), implode(' ', $result->errors));
    }

    public function testShellGroupsDoNotUseUnsupportedGroupMetadata(): void
    {
        $config = $this->configuration();

        foreach ($config['shell_groups'] as $groupKey => $group) {
            self::assertArrayNotHasKey('metadata', $group, sprintf('navigation.shell_groups.%s must keep metadata on items, not on the group node.', $groupKey));
        }
    }

    public function testLeftMiddleContainsOnlyBusinessCrudIndexRoots(): void
    {
        $config = $this->configuration();
        $items = $config['shell_groups']['left_middle_business']['items'] ?? [];

        self::assertArrayHasKey('vendor', $items);
        self::assertArrayHasKey('catalog', $items);
        self::assertArrayHasKey('order', $items);
        self::assertArrayHasKey('consumer', $items);
        self::assertArrayHasKey('billing', $items);
        self::assertArrayHasKey('analysis', $items);
        self::assertArrayNotHasKey('navigation', $items);
        self::assertArrayNotHasKey('access', $items);

        foreach ($items as $item) {
            self::assertSame('link', $item['type']);
            self::assertSame('index', $item['metadata']['operation'] ?? null);
            self::assertTrue($item['metadata']['crud_index_only'] ?? false);
            self::assertTrue($item['metadata']['root_only'] ?? false);
            self::assertSame('business', $item['metadata']['menu_scope'] ?? null);
            self::assertStringEndsWith('.index', (string) ($item['metadata']['route_name'] ?? ''));
        }
    }

    public function testLeftBottomContainsOnlyAdminSystemCrudIndexRoots(): void
    {
        $config = $this->configuration();
        $group = $config['shell_groups']['left_bottom_platform'] ?? [];
        $items = $group['items'] ?? [];

        self::assertSame(['ROLE_ADMIN', 'ROLE_SUPER_ADMIN'], $group['visible_for_roles'] ?? null);
        self::assertArrayHasKey('navigation', $items);
        self::assertArrayHasKey('management', $items);
        self::assertArrayHasKey('access', $items);
        self::assertArrayHasKey('governance', $items);
        self::assertArrayHasKey('gating', $items);
        self::assertArrayNotHasKey('vendor', $items);
        self::assertArrayNotHasKey('catalog', $items);

        foreach ($items as $item) {
            self::assertSame('link', $item['type']);
            self::assertSame(['ROLE_ADMIN', 'ROLE_SUPER_ADMIN'], $item['visible_for_roles'] ?? null);
            self::assertSame('index', $item['metadata']['operation'] ?? null);
            self::assertTrue($item['metadata']['crud_index_only'] ?? false);
            self::assertTrue($item['metadata']['root_only'] ?? false);
            self::assertSame('system', $item['metadata']['menu_scope'] ?? null);
            self::assertStringEndsWith('.index', (string) ($item['metadata']['route_name'] ?? ''));
        }
    }

    public function testContextTopContainsOnlyRelatedBusinessCrudIndexRoots(): void
    {
        $config = $this->configuration();
        $group = $config['shell_groups']['context_top_related_business'] ?? [];
        $items = $group['items'] ?? [];

        self::assertSame('shell.context.top', $group['location'] ?? null);
        self::assertArrayHasKey('attachment', $items);
        self::assertArrayHasKey('tag', $items);
        self::assertArrayHasKey('address', $items);
        self::assertArrayNotHasKey('access', $items);
        self::assertArrayNotHasKey('governance', $items);

        foreach ($items as $item) {
            self::assertSame('link', $item['type']);
            self::assertSame('index', $item['metadata']['operation'] ?? null);
            self::assertSame('business', $item['metadata']['menu_scope'] ?? null);
            self::assertTrue($item['metadata']['related_entity'] ?? false);
            self::assertTrue($item['metadata']['crud_index_only'] ?? false);
            self::assertStringEndsWith('.index', (string) ($item['metadata']['route_name'] ?? ''));
            self::assertArrayNotHasKey('visible_for_roles', $item);
        }
    }

    public function testContextBottomContainsOnlyRelatedAdminSystemCrudIndexRoots(): void
    {
        $config = $this->configuration();
        $group = $config['shell_groups']['context_bottom_related_system'] ?? [];
        $items = $group['items'] ?? [];

        self::assertSame('shell.context.bottom', $group['location'] ?? null);
        self::assertSame(['ROLE_ADMIN', 'ROLE_SUPER_ADMIN'], $group['visible_for_roles'] ?? null);
        self::assertArrayHasKey('access', $items);
        self::assertArrayHasKey('governance', $items);
        self::assertArrayHasKey('observability', $items);
        self::assertArrayNotHasKey('attachment', $items);
        self::assertArrayNotHasKey('tag', $items);

        foreach ($items as $item) {
            self::assertSame('link', $item['type']);
            self::assertSame(['ROLE_ADMIN', 'ROLE_SUPER_ADMIN'], $item['visible_for_roles'] ?? null);
            self::assertSame('index', $item['metadata']['operation'] ?? null);
            self::assertSame('system', $item['metadata']['menu_scope'] ?? null);
            self::assertTrue($item['metadata']['related_entity'] ?? false);
            self::assertTrue($item['metadata']['crud_index_only'] ?? false);
            self::assertTrue($item['metadata']['admin_only'] ?? false);
            self::assertStringEndsWith('.index', (string) ($item['metadata']['route_name'] ?? ''));
        }
    }

    public function testRuntimeProjectsBusinessAndAdminRoots(): void
    {
        $request = Request::create('/catalog/product/index');
        $request->attributes->set('_navigation_roles', ['ROLE_ADMIN']);
        $request->attributes->set('_navigation_scopes', ['user', 'system']);
        $request->attributes->set('_navigation_environment', 'dev');

        $locations = $this->provider($this->configuration())->provideShell($request)->toLocationsArray();

        self::assertContains('catalog', array_column($locations['shell.left.middle'], 'key'));
        self::assertContains('navigation', array_column($locations['shell.left.bottom'], 'key'));
        self::assertContains('attachment', array_column($locations['shell.context.top'], 'key'));
        self::assertContains('access', array_column($locations['shell.context.bottom'], 'key'));
        self::assertContains('command_palette', array_column($locations['shell.right.top'], 'key'));
        self::assertContains('route_map', array_column($locations['shell.right.tool'], 'key'));
        self::assertContains('documentation', array_column($locations['shell.footer.main'], 'key'));
        self::assertTrue($this->itemByKey($locations['shell.left.middle'], 'catalog')['active']);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function provider(array $config): NavigationShellProvideService
    {
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

    /**
     * @param list<array<string, mixed>> $items
     *
     * @return array<string, mixed>
     */
    private function itemByKey(array $items, string $key): array
    {
        foreach ($items as $item) {
            if (($item['key'] ?? null) === $key) {
                return $item;
            }
        }

        self::fail(sprintf('Navigation item "%s" was not found.', $key));
    }
}
