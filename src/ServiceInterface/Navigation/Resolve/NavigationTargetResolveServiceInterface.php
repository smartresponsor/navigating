<?php

declare(strict_types=1);

namespace App\Navigating\ServiceInterface\Navigation\Resolve;

use App\Navigating\Value\Navigation\NavigationTarget;

interface NavigationTargetResolveServiceInterface
{
    public function resolveUrl(NavigationTarget $target): string;
}
