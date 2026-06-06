<?php

declare(strict_types=1);

namespace App\Navigating\Service\Navigation;

use Symfony\Component\HttpFoundation\Request;

final readonly class NavigationTemplateDataProvider
{
    public const SURFACE = 'navigation';
    public const TEMPLATE = 'index';

    public function __construct(
        private NavigationRuntimeProvider $runtimeProvider,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function provide(Request $request): array
    {
        $locations = $this->runtimeProvider->provideLocations($request);
        $active = $this->runtimeProvider->provideActiveState($request);

        return [
            'surface' => self::SURFACE,
            'template' => self::TEMPLATE,
            'navigation' => [
                'locations' => $locations,
                'active' => $active,
            ],
        ];
    }
}
