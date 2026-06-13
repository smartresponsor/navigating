<?php

declare(strict_types=1);

namespace App\Navigating\DependencyInjection;

use App\Navigating\Service\Navigation\Validate\NavigationConfigValidateService;
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
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, array_merge($this->defaultNavigationConfigs(), $configs));
        $validationResult = (new NavigationConfigValidateService())->validate($config);

        if (!$validationResult->isValid()) {
            throw new InvalidConfigurationException(implode(PHP_EOL, $validationResult->errors));
        }

        $container->setParameter('navigation.config', $config);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('services.yaml');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function defaultNavigationConfigs(): array
    {
        $path = __DIR__.'/../../config/navigation.yaml';

        if (!is_file($path)) {
            return [];
        }

        $data = Yaml::parseFile($path);

        if (!is_array($data)) {
            return [];
        }

        $config = $data['navigation'] ?? $data;

        if (!is_array($config)) {
            return [];
        }

        return [$config];
    }

    public function getAlias(): string
    {
        return 'navigation';
    }
}
