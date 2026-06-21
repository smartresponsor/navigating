<?php

declare(strict_types=1);

namespace App\Navigating\Tests;

use PHPUnit\Framework\TestCase;

final class NavigationRuntimeActivationConfigTest extends TestCase
{
    public function testDefaultRuntimeActivationUsesNamespaceOwnership(): void
    {
        $config = [
            'runtime_activation' => [
                'strict' => true,
            ],
            'shell_groups' => [
                'left_top' => [
                    'items' => [
                        'dashboard' => [
                            'metadata' => [
                                'namespace_provider' => 'App\\Interfacing',
                            ],
                        ],
                    ],
                ],
                'left_middle_business' => [
                    'items' => [
                        'vendor' => [
                            'metadata' => [
                                'namespace_provider' => 'App\\Vendoring',
                            ],
                        ],
                    ],
                ],
            ],
        ];

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
