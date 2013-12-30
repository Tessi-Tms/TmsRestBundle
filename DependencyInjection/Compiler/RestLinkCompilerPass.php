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

class RestLinkCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('kernel.listener.link_request_listener')) {
            return;
        }

        $definition = $container->getDefinition('kernel.listener.link_request_listener');
        $taggedServices = $container->findTaggedServiceIds('rest.link');
        foreach ($taggedServices as $id => $attributes) {
            if (empty($attributes[0]['class'])) {
                continue;
            }
            $definition->addMethodCall(
                'addManager',
                array($attributes[0]['class'], new Reference($id))
            );
        }
    }
}
