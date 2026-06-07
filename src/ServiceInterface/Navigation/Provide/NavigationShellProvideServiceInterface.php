<?php

declare(strict_types=1);

namespace App\Navigating\ServiceInterface\Navigation\Provide;

use App\Navigating\Model\Navigation\View\NavigationShellView;
use Symfony\Component\HttpFoundation\Request;

interface NavigationShellProvideServiceInterface
{
    public function provideShell(Request $request): NavigationShellView;

    /**
     * @return array{active_group: string|null, active_item: string|null, active_root: string|null, active_section: string|null}
     */
    public function provideActiveState(Request $request): array;
}
