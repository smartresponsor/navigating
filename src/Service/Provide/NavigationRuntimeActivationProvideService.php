<?php

declare(strict_types=1);

namespace App\Navigating\Service\Provide;

use App\Navigating\ServiceInterface\Provide\NavigationRuntimeActivationProvideServiceInterface;
use App\Navigating\Value\Context\NavigationRuntimeActivationContext;

final readonly class NavigationRuntimeActivationProvideService implements NavigationRuntimeActivationProvideServiceInterface
{
    /**
     * @param string|list<string> $runtimeScope
     * @param string|list<string> $runtimeEntity
     */
    public function __construct(
        private string|array $runtimeScope = '',
        private string|array $runtimeEntity = '',
        private bool $runtimeActivationStrict = true,
    ) {
    }

    public function provide(): NavigationRuntimeActivationContext
    {
        return new NavigationRuntimeActivationContext(
            scopeTokens: $this->normalize($this->runtimeScope),
            entityTokens: $this->normalize($this->runtimeEntity),
            strict: $this->runtimeActivationStrict,
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
            $token = strtolower(trim($token));

            if ('' !== $token) {
                $normalized[$token] = $token;
            }
        }

        return array_values($normalized);
    }
}
