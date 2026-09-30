<?php

declare(strict_types=1);

namespace App\Navigating\ServiceInterface\Validate;

use App\Navigating\Value\NavigationValidationResult;

interface NavigationConfigValidateServiceInterface
{
    /**
     * @param array<string, mixed> $config
     */
    public function validate(array $config): NavigationValidationResult;
}
