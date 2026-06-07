<?php

declare(strict_types=1);

namespace App\Navigating\ServiceInterface\Navigation\Render;

use Symfony\Component\HttpFoundation\Response;

interface NavigationTemplateRenderServiceInterface
{
    public function supports(string $section, string $template): bool;

    /**
     * @param array<string, mixed> $data
     */
    public function render(string $section, string $template, array $data): Response;
}
