<?php

declare(strict_types=1);

namespace App\Navigating\Kernel;

use App\Navigating\NavigatingBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

final class NavigationKernel extends Kernel
{
    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();

        if (class_exists(SecurityBundle::class)) {
            yield new SecurityBundle();
        }

        if (class_exists(TwigBundle::class)) {
            yield new TwigBundle();
        }

        if (class_exists(DoctrineBundle::class)) {
            yield new DoctrineBundle();
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

        if (class_exists(SecurityBundle::class)) {
            $loader->load($configDir.'/standalone/security.yaml');
        }

        if (class_exists(DoctrineBundle::class)) {
            $loader->load($configDir.'/standalone/doctrine.yaml');
        }

        $loader->load($configDir.'/navigation.yaml');
    }

    public function getProjectDir(): string
    {
        return dirname(__DIR__, 2);
    }
}
