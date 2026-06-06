<?php

declare(strict_types=1);

namespace App\Navigating\ServiceInterface\Navigation;

use Symfony\Component\HttpFoundation\Request;

interface NavigationRuntimeProviderInterface
{
    /**
     * Returns slot-ready navigation payload.
     *
     * @return array<string, list<array<string, mixed>>>
     */
    public function provideLocations(Request $request): array;
}
