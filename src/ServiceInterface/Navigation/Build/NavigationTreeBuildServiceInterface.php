<?php

declare(strict_types=1);

namespace App\Navigating\ServiceInterface\Navigation\Build;

use App\Navigating\Model\Navigation\View\NavigationGroupView;
use App\Navigating\Value\Navigation\NavigationShellGroup;
use Symfony\Component\HttpFoundation\Request;

interface NavigationTreeBuildServiceInterface
{
    public function buildGroup(NavigationShellGroup $group, Request $request): NavigationGroupView;
}
