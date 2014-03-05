<?php

/**
 *
 * @author:  Gabriel BONDAZ <gabriel.bondaz@idci-consulting.fr>
 * @author:  Jean-Philippe CHATEAU <jp.chateau@trepia.fr>
 * @license: GPL
 *
 */

namespace Tms\Bundle\RestBundle\Criteria;

class CriteriaBuilder
{
    /**
     * @var array
     *
     * Example:
     * array('route_name' => array(
     *     'default' => 20,
     *     'maximum' => 100,
     * ))
     */
    private $pagination;

    /**
     * Constructor
     *
     * @param array $configuration
     */
    public function __construct(array $pagination)
    {
        $this->pagination = $pagination;
    }

    /**
     * Clean criteria according to a list of given parameters
     *
     * @param string $route
     * @param array $parameters
     * @return array
     */
    public function clean($route, array $parameters)
    {
        if (!count($parameters)) {
            return $parameters;
        }

        $paginationLimit = $this->guessPaginationLimitByRoute($route);
        foreach ($parameters as $name => $value) {
            if ('limit' === $name) {
                if (null === $value) {
                    $parameters[$name] = $paginationLimit['default'];
                } else if ($value > $paginationLimit['maximum']) {
                    $parameters[$name] = $paginationLimit['maximum'];
                }
                continue;
            }

            if (null === $value) {
                unset($parameters[$name]);
            }
        }

        return $parameters;
    }

    /**
     * Guess Pagination Limit by Route
     *
     * @param string $route
     */
    private function guessPaginationLimitByRoute($route)
    {
        if (isset($this->pagination[$route])) {
            return $this->pagination[$route];
        }

        return $this->pagination['default_configuration'];
    }
}