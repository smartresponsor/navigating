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

    public function allowsRuntimeToken(string $requiredToken): bool
    {
        $requiredToken = strtolower(trim($requiredToken));

        if ('' === $requiredToken) {
            return true;
        }

        return $this->allows([$requiredToken], $this->runtimeTokens());
    }

    /** @return list<string> */
    public function runtimeTokens(): array
    {
        $tokens = [];

        foreach ([...$this->scopeTokens, ...$this->entityTokens] as $token) {
            $token = strtolower(trim($token));

            if ('' !== $token) {
                $tokens[$token] = $token;
            }
        }

        return array_values($tokens);
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
