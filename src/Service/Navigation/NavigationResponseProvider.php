<?php

declare(strict_types=1);

namespace App\Navigating\Service\Navigation;

use Symfony\Component\HttpFoundation\Request;

final readonly class NavigationResponseProvider
{
    public function __construct(
        private NavigationTemplateDataProvider $templateDataProvider,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function providePayload(Request $request): array
    {
        $data = $this->templateDataProvider->provide($request);
        $section = (string) ($data['surface'] ?? NavigationTemplateDataProvider::SURFACE);
        $template = (string) ($data['template'] ?? NavigationTemplateDataProvider::TEMPLATE);

        return [
            '_view' => [
                'surface' => $section,
                'operation' => $template,
                'component' => 'Navigating',
                'intent' => 'navigation',
                'template_path' => sprintf('%s/%s.html.twig', $section, $template),
            ],
            'locations' => $data['navigation']['locations'] ?? [],
            'data' => $data,
            'meta' => [
                'navigation_surface' => $section,
                'navigation_template' => $template,
            ],
        ];
    }
}
