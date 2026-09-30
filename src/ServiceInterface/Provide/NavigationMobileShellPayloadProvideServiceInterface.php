<?php

declare(strict_types=1);

namespace App\Navigating\ServiceInterface\Provide;

use Symfony\Component\HttpFoundation\Request;

interface NavigationMobileShellPayloadProvideServiceInterface
{
    /** @return array<string, mixed> */
    public function provideMobileShell(Request $request): array;
}
