<?php

declare(strict_types=1);

namespace App\Navigating\Service\Navigation\Provide;

use App\Navigating\Model\Navigation\Context\NavigationRuntimeActivationContext;
use App\Navigating\ServiceInterface\Navigation\Provide\NavigationRuntimeActivationProvideServiceInterface;

final readonly class NavigationRuntimeActivationProvideService implements NavigationRuntimeActivationProvideServiceInterface
{
    /**
     * @param list<string> $runtimeScopeTokens
     * @param list<string> $runtimeEntityTokens
     */
    public function __construct(
        private array $runtimeScopeTokens = [],
        private array $runtimeEntityTokens = [],
        private bool $runtimeActivationStrict = true,
    ) {
    }

    public function provide(): NavigationRuntimeActivationContext
    {
        return new NavigationRuntimeActivationContext(
            scopeTokens: $this->normalize($this->runtimeScopeTokens),
            entityTokens: $this->normalize($this->runtimeEntityTokens),
            strict: $this->runtimeActivationStrict,
        );
    }

    /**
     * @param list<string> $tokens
     *
     * @return list<string>
     */
    private function normalize(array $tokens): array
    {
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
