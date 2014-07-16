<?php

namespace Tms\Bundle\RestBundle\Request;

/**
 * RequestProviderInterface is the interface that a class
 * should implement to be used as a provider of request.
 *
 * @author Thomas Prelot <thomas.prelot@tessi.fr>
 */
interface RequestProviderInterface
{
    /**
     * Provide the current request.
     *
     * @return \Symfony\Component\HttpKernel\Request The request.
     */
    function provide();
}
