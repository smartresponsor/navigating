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
    public function testNamespaceProviderDerivesWorkspaceScope(): void
    {
        self::assertSame(
            ['payment'],
            $this->visibleKeys([
                'payment' => $this->item(
                    path: '/payment/index',
                    namespaceProvider: 'App\\Cruding\\Service\\Http\\Payment\\PaymentIndexService',
                ),
            ], 'cruding'),
        );
    }

    public function testNamespaceIsFallbackWhenProviderIsAbsent(): void
    {
        self::assertSame(
            ['attachment'],
            $this->visibleKeys([
                'attachment' => $this->item(
                    path: '/attachment/index',
                    namespace: 'App\\Viewing\\Projection',
                ),
            ], 'viewing'),
        );
    }

    public function testNamespaceProviderHasPriorityOverNamespace(): void
    {
        self::assertSame(
            ['order'],
            $this->visibleKeys([
                'order' => $this->item(
                    path: '/order/index',
                    namespaceProvider: 'App\\Cruding\\RouteProvider',
                    namespace: 'App\\Ordering\\Entity',
                ),
            ], 'cruding'),
        );
    }

    public function testUriPrefixDoesNotSelectRuntimeScope(): void
    {
        $items = [
            'payment' => $this->item(
                path: '/payment/index',
                namespaceProvider: 'App\\Paying\\Service\\PaymentIndexService',
            ),
        ];

        self::assertSame([], $this->visibleKeys($items, 'cruding'));
        self::assertSame(['payment'], $this->visibleKeys($items, 'paying'));
    }

    public function testMissingNamespaceOwnershipFailsClosed(): void
    {
        self::assertSame(
            [],
            $this->visibleKeys([
                'vendor' => $this->item(path: '/vendor/index'),
            ], 'vendoring'),
        );
    }

    public function testGroupNamespaceProviderIsInheritedByItems(): void
    {
        self::assertSame(
            ['dashboard'],
            $this->visibleKeys(
                ['dashboard' => $this->item(path: '/app')],
                'interfacing',
                groupNamespaceProvider: 'App\\Interfacing',
            ),
        );
    }

    /**
     * @param array<string, array<string, mixed>> $items
     *
     * @return list<string>
     */
    private function visibleKeys(array $items, string $runtimeScope, ?string $groupNamespaceProvider = null): array
    {
        $group = [
            'label' => 'Test',
            'location' => 'shell.left.middle',
            'type' => 'navigation',
            'items' => $items,
        ];

        if (null !== $groupNamespaceProvider) {
            $group['metadata'] = ['namespace_provider' => $groupNamespaceProvider];
        }

        $config = [
            'runtime_scopes' => ['fallback_scopes' => []],
            'runtime_environment' => ['fallback_environment' => null],
            'shell_groups' => ['test' => $group],
        ];

        $groups = (new NavigationConfigNormalizeService())->normalizeShellGroups($config);
        $filtered = (new NavigationVisibilityFilterService(
            roleProvider: new class implements NavigationRequestRoleProvideServiceInterface {
                public function provideRoles(Request $request): array
                {
                    return ['ROLE_USER'];
                }
            },
            runtimeActivationProvider: new NavigationRuntimeActivationProvideService(
                runtimeScope: $runtimeScope,
                runtimeEntity: '',
                runtimeActivationStrict: true,
            ),
            navigationConfig: $config,
        ))->filterShellGroups($groups, Request::create('/app'));

        if ([] === $filtered) {
            return [];
        }

        return array_map(
            static fn ($item): string => $item->key,
            $filtered[0]->items,
        );
    }

    /** @return array<string, mixed> */
    private function item(
        string $path,
        ?string $namespaceProvider = null,
        ?string $namespace = null,
    ): array {
        $metadata = [];

        if (null !== $namespaceProvider) {
            $metadata['namespace_provider'] = $namespaceProvider;
        }

        if (null !== $namespace) {
            $metadata['namespace'] = $namespace;
        }

        return [
            'type' => 'link',
            'label' => ucfirst(trim($path, '/')),
            'path' => $path,
            'metadata' => $metadata,
        ];
    }
}
