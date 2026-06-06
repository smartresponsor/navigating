<?php

declare(strict_types=1);

namespace App\Navigating\Service\Navigation;

use App\Navigating\ServiceInterface\Navigation\NavigationRendererInterface;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

final readonly class TwigNavigationRenderer implements NavigationRendererInterface
{
    public function __construct(
        private ?Environment $twig = null,
    ) {
    }

    public function supports(string $section, string $template): bool
    {
        if (!$this->twig instanceof Environment) {
            return false;
        }

        foreach ($this->candidateNames($section, $template) as $candidateName) {
            if ($this->templateExists($candidateName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function render(string $section, string $template, array $data): Response
    {
        if (!$this->twig instanceof Environment) {
            throw new \RuntimeException('Twig environment is not available for navigation rendering.');
        }

        foreach ($this->candidateNames($section, $template) as $candidateName) {
            if (!$this->templateExists($candidateName)) {
                continue;
            }

            return new Response($this->twig->render($candidateName, $data));
        }

        throw new \RuntimeException(sprintf('Navigation template "%s/%s" was not found.', $section, $template));
    }

    /**
     * @return list<string>
     */
    private function candidateNames(string $section, string $template): array
    {
        $section = trim($section, '/');
        $template = trim($template, '/');

        if ('' === $section || '' === $template) {
            return [];
        }

        return [
            sprintf('@Interfacing/%s/%s.html.twig', $section, $template),
            sprintf('%s/%s.html.twig', $section, $template),
        ];
    }

    private function templateExists(string $candidateName): bool
    {
        if (!$this->twig instanceof Environment) {
            return false;
        }

        $loader = $this->twig->getLoader();

        if (!method_exists($loader, 'exists')) {
            return false;
        }

        try {
            return $loader->exists($candidateName);
        } catch (\Throwable) {
            return false;
        }
    }
}
