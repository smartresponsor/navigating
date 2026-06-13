<?php

declare(strict_types=1);

namespace App\Navigating\Service\Navigation\Provide;

use App\Navigating\ServiceInterface\Navigation\Provide\NavigationInterfaceLocationProjectionProviderInterface;
use App\Navigating\ServiceInterface\Navigation\Provide\NavigationShellProvideServiceInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class NavigationInterfaceLocationProjectionProvider implements NavigationInterfaceLocationProjectionProviderInterface
{
    public function __construct(
        private NavigationShellProvideServiceInterface $shellProvideService,
    ) {
    }

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    public function provideInterfaceLocations(Request $request): array
    {
        return $this->shellProvideService->provideShell($request)->toLocationsArray();
    }

    /**
     * @return array{locations: array<string, list<array<string, mixed>>>, active: array<string, mixed>}
     */
    public function provideInterfacePayload(Request $request): array
    {
        return [
            'locations' => $this->provideInterfaceLocations($request),
            'active' => $this->shellProvideService->provideActiveState($request),
        ];
    }
}
