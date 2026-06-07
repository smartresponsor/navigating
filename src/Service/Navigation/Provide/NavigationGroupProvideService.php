<?php

declare(strict_types=1);

namespace App\Navigating\Service\Navigation\Provide;

use App\Navigating\Model\Navigation\View\NavigationGroupView;
use App\Navigating\ServiceInterface\Navigation\Provide\NavigationGroupProvideServiceInterface;
use App\Navigating\ServiceInterface\Navigation\Provide\NavigationShellProvideServiceInterface;
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
