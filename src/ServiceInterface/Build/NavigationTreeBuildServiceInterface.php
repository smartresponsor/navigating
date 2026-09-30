<?php

declare(strict_types=1);

namespace App\Navigating\ServiceInterface\Build;

use App\Navigating\Value\NavigationShellGroup;
use App\Navigating\Value\View\NavigationGroupView;
use Symfony\Component\HttpFoundation\Request;

interface NavigationTreeBuildServiceInterface
{
    public function buildGroup(NavigationShellGroup $group, Request $request): NavigationGroupView;
}
