<?php

declare(strict_types=1);

namespace App\Navigating\Tests;

use App\Navigating\Service\Navigation\Build\NavigationTreeBuildService;
use App\Navigating\Service\Navigation\Filter\NavigationVisibilityFilterService;
use App\Navigating\Service\Navigation\Normalize\NavigationConfigNormalizeService;
use App\Navigating\Service\Navigation\Provide\NavigationGroupProvideService;
use App\Navigating\Service\Navigation\Provide\NavigationRequestRoleProvideService;
use App\Navigating\Service\Navigation\Provide\NavigationShellProvideService;
use App\Navigating\Service\Navigation\Render\NavigationRenderService;
use App\Navigating\Service\Navigation\Resolve\NavigationTargetResolveService;
use App\Navigating\Service\Navigation\Validate\NavigationConfigValidateService;
use App\Navigating\Service\Twig\Navigation\NavigationTwigExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class NavigationRenderSurfaceTest extends TestCase
{
    public function testShellProviderReturnsViewModelGroups(): void
    {
        $request = Request::create('/catalog/product/index');
        $request->attributes->set('_navigation_roles', ['ROLE_USER']);

        $shell = $this->shellProvider()->provideShell($request);
        $group = $shell->group('shell.left.middle')->toArray();

        self::assertSame('shell.left.middle', $group['location']);
        self::assertNotEmpty($group['items']);
        self::assertSame('catalog', $this->itemByKey($group['items'], 'catalog')['key']);
        self::assertTrue($this->itemByKey($group['items'], 'catalog')['active']);
    }

    public function testGroupProviderReturnsSingleSlotPayload(): void
    {
        $request = Request::create('/vendor/profile/index');
        $request->attributes->set('_navigation_roles', ['ROLE_USER']);

        $group = $this->groupProvider()->provideGroup('shell.left.middle', $request)->toArray();

        self::assertSame('shell.left.middle', $group['location']);
        self::assertContains('vendor', array_column($group['items'], 'key'));
        self::assertArrayHasKey('target', $this->itemByKey($group['items'], 'vendor'));
        self::assertSame('/vendor/profile/index', $this->itemByKey($group['items'], 'vendor')['href']);
    }

    public function testRenderServiceReturnsSafeShellHtmlWithoutTwigRouteLookup(): void
    {
        $request = Request::create('/catalog/product/index');
        $request->attributes->set('_navigation_roles', ['ROLE_USER']);

        $html = $this->renderService()->renderGroup('shell.left.middle', $request);

        self::assertStringContainsString('class="interfacing-navigation-provider interfacing-provider-navigation-menu interfacing-provider-navigation-menu--native"', $html);
        self::assertStringContainsString('<ul class="interfacing-menu-list">', $html);
        self::assertStringContainsString('class="interfacing-list-item is-active"', $html);
        self::assertStringContainsString('class="interfacing-nav-link is-active"', $html);
        self::assertStringContainsString('data-navigation-location="shell.left.middle"', $html);
        self::assertStringContainsString('data-navigation-key="catalog"', $html);
        self::assertStringContainsString('href="/catalog/product/index"', $html);
    }

    public function testTwigExtensionExposesCanonicalFunctions(): void
    {
        $requestStack = new RequestStack();
        $request = Request::create('/catalog/product/index');
        $request->attributes->set('_navigation_roles', ['ROLE_USER']);
        $requestStack->push($request);

        $extension = new NavigationTwigExtension(
            $requestStack,
            $this->shellProvider(),
            $this->groupProvider(),
            $this->renderService(),
        );

        $functions = array_map(static fn ($function): string => $function->getName(), $extension->getFunctions());

        self::assertContains('navigating_shell', $functions);
        self::assertContains('navigating_group', $functions);
        self::assertContains('navigating_render', $functions);
        self::assertArrayHasKey('groups', $extension->shell());
        self::assertSame('shell.left.middle', $extension->group('shell.left.middle')['location']);
    }

    private function shellProvider(): NavigationShellProvideService
    {
        $config = [
            'schema' => 3,
            'shell_locations' => [
                'shell.left.middle' => [
                    'label' => 'Primary navigation',
                    'region' => 'left',
                    'slot' => 'middle',
                    'type' => 'navigation',
                    'priority' => 50,
                    'metadata' => ['interface_location' => true],
                ],
            ],
            'shell_groups' => [
                'left_middle_business' => [
                    'label' => 'Business roots',
                    'location' => 'shell.left.middle',
                    'type' => 'navigation',
                    'items' => [
                        'vendor' => [
                            'type' => 'link',
                            'label' => 'Vendor',
                            'path' => '/vendor/profile/index',
                            'metadata' => ['route_name' => 'vendor.profile.index'],
                        ],
                        'catalog' => [
                            'type' => 'link',
                            'label' => 'Catalog',
                            'path' => '/catalog/product/index',
                            'metadata' => ['route_name' => 'catalog.product.index'],
                        ],
                    ],
                ],
            ],
        ];

        return new NavigationShellProvideService(
            new NavigationConfigNormalizeService(),
            new NavigationConfigValidateService(),
            new NavigationVisibilityFilterService(new NavigationRequestRoleProvideService($config), $config),
            new NavigationTreeBuildService(new NavigationTargetResolveService()),
            $config,
        );
    }

    private function groupProvider(): NavigationGroupProvideService
    {
        return new NavigationGroupProvideService($this->shellProvider());
    }

    private function renderService(): NavigationRenderService
    {
        return new NavigationRenderService($this->groupProvider());
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
