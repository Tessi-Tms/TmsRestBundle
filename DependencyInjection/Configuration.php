<?php

/**
 *
 * @author:  Gabriel BONDAZ <gabriel.bondaz@idci-consulting.fr>
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
                ->arrayNode('default_configuration')
                    ->children()
                        ->arrayNode('pagination_limit')
                            ->children()
                                ->integerNode('default')->isRequired()->min(0)->end()
                                ->integerNode('maximum')->isRequired()->min(0)->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            ->useAttributeAsKey('')
            ->prototype('array')
                ->children()
                    ->arrayNode('pagination_limit')
                        ->children()
                            ->integerNode('default')->isRequired()->min(0)->end()
                            ->integerNode('maximum')->isRequired()->min(0)->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
