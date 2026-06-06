<?php

declare(strict_types=1);

namespace App\Navigating\Value\Navigation;

final readonly class NavigationValidationResult
{
    /**
     * @param list<string> $errors
     * @param list<string> $warnings
     */
    public function __construct(
        public array $errors = [],
        public array $warnings = [],
    ) {
    }

    public function isValid(): bool
    {
        return [] === $this->errors;
    }

    /**
     * @return list<string>
     */
    public function messages(): array
    {
        return [...$this->errors, ...$this->warnings];
    }
}
