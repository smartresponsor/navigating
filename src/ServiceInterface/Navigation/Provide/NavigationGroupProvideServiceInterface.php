<?php

declare(strict_types=1);

namespace App\Navigating\ServiceInterface\Navigation\Provide;

use App\Navigating\Model\Navigation\View\NavigationGroupView;
use Symfony\Component\HttpFoundation\Request;

interface NavigationGroupProvideServiceInterface
{
    public function provideGroup(string $location, Request $request): NavigationGroupView;
}
