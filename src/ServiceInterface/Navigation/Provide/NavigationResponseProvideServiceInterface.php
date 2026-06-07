<?php

declare(strict_types=1);

namespace App\Navigating\ServiceInterface\Navigation\Provide;

use Symfony\Component\HttpFoundation\Request;

interface NavigationResponseProvideServiceInterface
{
    /**
     * @return array<string, mixed>
     */
    public function providePayload(Request $request): array;
}
