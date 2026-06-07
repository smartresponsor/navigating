<?php

declare(strict_types=1);

namespace App\Navigating\ServiceInterface\Navigation\Validate;

use App\Navigating\Value\Navigation\NavigationValidationResult;

interface NavigationConfigValidateServiceInterface
{
    /**
     * @param array<string, mixed> $config
     */
    public function validate(array $config): NavigationValidationResult;
}
