<?php

namespace Tms\Bundle\RestBundle\Exception;

/**
 * Exception thrown when an entity field is invalid.
 *
 * @author Thomas Prelot
 */
class InvalidEntityFieldException extends \InvalidArgumentException
{
    /**
     * Constructor
     *
     * @param array $errorList
     */
    public function __construct($errorList)
    {
        parent::__construct(print_r($errorList, true), 0, null);
    }
}