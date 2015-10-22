<?php

namespace Tms\Bundle\RestBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class EntityEvent extends Event
{
    protected $partipation;

    public function __construct($entity)
    {
        $this->entity = $entity;
    }

    public function getEntity()
    {
        return $this->entity;
    }
}