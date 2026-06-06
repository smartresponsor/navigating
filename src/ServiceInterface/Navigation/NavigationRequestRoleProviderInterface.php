<?php

declare(strict_types=1);

namespace App\Navigating\ServiceInterface\Navigation;

use Symfony\Component\HttpFoundation\Request;

interface NavigationRequestRoleProviderInterface
{
    /**
     * @return list<string>
     */
    public function provideRoles(Request $request): array;
}
