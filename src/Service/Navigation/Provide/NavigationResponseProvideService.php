<?php

declare(strict_types=1);

namespace App\Navigating\Service\Navigation\Provide;

use App\Navigating\ServiceInterface\Navigation\Provide\NavigationResponseProvideServiceInterface;
use App\Navigating\ServiceInterface\Navigation\Provide\NavigationTemplateDataProvideServiceInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class NavigationResponseProvideService implements NavigationResponseProvideServiceInterface
{
    public function __construct(
        private NavigationTemplateDataProvideServiceInterface $templateDataProvideService,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function providePayload(Request $request): array
    {
        $data = $this->templateDataProvideService->provide($request);
        $section = (string) ($data['surface'] ?? NavigationTemplateDataProvideService::SURFACE);
        $template = (string) ($data['template'] ?? NavigationTemplateDataProvideService::TEMPLATE);
        $navigation = \is_array($data['navigation'] ?? null) ? $data['navigation'] : [];
        $interface = \is_array($data['interface'] ?? null) ? $data['interface'] : ['locations' => []];

        return [
            '_view' => [
                'surface' => $section,
                'operation' => $template,
                'component' => 'Navigating',
                'intent' => 'navigation',
                'template_path' => sprintf('%s/%s.html.twig', $section, $template),
            ],
            'interface' => $interface,
            'navigation' => $navigation,
            'locations' => $navigation['locations'] ?? $interface['locations'] ?? [],
            'groups' => $navigation['groups'] ?? [],
            'active' => $navigation['active'] ?? $interface['active'] ?? [],
            'data' => $data,
            'meta' => [
                'navigation_surface' => $section,
                'navigation_template' => $template,
            ],
        ];
    }
}
