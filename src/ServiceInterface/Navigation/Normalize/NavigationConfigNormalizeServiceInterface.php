<?php

declare(strict_types=1);

namespace App\Navigating\ServiceInterface\Navigation\Normalize;

use App\Navigating\Value\Navigation\NavigationShellGroup;

interface NavigationConfigNormalizeServiceInterface
{
    /**
     * @param array<string, mixed> $config
     *
     * @return list<NavigationShellGroup>
     */
    public function normalizeShellGroups(array $config): array;
}
