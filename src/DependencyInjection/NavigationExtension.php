<?php

declare(strict_types=1);

namespace App\Navigating\DependencyInjection;

use App\Navigating\Service\Navigation\NavigationConfigValidator;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Yaml\Yaml;

final class NavigationExtension extends Extension
{
    /**
     * @param array<int, array<string, mixed>> $configs
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $packageConfigFile = __DIR__.'/../../config/navigation.yaml';
        if (is_file($packageConfigFile)) {
            $packageConfig = Yaml::parseFile($packageConfigFile);
            if (is_array($packageConfig) && isset($packageConfig['navigation']) && is_array($packageConfig['navigation'])) {
                $packageConfig = $packageConfig['navigation'];
            }

            if (is_array($packageConfig)) {
                array_unshift($configs, $packageConfig);
            }
        }

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
