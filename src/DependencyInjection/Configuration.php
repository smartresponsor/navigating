<?php

declare(strict_types=1);

namespace App\Navigating\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('navigation');

        $treeBuilder->getRootNode()
            ->children()
                ->integerNode('schema')->defaultValue(3)->end()
                ->arrayNode('runtime_roles')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('fallback_roles')
                            ->scalarPrototype()->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('runtime_scopes')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('fallback_scopes')
                            ->scalarPrototype()->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('runtime_environment')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('fallback_environment')->defaultNull()->end()
                    ->end()
                ->end()
                ->arrayNode('shell_groups')
                    ->useAttributeAsKey('key')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('label')->defaultNull()->end()
                            ->scalarNode('location')->isRequired()->cannotBeEmpty()->end()
                            ->scalarNode('type')->defaultValue('menu')->end()
                            ->integerNode('priority')->defaultValue(100)->end()
                            ->booleanNode('enabled')->defaultTrue()->end()
                            ->booleanNode('visible')->defaultTrue()->end()
                            ->arrayNode('visible_for_roles')
                                ->scalarPrototype()->end()
                            ->end()
                            ->arrayNode('visible_for_scopes')
                                ->scalarPrototype()->end()
                            ->end()
                            ->arrayNode('visible_for_environments')
                                ->scalarPrototype()->end()
                            ->end()
                            ->arrayNode('items')
                                ->useAttributeAsKey('key')
                                ->arrayPrototype()
                                    ->children()
                                        ->scalarNode('label')->defaultNull()->end()
                                        ->integerNode('priority')->defaultValue(100)->end()
                                        ->booleanNode('enabled')->defaultTrue()->end()
                                        ->booleanNode('visible')->defaultTrue()->end()
                                        ->scalarNode('type')->isRequired()->cannotBeEmpty()->end()
                                        ->arrayNode('visible_for_roles')
                                            ->scalarPrototype()->end()
                                        ->end()
                                        ->arrayNode('visible_for_scopes')
                                            ->scalarPrototype()->end()
                                        ->end()
                                        ->arrayNode('visible_for_environments')
                                            ->scalarPrototype()->end()
                                        ->end()
                                        ->variableNode('target')->defaultNull()->end()
                                        ->scalarNode('route')->defaultNull()->end()
                                        ->scalarNode('path')->defaultNull()->end()
                                        ->scalarNode('action')->defaultNull()->end()
                                        ->scalarNode('widget')->defaultNull()->end()
                                        ->scalarNode('icon')->defaultNull()->end()
                                        ->scalarNode('badge')->defaultNull()->end()
                                        ->arrayNode('metadata')
                                            ->variablePrototype()->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
