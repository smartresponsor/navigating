<?php

declare(strict_types=1);

namespace App\Navigating\ServiceInterface\Provide;

use Symfony\Component\HttpFoundation\Request;

interface NavigationTemplateDataProvideServiceInterface
{
    /**
     * @return array<string, mixed>
     */
    public function provide(Request $request): array;
}
