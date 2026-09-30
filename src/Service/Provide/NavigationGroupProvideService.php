<?php

declare(strict_types=1);

namespace App\Navigating\Service\Provide;

use App\Navigating\ServiceInterface\Provide\NavigationGroupProvideServiceInterface;
use App\Navigating\ServiceInterface\Provide\NavigationShellProvideServiceInterface;
use App\Navigating\Value\View\NavigationGroupView;
use Symfony\Component\HttpFoundation\Request;

final readonly class NavigationGroupProvideService implements NavigationGroupProvideServiceInterface
{
    public function __construct(
        private NavigationShellProvideServiceInterface $shellProvideService,
    ) {
    }

    public function provideGroup(string $location, Request $request): NavigationGroupView
    {
        return $this->shellProvideService->provideShell($request)->group($location);
    }
}
