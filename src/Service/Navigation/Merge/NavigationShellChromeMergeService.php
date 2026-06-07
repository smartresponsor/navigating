<?php

declare(strict_types=1);

namespace App\Navigating\Service\Navigation\Merge;

use App\Navigating\ServiceInterface\Navigation\Merge\NavigationShellChromeMergeServiceInterface;
use App\Navigating\ServiceInterface\Navigation\Provide\NavigationShellPayloadProvideServiceInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class NavigationShellChromeMergeService implements NavigationShellChromeMergeServiceInterface
{
    public function __construct(
        private NavigationShellPayloadProvideServiceInterface $shellPayloadProvideService,
    ) {
    }

    /**
     * @param array<string, mixed> $shell
     *
     * @return array<string, mixed>
     */
    public function merge(array $shell, Request $request): array
    {
        $navigationPayload = $this->shellPayloadProvideService->provideShellNavigation($request);

        return array_replace_recursive($shell, $navigationPayload);
    }
}
