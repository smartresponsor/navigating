<?php

declare(strict_types=1);

namespace App\Navigating\Kernel;

use App\Navigating\NavigatingBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

final class NavigationKernel extends Kernel
{
    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();

        if (class_exists(TwigBundle::class)) {
            yield new TwigBundle();
        }

        yield new NavigatingBundle();
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $configDir = dirname(__DIR__, 2).'/config';

        $loader->load($configDir.'/standalone/framework.yaml');

        if (class_exists(TwigBundle::class)) {
            $loader->load($configDir.'/standalone/twig.yaml');
        }

        $loader->load($configDir.'/navigation.yaml');
    }

    public function getProjectDir(): string
    {
        return dirname(__DIR__, 2);
    }
}
