<?php

namespace Tms\Bundle\RestBundle\Exception;

use FOS\RestBundle\Util\Codes;

/**
 * Exception thrown when an entity field is invalid.
 *
 * @author Thomas Prelot
 */
class InvalidEntityFieldException extends \InvalidArgumentException
{
    /**
     * The HTTP status code
     *
     * @var integer
     */
    private $statusCode = Codes::HTTP_BAD_REQUEST;

    /**
     * Constructor
     *
     * @param array $errorList
     */
    public function __construct($errorList, $statusCode = null)
    {
        parent::__construct(print_r($errorList, true), 0, null);

        if ($statusCode) {
            $this->statusCode = $statusCode;
        }
    }

    /**
     * Constructor
     *
     * @param array $errorList
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }
}