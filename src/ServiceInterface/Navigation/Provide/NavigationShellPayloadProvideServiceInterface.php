<?php

declare(strict_types=1);

namespace App\Navigating\ServiceInterface\Navigation\Provide;

use Symfony\Component\HttpFoundation\Request;

interface NavigationShellPayloadProvideServiceInterface
{
    /**
     * @return array<string, mixed>
     */
    public function provideShellNavigation(Request $request): array;
}
