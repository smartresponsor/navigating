<?php

declare(strict_types=1);

namespace App\Navigating\Service\Navigation;

use App\Navigating\ServiceInterface\Navigation\NavigationShellPayloadProviderInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class NavigationShellChromeMerger
{
    public function __construct(
        private NavigationShellPayloadProviderInterface $shellPayloadProvider,
    ) {
    }

    /**
     * Merges Navigation payload into an existing shell array without taking ownership of rendering.
     *
     * @param array<string, mixed> $shell
     *
     * @return array<string, mixed>
     */
    public function merge(array $shell, Request $request): array
    {
        $navigationPayload = $this->shellPayloadProvider->provideShellNavigation($request);

        return array_replace_recursive($shell, $navigationPayload);
    }
}
