<?php

namespace Tms\Bundle\RestBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Initialize the formatter factory.
 *
 * @author Thomas Prelot <thomas.prelot@tessi.fr>
 */
class InitializeFormatterFactoryPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('tms_rest.formatter.factory')) {
            return;
        }

        $definition = $container->findDefinition('tms_rest.formatter.factory');

        // Injection of the aggregators.
        $taggedServices = $container->findTaggedServiceIds('tms_rest.formatter.provider');

        foreach ($taggedServices as $id => $tagAttributes) {
            foreach ($tagAttributes as $attributes) {
                $definition->addMethodCall(
                    'addFormatterProvider',
                    array($attributes['id'], new Reference($id))
                );
            }
        }
    }
}