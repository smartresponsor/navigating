<?php

declare(strict_types=1);

namespace App\Navigating\Service\Navigation\Provide;

use App\Navigating\ServiceInterface\Navigation\Provide\NavigationShellProvideServiceInterface;
use App\Navigating\ServiceInterface\Navigation\Provide\NavigationTemplateDataProvideServiceInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class NavigationTemplateDataProvideService implements NavigationTemplateDataProvideServiceInterface
{
    public const SURFACE = 'navigation';
    public const TEMPLATE = 'index';

    public function __construct(
        private NavigationShellProvideServiceInterface $shellProvideService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function provide(Request $request): array
    {
        $shell = $this->shellProvideService->provideShell($request);

        return [
            'surface' => self::SURFACE,
            'template' => self::TEMPLATE,
            'interface' => [
                'locations' => $shell->toLocationsArray(),
                'active' => $this->shellProvideService->provideActiveState($request),
            ],
            'navigation' => [
                'groups' => $shell->toArray()['groups'],
            ],
        ];
    }
}
