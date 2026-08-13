<?php

declare(strict_types=1);

namespace App\Navigating\Tests;

use App\Navigating\DependencyInjection\NavigationExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class NavigationExtensionTest extends TestCase
{
    public function testExtensionLoadsCanonicalBootstrapNavigationConfig(): void
    {
        $container = new ContainerBuilder();

        (new NavigationExtension())->load([], $container);

        self::assertTrue($container->hasParameter('navigation.config'));

        $config = $container->getParameter('navigation.config');

        self::assertIsArray($config);
        self::assertSame(3, $config['schema']);
        self::assertArrayNotHasKey('left_middle_primary', $config['shell_groups'] ?? []);
        self::assertArrayHasKey('left_middle_business', $config['shell_groups'] ?? []);
        self::assertArrayHasKey('body_top', $config['shell_groups'] ?? []);
    }

    public function testExtensionUsesExplicitApplicationNavigationConfigOnly(): void
    {
        $container = new ContainerBuilder();

        (new NavigationExtension())->load([
            [
                'schema' => 3,
                'shell_groups' => [
                    'left_middle_primary' => [
                        'location' => 'shell.left.middle',
                        'items' => [
                            'dashboard' => [
                                'type' => 'link',
                                'path' => '/',
                            ],
                        ],
                    ],
                ],
            ],
        ], $container);

        $config = $container->getParameter('navigation.config');

        self::assertIsArray($config);
        self::assertArrayHasKey('left_middle_primary', $config['shell_groups']);
        self::assertArrayNotHasKey('body_top_workspace', $config['shell_groups']);
    }
}
