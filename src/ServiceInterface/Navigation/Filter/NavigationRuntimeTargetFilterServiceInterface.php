<?php

declare(strict_types=1);

namespace App\Navigating\ServiceInterface\Navigation\Filter;

use Symfony\Component\HttpFoundation\Request;

interface NavigationRuntimeTargetFilterServiceInterface
{
    public function allows(string $href, Request $request): bool;
}
