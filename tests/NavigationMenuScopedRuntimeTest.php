<?php

declare(strict_types=1);

namespace App\Navigating\Tests;

use App\Navigating\Service\Navigation\Filter\NavigationVisibilityFilterService;
use App\Navigating\Service\Navigation\Normalize\NavigationConfigNormalizeService;
use App\Navigating\Service\Navigation\Provide\NavigationRuntimeActivationProvideService;
use App\Navigating\ServiceInterface\Navigation\Provide\NavigationRequestRoleProvideServiceInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class NavigationMenuScopedRuntimeTest extends TestCase
{
    public function testIdenticalItemKeysRemainScopedToTheirOwnMenu(): void
    {
        $config = [
            'runtime_scopes' => ['fallback_scopes' => []],
            'runtime_environment' => ['fallback_environment' => null],
            'shell_groups' => [
                'first_menu' => $this->group([
                    'root' => $this->item('First root'),
                    'child' => $this->item('First child', parentKey: 'root'),
                ]),
                'second_menu' => $this->group([
                    'root' => $this->item('Second root'),
                    'child' => $this->item('Second child', parentKey: 'root'),
                ]),
            ],
        ];

        $groups = (new NavigationConfigNormalizeService())->normalizeShellGroups($config);
        $filtered = $this->filter($groups, $config, ['ROLE_USER']);

        self::assertCount(2, $filtered);
        self::assertSame(['root', 'child'], array_map(static fn ($item): string => $item->key, $filtered[0]->items));
        self::assertSame(['root', 'child'], array_map(static fn ($item): string => $item->key, $filtered[1]->items));
        self::assertSame('First child', $filtered[0]->items[1]->label);
        self::assertSame('Second child', $filtered[1]->items[1]->label);
    }

    public function testHiddenAncestorInOneMenuDoesNotSuppressSameKeyInAnotherMenu(): void
    {
        $firstRoot = $this->item('First root');
        $firstRoot['visible_for_roles'] = ['ROLE_ADMIN'];

        $config = [
            'runtime_scopes' => ['fallback_scopes' => []],
            'runtime_environment' => ['fallback_environment' => null],
            'shell_groups' => [
                'first_menu' => $this->group([
                    'root' => $firstRoot,
                    'child' => $this->item('First child', parentKey: 'root'),
                ]),
                'second_menu' => $this->group([
                    'root' => $this->item('Second root'),
                    'child' => $this->item('Second child', parentKey: 'root'),
                ]),
            ],
        ];

        $groups = (new NavigationConfigNormalizeService())->normalizeShellGroups($config);
        $filtered = $this->filter($groups, $config, ['ROLE_USER']);

        self::assertCount(2, $filtered);
        self::assertSame([], array_map(static fn ($item): string => $item->key, $filtered[0]->items));
        self::assertSame(['root', 'child'], array_map(static fn ($item): string => $item->key, $filtered[1]->items));
    }

    /** @param array<string, array<string, mixed>> $items */
    private function group(array $items): array
    {
        return [
            'label' => 'Menu',
            'location' => 'shell.left.middle',
            'type' => 'navigation',
            'metadata' => ['namespace_provider' => 'App\\Interfacing'],
            'items' => $items,
        ];
    }

    /** @return array<string, mixed> */
    private function item(string $label, ?string $parentKey = null): array
    {
        $metadata = [];
        if (null !== $parentKey) {
            $metadata['parent_key'] = $parentKey;
        }

        return [
            'type' => 'link',
            'label' => $label,
            'path' => '/'.strtolower(str_replace(' ', '-', $label)),
            'metadata' => $metadata,
        ];
    }

    /**
     * @param list<\App\Navigating\Value\Navigation\NavigationShellGroup> $groups
     * @param array<string, mixed> $config
     * @param list<string> $roles
     *
     * @return list<\App\Navigating\Value\Navigation\NavigationShellGroup>
     */
    private function filter(array $groups, array $config, array $roles): array
    {
        $roleProvider = new class($roles) implements NavigationRequestRoleProvideServiceInterface {
            /** @param list<string> $roles */
            public function __construct(private readonly array $roles)
            {
            }

            public function provideRoles(Request $request): array
            {
                return $this->roles;
            }
        };

        return (new NavigationVisibilityFilterService(
            roleProvider: $roleProvider,
            runtimeActivationProvider: new NavigationRuntimeActivationProvideService(
                runtimeScope: 'interfacing',
                runtimeEntity: '',
                runtimeActivationStrict: true,
            ),
            navigationConfig: $config,
        ))->filterShellGroups($groups, Request::create('/'));
    }
}
