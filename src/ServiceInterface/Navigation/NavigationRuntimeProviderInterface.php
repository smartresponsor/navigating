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

    /**
     * Returns deterministic active navigation identifiers.
     *
     * @return array{active_group: string|null, active_item: string|null, active_root: string|null, active_section: string|null}
     */
    public function provideActiveState(Request $request): array;
}
