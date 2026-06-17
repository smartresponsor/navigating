<?php

declare(strict_types=1);

namespace App\Navigating\Tests;

use App\Navigating\Service\Navigation\Filter\NavigationRuntimeTargetFilterService;
use App\Navigating\Service\Navigation\Provide\NavigationRuntimeActivationProvideService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class NavigationRuntimeActivationTest extends TestCase
{
    public function testExactEntityTokenAllowsMatchingFirstPathSegment(): void
    {
        $filter = $this->filter(runtimeScope: 'cruding,viewing', runtimeEntity: 'vendor');

        self::assertTrue($filter->allows('/vendor/index', Request::create('/app')));
    }

    public function testSemanticAliasCannotActivateDifferentUrlPrefix(): void
    {
        $filter = $this->filter(runtimeScope: '', runtimeEntity: 'category,product');
        $request = Request::create('/app');

        self::assertFalse($filter->allows('/catalog/index', $request));
        self::assertFalse($filter->allows('/merchandise/index', $request));
    }

    public function testArchitecturalAliasCannotActivateDifferentUrlPrefix(): void
    {
        $filter = $this->filter(runtimeScope: 'interfacing', runtimeEntity: '');

        self::assertFalse($filter->allows('/interface/index', Request::create('/app')));
    }

    public function testTokenMayComeFromScopeOrEntityRuntimeList(): void
    {
        $request = Request::create('/app');

        self::assertTrue($this->filter('catalog', '')->allows('/catalog/index', $request));
        self::assertTrue($this->filter('', 'catalog')->allows('/catalog/index', $request));
    }

    public function testPrefixMatchingUsesTheWholeFirstPathSegment(): void
    {
        $filter = $this->filter(runtimeScope: '', runtimeEntity: 'vendor');

        self::assertFalse($filter->allows('/vendor-extra/index', Request::create('/app')));
    }

    public function testSameHostAbsoluteUrlIsRuntimeGated(): void
    {
        $filter = $this->filter(runtimeScope: '', runtimeEntity: 'vendor');
        $request = Request::create('https://smartresponsor.com/app');

        self::assertTrue($filter->allows('https://smartresponsor.com/vendor/index', $request));
        self::assertFalse($filter->allows('https://smartresponsor.com/catalog/index', $request));
    }

    public function testExternalUrlDoesNotRepresentAnInstalledLocalComponent(): void
    {
        $filter = $this->filter(runtimeScope: '', runtimeEntity: 'vendor');
        $request = Request::create('https://smartresponsor.com/app');

        self::assertTrue($filter->allows('https://example.com/catalog/index', $request));
    }

    public function testHostRootIsNotAComponentPrefix(): void
    {
        $filter = $this->filter(runtimeScope: '', runtimeEntity: '');

        self::assertTrue($filter->allows('/', Request::create('/')));
    }

    public function testEmptyLinkTargetIsRejected(): void
    {
        $filter = $this->filter(runtimeScope: 'vendor', runtimeEntity: 'vendor');

        self::assertFalse($filter->allows('', Request::create('/app')));
    }

    private function filter(string $runtimeScope, string $runtimeEntity): NavigationRuntimeTargetFilterService
    {
        return new NavigationRuntimeTargetFilterService(
            new NavigationRuntimeActivationProvideService(
                runtimeScope: $runtimeScope,
                runtimeEntity: $runtimeEntity,
                runtimeActivationStrict: true,
            ),
        );
    }
}
