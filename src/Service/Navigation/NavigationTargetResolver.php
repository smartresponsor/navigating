<?php

declare(strict_types=1);

namespace App\Navigating\Service\Navigation;

use App\Navigating\Value\Navigation\NavigationTarget;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class NavigationTargetResolver
{
    public function __construct(
        private ?UrlGeneratorInterface $urlGenerator = null,
    ) {
    }

    public function resolveUrl(NavigationTarget $target): string
    {
        $url = match ($target->type) {
            'route' => $this->resolveRouteUrl($target),
            'path' => $this->normalizePath($target->path ?? '/'),
            default => '/',
        };

        if ([] !== $target->query) {
            $query = http_build_query($target->query);
            if ('' !== $query) {
                $url .= str_contains($url, '?') ? '&'.$query : '?'.$query;
            }
        }

        return $url;
    }

    private function resolveRouteUrl(NavigationTarget $target): string
    {
        if (null === $target->route) {
            return '/';
        }

        return $this->tryGenerateRoute($target->route, $target->params) ?? '/';
    }

    /**
     * @param array<string, mixed> $params
     */
    private function tryGenerateRoute(string $route, array $params): ?string
    {
        if (!$this->urlGenerator instanceof UrlGeneratorInterface) {
            return null;
        }

        try {
            return $this->urlGenerator->generate($route, $params);
        } catch (RouteNotFoundException) {
            return null;
        }
    }

    private function normalizePath(string $path): string
    {
        if ('' === $path) {
            return '/';
        }

        return str_starts_with($path, '/') ? $path : '/'.$path;
    }
}
