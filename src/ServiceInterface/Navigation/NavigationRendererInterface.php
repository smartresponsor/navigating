<?php

declare(strict_types=1);

namespace App\Navigating\ServiceInterface\Navigation;

use Symfony\Component\HttpFoundation\Response;

interface NavigationRendererInterface
{
    /**
     * Returns true when a navigation section/template can be rendered.
     */
    public function supports(string $section, string $template): bool;

    /**
     * Renders a navigation section through Interfacing.
     *
     * @param array<string, mixed> $data
     */
    public function render(string $section, string $template, array $data): Response;
}
