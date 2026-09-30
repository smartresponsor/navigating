<?php

declare(strict_types=1);

namespace App\Navigating\ServiceInterface\Provide;

use Symfony\Component\HttpFoundation\Request;

interface NavigationRequestRoleProvideServiceInterface
{
    /**
     * @return list<string>
     */
    public function provideRoles(Request $request): array;
}
