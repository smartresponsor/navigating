<?php

declare(strict_types=1);

namespace App\Navigating\Service\Navigation\Resolve;

use App\Navigating\ServiceInterface\Navigation\Resolve\NavigationTargetResolveServiceInterface;
use App\Navigating\Value\Navigation\NavigationTarget;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class NavigationTargetResolveService implements NavigationTargetResolveServiceInterface
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
            'url' => $target->path ?? '/',
            default => '',
        };

        if ('' !== $url && [] !== $target->query) {
            $query = http_build_query($target->query);
            if ('' !== $query) {
                $url .= str_contains($url, '?') ? '&'.$query : '?'.$query;
            }
        }

        return $url;
    }

    private function resolveRouteUrl(NavigationTarget $target): string
    {
        if (null === $target->route || '' === trim($target->route)) {
            return '';
        }

        if (!$this->urlGenerator instanceof UrlGeneratorInterface) {
            return '';
        }

        try {
            return $this->urlGenerator->generate($target->route, $target->params);
        } catch (RouteNotFoundException) {
            return '';
        }
    }

    private function normalizePath(string $path): string
    {
        if ('' === trim($path)) {
            return '/';
        }

        return str_starts_with($path, '/') ? $path : '/'.$path;
    }
}
