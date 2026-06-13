<?php

declare(strict_types=1);

namespace App\Navigating\ServiceInterface\Navigation\Provide;

use Symfony\Component\HttpFoundation\Request;

interface NavigationInterfaceLocationProjectionProviderInterface
{
    /**
     * @return array<string, list<array<string, mixed>>>
     */
    public function provideInterfaceLocations(Request $request): array;

    /**
     * @return array{locations: array<string, list<array<string, mixed>>>, active: array<string, mixed>}
     */
    public function provideInterfacePayload(Request $request): array;
}
