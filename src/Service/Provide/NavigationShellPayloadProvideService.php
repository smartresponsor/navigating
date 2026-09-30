<?php

declare(strict_types=1);

namespace App\Navigating\Service\Provide;

use App\Navigating\ServiceInterface\Provide\NavigationShellPayloadProvideServiceInterface;
use App\Navigating\ServiceInterface\Provide\NavigationShellProvideServiceInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class NavigationShellPayloadProvideService implements NavigationShellPayloadProvideServiceInterface
{
    public function __construct(
        private NavigationShellProvideServiceInterface $shellProvideService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function provideShellNavigation(Request $request): array
    {
        $shell = $this->shellProvideService->provideShell($request);

        return [
            'interface' => [
                'locations' => $shell->toLocationsArray(),
                'active' => $this->shellProvideService->provideActiveState($request),
            ],
        ];
    }
}
