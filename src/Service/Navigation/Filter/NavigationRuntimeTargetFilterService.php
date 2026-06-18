<?php

declare(strict_types=1);

namespace App\Navigating\Service\Navigation\Filter;

use App\Navigating\ServiceInterface\Navigation\Filter\NavigationRuntimeTargetFilterServiceInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class NavigationRuntimeTargetFilterService implements NavigationRuntimeTargetFilterServiceInterface
{
    public function allows(string $href, Request $request): bool
    {
        return true;
    }
}
