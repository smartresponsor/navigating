<?php

declare(strict_types=1);

namespace App\Navigating\ServiceInterface\Provide;

use Symfony\Component\HttpFoundation\Request;

interface NavigationShellPayloadProvideServiceInterface
{
    /**
     * @return array<string, mixed>
     */
    public function provideShellNavigation(Request $request): array;
}
