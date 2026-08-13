<?php

declare(strict_types=1);

namespace App\Navigating\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

final class NavigationSynchronizationContractTest extends TestCase
{
    public function testMobileInventoryIsBootstrapOnlyAndProjectionUsesCanonicalShell(): void
    {
        $extension = self::read('src/DependencyInjection/NavigationRuntimeExtension.php');
        $provider = self::read('src/Service/Navigation/Provide/NavigationMobileShellPayloadProvideService.php');
        $mobile = Yaml::parse(self::read('config/navigation.mobile.yaml'));

        self::assertStringContainsString("'navigation.mobile.yaml'", $extension);
        self::assertStringContainsString('NavigationShellProvideServiceInterface', $provider);
        self::assertStringNotContainsString('array $navigationConfig', $provider);
        self::assertIsArray($mobile);
        self::assertArrayHasKey('shell.mobile.bottom.primary', $mobile['navigation']['shell_locations']);
        self::assertSame('/message', $mobile['navigation']['shell_groups']['mobile_bottom_primary']['items']['message']['path']);
        self::assertSame('vendor/page', $mobile['navigation']['shell_groups']['mobile_bottom_primary']['items']['profile']['metadata']['mobile_route']);
    }

    public function testAccessLifecyclePortKeepsCurrentPlatformRoutesAndPostSemantics(): void
    {
        $config = Yaml::parse(self::read('config/navigation_access_quick.yaml'));
        $items = $config['navigation']['shell_groups']['right_toolbar_quick']['items'];

        self::assertSame('/access/register', $items['access_register']['path']);
        self::assertSame('/access/recover', $items['access_recovery']['path']);
        self::assertSame('access.recover_request', $items['access_recovery']['metadata']['route_name']);
        self::assertSame('/access/password', $items['access_password']['path']);
        self::assertSame('access.password', $items['access_password']['metadata']['route_name']);
        self::assertSame('POST', $items['access_switch']['metadata']['http_method']);
        self::assertSame('access.switch', $items['access_switch']['metadata']['route_name']);
        self::assertSame('POST', $items['access_signout']['metadata']['http_method']);
        self::assertSame('access.signout', $items['access_signout']['metadata']['route_name']);
    }

    private static function read(string $relativePath): string
    {
        $contents = file_get_contents(dirname(__DIR__).'/'.$relativePath);
        self::assertIsString($contents);

        return $contents;
    }
}
