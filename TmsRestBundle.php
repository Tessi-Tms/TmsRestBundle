<?php

namespace Tms\Bundle\RestBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tms\Bundle\RestBundle\DependencyInjection\Compiler\InitializeFormatterFactoryPass;

/**
 * @author:  TESSI Marketing <contact@tessi.fr>
 */
class TmsRestBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new InitializeFormatterFactoryPass());
    }
}
