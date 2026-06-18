<?php

declare(strict_types=1);

namespace App\Navigating\Model\Navigation\Context;

final readonly class NavigationRuntimeActivationContext
{
    /**
     * @param list<string> $scopeTokens
     * @param list<string> $entityTokens
     */
    public function __construct(
        public array $scopeTokens = [],
        public array $entityTokens = [],
        public bool $strict = true,
    ) {
    }

    /** @param list<string> $requiredTokens */
    public function allowsScope(array $requiredTokens): bool
    {
        return $this->allows($requiredTokens, $this->scopeTokens);
    }

    /** @param list<string> $requiredTokens */
    public function allowsEntity(array $requiredTokens): bool
    {
        return $this->allows($requiredTokens, $this->entityTokens);
    }

    /**
     * @param list<string> $requiredTokens
     * @param list<string> $activeTokens
     */
    private function allows(array $requiredTokens, array $activeTokens): bool
    {
        if ([] === $requiredTokens) {
            return true;
        }

        if ([] === $activeTokens) {
            return !$this->strict;
        }

        $activeLookup = array_fill_keys($activeTokens, true);

        foreach ($requiredTokens as $requiredToken) {
            if (isset($activeLookup[$requiredToken])) {
                return true;
            }
        }

        return false;
    }
}
