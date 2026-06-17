<?php

declare(strict_types=1);

namespace App\Navigating\ServiceInterface\Navigation\Provide;

use App\Navigating\Model\Navigation\Context\NavigationRuntimeActivationContext;

interface NavigationRuntimeActivationProvideServiceInterface
{
    public function provide(): NavigationRuntimeActivationContext;
}
