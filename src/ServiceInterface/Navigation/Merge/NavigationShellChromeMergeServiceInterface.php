<?php

declare(strict_types=1);

namespace App\Navigating\ServiceInterface\Navigation\Merge;

use Symfony\Component\HttpFoundation\Request;

interface NavigationShellChromeMergeServiceInterface
{
    /**
     * @param array<string, mixed> $shell
     *
     * @return array<string, mixed>
     */
    public function merge(array $shell, Request $request): array;
}
