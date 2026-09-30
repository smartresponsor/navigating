<?php

declare(strict_types=1);

namespace App\Navigating\ServiceInterface\Provide;

use App\Navigating\Value\Context\NavigationRuntimeActivationContext;

interface NavigationRuntimeActivationProvideServiceInterface
{
    public function provide(): NavigationRuntimeActivationContext;
}
