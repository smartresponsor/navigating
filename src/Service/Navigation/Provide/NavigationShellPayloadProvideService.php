<?php

declare(strict_types=1);

namespace App\Navigating\Service\Navigation\Provide;

use App\Navigating\ServiceInterface\Navigation\Provide\NavigationShellPayloadProvideServiceInterface;
use App\Navigating\ServiceInterface\Navigation\Provide\NavigationShellProvideServiceInterface;
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
            'navigation' => [
                'locations' => $shell->toLocationsArray(),
                'groups' => $shell->toArray()['groups'],
                'active' => $this->shellProvideService->provideActiveState($request),
            ],
        ];
    }
}
