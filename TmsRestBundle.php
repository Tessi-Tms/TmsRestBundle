<?php

/**
 *
 * @author:  TESSI Marketing <contact@tessi.fr>
 *
 */

namespace Tms\Bundle\RestBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tms\Bundle\RestBundle\DependencyInjection\Compiler\RestLinkCompilerPass;
use Tms\Bundle\RestBundle\DependencyInjection\Compiler\PaginationCompilerPass;

class TmsRestBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new RestLinkCompilerPass());
        $container->addCompilerPass(new PaginationCompilerPass());
    }
}
