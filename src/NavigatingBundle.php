<?php

declare(strict_types=1);

namespace App\Navigating;

use App\Navigating\DependencyInjection\NavigationExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class NavigatingBundle extends Bundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new NavigationExtension();
    }
}
