<?php

declare(strict_types=1);

namespace App\Navigating\Tests;

use App\Navigating\DependencyInjection\NavigationExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class NavigationRuntimeActivationConfigTest extends TestCase
{
    public function testDefaultRuntimeActivationIsStrict(): void
    {
        $container = new ContainerBuilder();
        (new NavigationExtension())->load([], $container);

        $config = $container->getParameter('navigation.config');

        self::assertIsArray($config);
        self::assertTrue($config['runtime_activation']['strict']);
    }
}
