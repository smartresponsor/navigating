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

class NavigationRuntimeExtension extends Extension
{
    /** @param array<int, array<string, mixed>> $configs */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(
            new Configuration(),
            array_merge($this->defaultConfigs(), $configs),
        );
        $validation = (new NavigationConfigValidateService())->validate($config);

        if (!$validation->isValid()) {
            throw new InvalidConfigurationException(implode(PHP_EOL, $validation->errors));
        }

        $container->setParameter('navigation.config', $config);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('services.yaml');
    }

    /** @return list<array<string, mixed>> */
    private function defaultConfigs(): array
    {
        $configs = [];

        foreach ([
            'navigation.yaml',
            'navigation_runtime_activation.yaml',
            'navigation_runtime_activation_business.yaml',
            'navigation_runtime_activation_system.yaml',
            'navigation_runtime_activation_entity.yaml',
        ] as $file) {
            $data = Yaml::parseFile(__DIR__.'/../../config/'.$file);
            $config = is_array($data) ? ($data['navigation'] ?? $data) : null;

            if (is_array($config)) {
                $configs[] = $config;
            }
        }

        return $configs;
    }

    public function getAlias(): string
    {
        return 'navigation';
    }
}
