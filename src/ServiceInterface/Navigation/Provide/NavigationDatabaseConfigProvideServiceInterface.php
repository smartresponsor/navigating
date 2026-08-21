<?php

declare(strict_types=1);

namespace App\Navigating\ServiceInterface\Navigation\Provide;

interface NavigationDatabaseConfigProvideServiceInterface
{
    /** @return array<string, mixed> */
    public function provideConfig(): array;
}
