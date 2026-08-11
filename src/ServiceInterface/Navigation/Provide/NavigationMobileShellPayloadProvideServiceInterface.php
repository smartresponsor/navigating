<?php

declare(strict_types=1);

namespace App\Navigating\ServiceInterface\Navigation\Provide;

use Symfony\Component\HttpFoundation\Request;

interface NavigationMobileShellPayloadProvideServiceInterface
{
    /** @return array<string, mixed> */
    public function provideMobileShell(Request $request): array;
}
