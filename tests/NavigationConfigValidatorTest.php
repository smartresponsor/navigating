<?php

declare(strict_types=1);

namespace App\Navigating\Tests;

use App\Navigating\Service\Navigation\NavigationConfigValidator;
use PHPUnit\Framework\TestCase;

final class NavigationConfigValidatorTest extends TestCase
{
    public function testAcceptsShellGroupsOnlyConfig(): void
    {
        $result = (new NavigationConfigValidator())->validate([
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
        ]);

        self::assertTrue($result->isValid(), implode(' ', $result->errors));
    }

    public function testRejectsRemovedLegacyRootFooterAndSlotKeys(): void
    {
        $result = (new NavigationConfigValidator())->validate([
            'schema' => 3,
            'slots' => ['roots' => 'shell.left.middle'],
            'roots' => [],
            'footer' => [],
            'shell_groups' => [],
        ]);

        self::assertFalse($result->isValid());
        self::assertStringContainsString('navigation.slots has been removed', implode(' ', $result->errors));
        self::assertStringContainsString('navigation.roots has been removed', implode(' ', $result->errors));
        self::assertStringContainsString('navigation.footer has been removed', implode(' ', $result->errors));
    }

    public function testRejectsNonCanonicalShellLocation(): void
    {
        $result = (new NavigationConfigValidator())->validate([
            'schema' => 3,
            'shell_groups' => [
                'legacy' => [
                    'location' => 'shell.left.primary',
                    'items' => [],
                ],
            ],
        ]);

        self::assertFalse($result->isValid());
        self::assertStringContainsString('must use a canonical shell location', implode(' ', $result->errors));
        self::assertStringContainsString('shell.left.primary', implode(' ', $result->errors));
    }

    public function testAcceptsProcessedConfigurationDefaultsForTypedItems(): void
    {
        $result = (new NavigationConfigValidator())->validate([
            'schema' => 3,
            'shell_groups' => [
                'left_middle_primary' => [
                    'location' => 'shell.left.middle',
                    'items' => [
                        'dashboard' => [
                            'type' => 'link',
                            'label' => 'Dashboard',
                            'target' => [],
                            'route' => null,
                            'path' => '/',
                        ],
                    ],
                ],
                'main_toolbar_actions' => [
                    'location' => 'shell.main.toolbar',
                    'items' => [
                        'refresh' => [
                            'type' => 'action',
                            'label' => 'Refresh',
                            'target' => [],
                            'route' => null,
                            'path' => null,
                            'action' => 'navigation.refresh',
                        ],
                    ],
                ],
                'footer_context_environment' => [
                    'location' => 'shell.footer.context',
                    'items' => [
                        'environment' => [
                            'type' => 'badge',
                            'label' => 'Environment',
                            'target' => [],
                            'route' => null,
                            'path' => null,
                            'badge' => 'dev',
                        ],
                    ],
                ],
            ],
        ]);

        self::assertTrue($result->isValid(), implode(' ', $result->errors));
    }

    public function testRejectsMissingShellItemType(): void
    {
        $result = (new NavigationConfigValidator())->validate([
            'schema' => 3,
            'shell_groups' => [
                'left_middle_primary' => [
                    'location' => 'shell.left.middle',
                    'items' => [
                        'dashboard' => [
                            'label' => 'Dashboard',
                            'path' => '/',
                        ],
                    ],
                ],
            ],
        ]);

        self::assertFalse($result->isValid());
        self::assertStringContainsString('type must be configured', implode(' ', $result->errors));
    }

    public function testRejectsInvalidShellItemType(): void
    {
        $result = (new NavigationConfigValidator())->validate([
            'schema' => 3,
            'shell_groups' => [
                'left_middle_primary' => [
                    'location' => 'shell.left.middle',
                    'items' => [
                        'dashboard' => [
                            'type' => 'menu',
                            'label' => 'Dashboard',
                            'path' => '/',
                        ],
                    ],
                ],
            ],
        ]);

        self::assertFalse($result->isValid());
        self::assertStringContainsString('type must be one of', implode(' ', $result->errors));
        self::assertStringContainsString('menu', implode(' ', $result->errors));
    }

    public function testRejectsActionWithoutActionToken(): void
    {
        $result = (new NavigationConfigValidator())->validate([
            'schema' => 3,
            'shell_groups' => [
                'main_toolbar_actions' => [
                    'location' => 'shell.main.toolbar',
                    'items' => [
                        'refresh' => [
                            'type' => 'action',
                            'label' => 'Refresh',
                        ],
                    ],
                ],
            ],
        ]);

        self::assertFalse($result->isValid());
        self::assertStringContainsString('action item must configure a non-empty action token', implode(' ', $result->errors));
    }

    public function testAcceptsTargetlessHeadingAndSeparator(): void
    {
        $result = (new NavigationConfigValidator())->validate([
            'schema' => 3,
            'shell_groups' => [
                'left_middle_primary' => [
                    'location' => 'shell.left.middle',
                    'items' => [
                        'management' => [
                            'type' => 'heading',
                            'label' => 'Management',
                        ],
                        'line' => [
                            'type' => 'separator',
                        ],
                    ],
                ],
            ],
        ]);

        self::assertTrue($result->isValid(), implode(' ', $result->errors));
    }

    public function testRejectsTargetOnTargetlessItem(): void
    {
        $result = (new NavigationConfigValidator())->validate([
            'schema' => 3,
            'shell_groups' => [
                'main_toolbar_actions' => [
                    'location' => 'shell.main.toolbar',
                    'items' => [
                        'refresh' => [
                            'type' => 'action',
                            'label' => 'Refresh',
                            'action' => 'navigation.refresh',
                            'path' => '/refresh',
                        ],
                    ],
                ],
            ],
        ]);

        self::assertFalse($result->isValid());
        self::assertStringContainsString('path is only supported for link items', implode(' ', $result->errors));
    }

    public function testRejectsAnyNestedItemShapeKeyIndependently(): void
    {
        foreach (['items', 'sections', 'children'] as $nestedKey) {
            $result = (new NavigationConfigValidator())->validate([
                'schema' => 3,
                'shell_groups' => [
                    'left_middle_primary' => [
                        'location' => 'shell.left.middle',
                        'items' => [
                            'dashboard' => [
                                'type' => 'link',
                                'label' => 'Dashboard',
                                'path' => '/',
                                $nestedKey => [],
                            ],
                        ],
                    ],
                ],
            ]);

            self::assertFalse($result->isValid(), $nestedKey);
            self::assertStringContainsString(
                'must not contain nested items/sections/children',
                implode(' ', $result->errors),
                $nestedKey,
            );
        }
    }

    public function testAcceptsScopeAndEnvironmentVisibility(): void
    {
        $result = (new NavigationConfigValidator())->validate([
            'schema' => 3,
            'runtime_scopes' => [
                'fallback_scopes' => ['user', 'system'],
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
        ]);

        self::assertTrue($result->isValid(), implode(' ', $result->errors));
    }

    public function testRejectsInvalidScopeAndEnvironmentVisibility(): void
    {
        $result = (new NavigationConfigValidator())->validate([
            'schema' => 3,
            'runtime_scopes' => [
                'fallback_scopes' => ['user', ''],
            ],
            'runtime_environment' => [
                'fallback_environment' => '',
            ],
            'shell_groups' => [
                'right_tool_panel' => [
                    'location' => 'shell.right.tool',
                    'visible_for_scopes' => 'system',
                    'items' => [
                        'inspector' => [
                            'type' => 'widget',
                            'label' => 'Inspector',
                            'widget' => 'shell.inspector',
                            'visible_for_environments' => ['dev', ''],
                        ],
                    ],
                ],
            ],
        ]);

        self::assertFalse($result->isValid());
        self::assertStringContainsString('runtime_scopes.fallback_scopes must contain only non-empty scope strings', implode(' ', $result->errors));
        self::assertStringContainsString('runtime_environment.fallback_environment must be a non-empty string', implode(' ', $result->errors));
        self::assertStringContainsString('visible_for_scopes must be a list of scope strings', implode(' ', $result->errors));
        self::assertStringContainsString('visible_for_environments must contain only non-empty environment strings', implode(' ', $result->errors));
    }
}
