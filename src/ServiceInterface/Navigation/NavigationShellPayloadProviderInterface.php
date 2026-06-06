<?php

declare(strict_types=1);

namespace App\Navigating\ServiceInterface\Navigation;

use Symfony\Component\HttpFoundation\Request;

interface NavigationShellPayloadProviderInterface
{
    /**
     * Returns shell-ready navigation payload for Interfacing.
     *
     * @return array<string, mixed>
     */
    public function provideShellNavigation(Request $request): array;
}
