<?php

/**
 *
 * @author:  Jean-Philippe CHATEAU <jp.chateau@trepia.fr>
 * @license: GPL
 *
 */

namespace Tms\Bundle\RestBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class PaginationCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $configuration = $container->getParameter('tms_rest');
        //var_dump($configuration);

        if (!$container->hasDefinition('tms_rest.criteria_builder')) {
            return;
        }


        $pagination = array();
        foreach ($configuration as $key => $config) {
            $pagination[$key] = array(
                'default' => $config['pagination_limit']['default'],
                'maximum' => $config['pagination_limit']['maximum'],
            );
        }

        $definition = $container->getDefinition('tms_rest.criteria_builder');
        $definition
            ->replaceArgument(0, $pagination);
        ;

    }
}
