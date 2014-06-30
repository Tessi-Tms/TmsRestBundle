<?php

namespace Tms\Bundle\RestBundle\Formatter\Provider;

/**
 * FormatterProviderInterface is the interface that a class should
 * implement to be used as a formatter provider.
 *
 * @author Thomas Prelot <thomas.prelot@tessi.fr>
 */
interface FormatterProviderInterface
{
    /**
     * Create and return a hypermedia formatter.
     *
     * @param array $arguments The arguments to pass to the constructor of the formatter.
     *
     * @return object A formatter.
     */
    public function create($arguments = array());
}
