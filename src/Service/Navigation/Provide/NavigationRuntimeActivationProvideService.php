<?php

declare(strict_types=1);

namespace App\Navigating\Service\Navigation\Provide;

use App\Navigating\Model\Navigation\Context\NavigationRuntimeActivationContext;
use App\Navigating\ServiceInterface\Navigation\Provide\NavigationRuntimeActivationProvideServiceInterface;

final readonly class NavigationRuntimeActivationProvideService implements NavigationRuntimeActivationProvideServiceInterface
{
    /**
     * @param string|list<string>   $runtimeScope
     * @param string|list<string>   $runtimeEntity
     * @param array<string, mixed> $navigationConfig
     */
    public function __construct(
        private string|array $runtimeScope = '',
        private string|array $runtimeEntity = '',
        private array $navigationConfig = [],
    ) {
    }

    public function provide(): NavigationRuntimeActivationContext
    {
        return new NavigationRuntimeActivationContext(
            scopeTokens: $this->normalize($this->runtimeScope),
            entityTokens: $this->normalize($this->runtimeEntity),
            strict: (bool) ($this->navigationConfig['runtime_activation']['strict'] ?? true),
        );
    }

    /**
     * @param string|list<string> $tokens
     *
     * @return list<string>
     */
    private function normalize(string|array $tokens): array
    {
        if (is_string($tokens)) {
            $tokens = preg_split('/[,\s]+/', $tokens) ?: [];
        }

        $normalized = [];

        foreach ($tokens as $token) {
            if (!is_string($token)) {
                continue;
            }

            $token = strtolower(trim($token));

            if ('' !== $token) {
                $normalized[$token] = $token;
            }
        }

        return array_values($normalized);
    }
}
