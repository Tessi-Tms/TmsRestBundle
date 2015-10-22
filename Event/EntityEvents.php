<?php

namespace Tms\Bundle\RestBundle\Event;

final class EntityEvents
{
    /**
     * @var string
     */
    const PRE_CREATE =  'tms_stock.pre_create';
    const POST_CREATE = 'tms_stock.post_create';

    const PRE_UPDATE =  'tms_stock.pre_update';
    const POST_UPDATE = 'tms_stock.post_update';

    const PRE_DELETE =  'tms_stock.pre_delete';
    const POST_DELETE = 'tms_stock.post_delete';
}