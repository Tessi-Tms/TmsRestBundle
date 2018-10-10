<?php

namespace Tms\Bundle\RestBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class IsJSON extends Constraint
{
    public $message = 'This value must be JSON valid';
}
