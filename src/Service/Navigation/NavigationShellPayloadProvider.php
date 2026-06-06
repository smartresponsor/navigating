<?php

declare(strict_types=1);

namespace App\Navigating\Service\Navigation;

use App\Navigating\ServiceInterface\Navigation\NavigationShellPayloadProviderInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class NavigationShellPayloadProvider implements NavigationShellPayloadProviderInterface
{
    public function __construct(
        private NavigationRuntimeProvider $runtimeProvider,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function provideShellNavigation(Request $request): array
    {
        return [
            'navigation' => [
                'locations' => $this->runtimeProvider->provideLocations($request),
                'active' => $this->runtimeProvider->provideActiveState($request),
            ],
        ];
    }
}
