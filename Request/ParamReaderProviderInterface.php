<?php

namespace Tms\Bundle\RestBundle\Request;

/**
 * ParamReaderProviderInterface is the interface that a class
 * should implement to be used as a provider of param reader.
 *
 * @author Thomas Prelot <thomas.prelot@tessi.fr>
 */
interface ParamReaderProviderInterface
{
    /**
     * Provide the current param reader.
     *
     * @return \FOS\RestBundle\Request\ParamReaderInterface The param reader.
     */
    function provide();
}
