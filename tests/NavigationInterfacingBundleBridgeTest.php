<?php

declare(strict_types=1);

namespace App\Navigating\Tests;

use App\Navigating\Model\Navigation\View\NavigationTargetView;
use App\Navigating\Service\Navigation\Provide\NavigationResponseProvideService;
use App\Navigating\Service\Navigation\Provide\NavigationTemplateDataProvideService;
use App\Navigating\Service\Navigation\Render\NavigationTemplateRenderService;
use App\Navigating\ServiceInterface\Navigation\NavigationRendererInterface;
use App\Navigating\ServiceInterface\Navigation\Provide\NavigationTemplateDataProvideServiceInterface;
use App\Navigating\ServiceInterface\Navigation\Render\NavigationTemplateRenderServiceInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class NavigationInterfacingBundleBridgeTest extends TestCase
{
    public function testTemplateRendererImplementsBothBundleAndCanonicalContracts(): void
    {
        $renderer = new NavigationTemplateRenderService(null);

        self::assertInstanceOf(NavigationRendererInterface::class, $renderer);
        self::assertInstanceOf(NavigationTemplateRenderServiceInterface::class, $renderer);
    }

    public function testNavigationTargetExportsHrefAndUrlForInterfacingTemplates(): void
    {
        $target = new NavigationTargetView(type: 'path', href: '/vendor/index');

        self::assertSame('/vendor/index', $target->toArray()['href']);
        self::assertSame('/vendor/index', $target->toArray()['url']);
    }

    public function testResponsePayloadExposesRootNavigationForBundleInterfacingRenderer(): void
    {
        $provider = new NavigationResponseProvideService(new class implements NavigationTemplateDataProvideServiceInterface {
            /**
             * @return array<string, mixed>
             */
            public function provide(Request $request): array
            {
                return [
                    'surface' => NavigationTemplateDataProvideService::SURFACE,
                    'template' => NavigationTemplateDataProvideService::TEMPLATE,
                    'navigation' => [
                        'locations' => [
                            'shell.left.middle' => [
                                ['label' => 'Vendors', 'href' => '/vendor/index', 'url' => '/vendor/index'],
                            ],
                        ],
                        'groups' => [],
                        'active' => ['path' => '/vendor/index'],
                    ],
                ];
            }
        });

        $payload = $provider->providePayload(Request::create('/vendor/index'));

        self::assertArrayHasKey('navigation', $payload);
        self::assertArrayHasKey('locations', $payload['navigation']);
        self::assertSame($payload['navigation']['locations'], $payload['locations']);
        self::assertSame($payload['navigation']['groups'], $payload['groups']);
        self::assertSame($payload['navigation']['active'], $payload['active']);
    }

    public function testBundleServicesExportInterfacingBridgeAliases(): void
    {
        $services = (string) file_get_contents(__DIR__.'/../config/services.yaml');

        self::assertStringContainsString('NavigationTemplateRenderServiceInterface', $services);
        self::assertStringContainsString('navigating.shell_payload_provider', $services);
        self::assertStringContainsString('navigating.response_provider', $services);
        self::assertStringContainsString('navigating.template_data_provider', $services);
        self::assertStringContainsString('navigating.renderer', $services);
        self::assertStringContainsString('twig.extension', $services);
    }
}
