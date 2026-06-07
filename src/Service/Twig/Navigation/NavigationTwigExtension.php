<?php

declare(strict_types=1);

namespace App\Navigating\Service\Twig\Navigation;

use App\Navigating\ServiceInterface\Navigation\Provide\NavigationGroupProvideServiceInterface;
use App\Navigating\ServiceInterface\Navigation\Provide\NavigationShellProvideServiceInterface;
use App\Navigating\ServiceInterface\Navigation\Render\NavigationRenderServiceInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\Markup;
use Twig\TwigFunction;

final class NavigationTwigExtension extends AbstractExtension
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly NavigationShellProvideServiceInterface $shellProvideService,
        private readonly NavigationGroupProvideServiceInterface $groupProvideService,
        private readonly NavigationRenderServiceInterface $renderService,
    ) {
    }

    /**
     * @return list<TwigFunction>
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('navigating_shell', $this->shell(...)),
            new TwigFunction('navigating_group', $this->group(...)),
            new TwigFunction('navigating_render', $this->render(...), ['is_safe' => ['html']]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function shell(?Request $request = null): array
    {
        return $this->shellProvideService->provideShell($request ?? $this->currentRequest())->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    public function group(string $location, ?Request $request = null): array
    {
        return $this->groupProvideService->provideGroup($location, $request ?? $this->currentRequest())->toArray();
    }

    public function render(string $location, ?Request $request = null): Markup
    {
        return new Markup($this->renderService->renderGroup($location, $request ?? $this->currentRequest()), 'UTF-8');
    }

    private function currentRequest(): Request
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request instanceof Request) {
            return Request::create('/');
        }

        return $request;
    }
}
