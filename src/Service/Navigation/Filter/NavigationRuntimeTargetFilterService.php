<?php

declare(strict_types=1);

namespace App\Navigating\Service\Navigation\Filter;

use App\Navigating\ServiceInterface\Navigation\Filter\NavigationRuntimeTargetFilterServiceInterface;
use App\Navigating\ServiceInterface\Navigation\Provide\NavigationRuntimeActivationProvideServiceInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class NavigationRuntimeTargetFilterService implements NavigationRuntimeTargetFilterServiceInterface
{
    public function __construct(
        private NavigationRuntimeActivationProvideServiceInterface $runtimeActivationProvider,
    ) {
    }

    public function allows(string $href, Request $request): bool
    {
        $href = trim($href);

        if ('' === $href) {
            return false;
        }

        $parts = parse_url($href);

        if (false === $parts) {
            return false;
        }

        $host = is_string($parts['host'] ?? null) ? strtolower($parts['host']) : null;

        if (null !== $host && '' !== $host && $host !== strtolower($request->getHost())) {
            return true;
        }

        $path = is_string($parts['path'] ?? null) ? $parts['path'] : '';

        if ('' === $path || '/' === $path) {
            return true;
        }

        $segments = explode('/', trim($path, '/'));
        $runtimeToken = strtolower(rawurldecode((string) ($segments[0] ?? '')));

        if ('' === $runtimeToken) {
            return true;
        }

        return $this->runtimeActivationProvider->provide()->allowsRuntimeToken($runtimeToken);
    }
}
