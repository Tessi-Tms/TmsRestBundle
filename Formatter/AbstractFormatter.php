<?php

/**
 *
 * @author:  Gabriel BONDAZ <gabriel.bondaz@idci-consulting.fr>
 * @author:  Pierre FERROLLIET <pierre.ferrolliet@idci-consulting.fr>
 * @license: GPL
 *
 */

namespace Tms\Bundle\RestBundle\Formatter;

abstract class AbstractFormatter implements FormatterInterface
{
    const SERIALIZER_CONTEXT_GROUP_SINGLE = 'details';
    const SERIALIZER_CONTEXT_GROUP_COLLECTION = 'list';

    protected $serializer;

    public function __construct($serializer)
    {
        $this->serializer = $serializer;
    }
}
