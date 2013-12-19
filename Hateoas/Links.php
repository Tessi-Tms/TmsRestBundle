<?php

/**
 *
 * @author:  Gabriel BONDAZ <gabriel.bondaz@idci-consulting.fr>
 * @author:  Jean-Philippe CHATEAU <jp.chateau@trepia.fr>
 * @license: GPL
 *
 */

namespace Tms\Bundle\RestBundle\Service;

use Symfony\Component\Routing\Router;

class Links
{
    private $router;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    public function setData($data)
    {

    }

    public function improve()
    {

    }

    public function improvePagination()
    {

    }
}