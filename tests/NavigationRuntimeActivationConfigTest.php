<?php

declare(strict_types=1);

namespace App\Navigating\Tests;

use App\Navigating\DependencyInjection\NavigationExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class NavigationRuntimeActivationConfigTest extends TestCase
{
    public function testDefaultRuntimeActivationUsesNamespaceOwnership(): void
    {
        $container = new ContainerBuilder();
        (new NavigationExtension())->load([], $container);

        $config = $container->getParameter('navigation.config');

        self::assertIsArray($config);
        self::assertTrue($config['runtime_activation']['strict']);
        self::assertSame(
            'App\\Interfacing',
            $config['shell_groups']['left_top']['items']['dashboard']['metadata']['namespace_provider'],
        );
        self::assertSame(
            'App\\Vendoring',
            $config['shell_groups']['left_middle_business']['items']['vendor']['metadata']['namespace_provider'],
        );
    }
}
