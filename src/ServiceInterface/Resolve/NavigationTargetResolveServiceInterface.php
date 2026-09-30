<?php

declare(strict_types=1);

namespace App\Navigating\ServiceInterface\Resolve;

use App\Navigating\Value\NavigationTarget;

interface NavigationTargetResolveServiceInterface
{
    public function resolveUrl(NavigationTarget $target): string;
}
