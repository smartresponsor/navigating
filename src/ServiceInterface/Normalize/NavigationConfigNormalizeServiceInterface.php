<?php

declare(strict_types=1);

namespace App\Navigating\ServiceInterface\Normalize;

use App\Navigating\Value\NavigationShellGroup;

interface NavigationConfigNormalizeServiceInterface
{
    /**
     * @param array<string, mixed> $config
     *
     * @return list<NavigationShellGroup>
     */
    public function normalizeShellGroups(array $config): array;
}
