<?php

namespace Tms\Bundle\RestBundle\Binder;

interface BinderInterface
{
    /**
     * Bind associative data with a given object
     *
     * @param  object $object
     * @param  array $data
     */
    public function bind(& $object, array $data);
}
