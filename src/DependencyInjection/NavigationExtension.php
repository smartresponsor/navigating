<?php

declare(strict_types=1);

namespace App\Navigating\DependencyInjection;

use App\Navigating\Service\Navigation\NavigationConfigValidator;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class NavigationExtension extends Extension
{
    /**
     * @param array<int, array<string, mixed>> $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $validationResult = (new NavigationConfigValidator())->validate($config);

        if (!$validationResult->isValid()) {
            throw new InvalidConfigurationException(implode(PHP_EOL, $validationResult->errors));
        }

        $container->setParameter('navigation.config', $config);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('services.yaml');
    }

    public function getAlias(): string
    {
        return 'navigation';
    }
}
