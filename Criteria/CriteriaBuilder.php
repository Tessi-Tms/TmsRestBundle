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
     * Clean criteria according to a list of given parameters
     *
     * @param array $parameters
     * @return array
     */
    public function clean(array $parameters)
    {
        if (!count($parameters)) {
            return $parameters;
        }

        foreach ($parameters as $name => $value) {
            if (null === $value) {
                unset($parameters[$name]);
                continue;
            }

            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    try {
                        $parameters[$name][$k] = unserialize($v);
                    } catch(\Exception $e) {
                        continue;
                    }
                }
            }
        }

        return $parameters;
    }
}
