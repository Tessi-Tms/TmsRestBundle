<?php

/**
 *
 * @author:  Gabriel BONDAZ <gabriel.bondaz@idci-consulting.fr>
 * @author:  Pierre FERROLLIET <pierre.ferrolliet@idci-consulting.fr>
 * @author:  Jean-Philippe CHATEAU <jp.chateau@trepia.fr>
 * @license: GPL
 *
 */

namespace Tms\Bundle\RestBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $rootNode = $treeBuilder->root('tms_rest');

        $rootNode
            ->children()
                ->arrayNode('default')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('pagination')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->arrayNode('limit')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->integerNode('default')->defaultValue(20)->min(1)->end()
                                        ->integerNode('maximum')->defaultValue(50)->min(1)->end()
                                    ->end()
                                ->end()
                                ->arrayNode('sort')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('field')->defaultValue('id')->end()
                                        ->scalarNode('order')->defaultValue('asc')->end()
                                    ->end()
                                ->end()
                                ->arrayNode('offset')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->integerNode('default')->defaultValue(0)->end()
                                    ->end()
                                ->end()
                                ->arrayNode('page')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->integerNode('default')->defaultValue(1)->min(1)->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->children()
                ->arrayNode('routes')
                    ->useAttributeAsKey('')
                    ->prototype('array')
                        ->children()
                            ->arrayNode('pagination')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->arrayNode('limit')
                                        ->addDefaultsIfNotSet()
                                        ->children()
                                            ->integerNode('default')->defaultValue(20)->min(1)->end()
                                            ->integerNode('maximum')->defaultValue(50)->min(1)->end()
                                        ->end()
                                    ->end()
                                    ->arrayNode('sort')
                                        ->addDefaultsIfNotSet()
                                        ->children()
                                            ->scalarNode('field')->defaultValue('id')->end()
                                            ->scalarNode('order')->defaultValue('asc')->end()
                                        ->end()
                                    ->end()
                                    ->arrayNode('offset')
                                        ->addDefaultsIfNotSet()
                                        ->children()
                                            ->integerNode('default')->defaultValue(0)->end()
                                        ->end()
                                    ->end()
                                    ->arrayNode('page')
                                        ->addDefaultsIfNotSet()
                                        ->children()
                                            ->integerNode('default')->defaultValue(1)->min(1)->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->children()
                ->arrayNode('entity_handlers')
                    ->useAttributeAsKey('')
                    ->prototype('array')
                        ->children()
                            ->scalarNode('entity')->end()
                            ->scalarNode('service')->defaultValue('tms_rest.entity_handler')->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
