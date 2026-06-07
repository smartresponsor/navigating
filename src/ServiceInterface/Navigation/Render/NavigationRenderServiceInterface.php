<?php

declare(strict_types=1);

namespace App\Navigating\ServiceInterface\Navigation\Render;

use Symfony\Component\HttpFoundation\Request;

interface NavigationRenderServiceInterface
{
    public function renderGroup(string $location, Request $request): string;
}
