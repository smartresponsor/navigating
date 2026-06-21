<?php

declare(strict_types=1);

namespace App\Navigating\DependencyInjection;

use App\Navigating\Service\Navigation\Validate\NavigationConfigValidateService;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class NavigationRuntimeExtension extends Extension
{
    /** @param array<int, array<string, mixed>> $configs */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(
            new Configuration(),
            $configs,
        );
        $validation = (new NavigationConfigValidateService())->validate($config);

        if (!$validation->isValid()) {
            throw new InvalidConfigurationException(implode(PHP_EOL, $validation->errors));
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
