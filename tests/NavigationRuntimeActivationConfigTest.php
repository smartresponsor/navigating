<?php

declare(strict_types=1);

namespace App\Navigating\Tests;

use App\Navigating\DependencyInjection\NavigationExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class NavigationRuntimeActivationConfigTest extends TestCase
{
    public function testDefaultRuntimeActivationMapsAreMergedIntoNavigationConfig(): void
    {
        $container = new ContainerBuilder();
        (new NavigationExtension())->load([], $container);

        $config = $container->getParameter('navigation.config');
        self::assertIsArray($config);
        self::assertSame(['vendoring'], $config['runtime_activation']['scope_by_domain']['vendor']);
        self::assertSame(['accessing'], $config['runtime_activation']['scope_by_domain']['access']);
        self::assertSame(['vendor'], $config['runtime_activation']['entity_by_domain']['vendor']);
        self::assertTrue($config['runtime_activation']['strict']);
    }
}
