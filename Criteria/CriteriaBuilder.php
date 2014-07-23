<?php

/**
 *
 * @author:  Gabriel BONDAZ <gabriel.bondaz@idci-consulting.fr>
 * @author:  Pierre FERROLLIET <pierre.ferrolliet@idci-consulting.fr>
 * @author:  Jean-Philippe CHATEAU <jp.chateau@trepia.fr>
 * @license: GPL
 *
 */

namespace Tms\Bundle\RestBundle\Criteria;

class CriteriaBuilder
{
    const ORDER_ASC = 'asc';
    const ORDER_DESC = 'desc';
    
    /**
     * @var array
     */
    protected $configuration;

    /**
     * Constructor
     *
     * @param array $configuration
     */
    public function __construct(array $configuration)
    {
        $this->configuration = $configuration;
    }


    /**
     * Guess configuration by Route
     *
     * @param string|null $route
     * @return array
     */
    public function guessConfigurationByRoute($route = null)
    {
        if (null === $route || count($this->configuration['routes']) == 0) {
            $this->configuration = $this->configuration['default'];
        } else if (isset($this->configuration['routes'][$route])) {
            $this->configuration = array_merge(
                $this->configuration['routes'][$route],
                $this->configuration['default']
            );
        } else {
            $this->configuration = $this->configuration['default'];
        }
    }

    /**
     * Clean the criteria value
     *
     * @param array $criteria
     * @return array
     */
    public function cleanCriteriaValue($criteria = null)
    {
        if(is_null($criteria)) {
            return array();
        }

        foreach ($criteria as $name => $value) {
            if (null === $value || $value === array()) {
                unset($criteria[$name]);

                continue;
            }

            if (is_array($value)) {
                $criteria[$name] = $this->cleanCriteriaValue($value);
//                foreach ($value as $k => $v) {
//                    if(is_array($v)) {
//                        unset($criteria[$v]);
//                        $this->cleanCriteriaValue($v);
//                    }
//                    $criteria[$k] = $v;
//                }
//                unset($criteria[$name]);
            }
        }

        return $criteria;
    }

    /**
     * Define the limit according to the original value and the defined configuration
     *
     * @param mixed $originalValue
     * @return integer
     */
    public function initDefaultLimitValue($originalValue = null)
    {
        $defaultLimit = $this->configuration['pagination']['limit'];
        if (is_null($originalValue)) {
            if ($defaultLimit['default'] > $defaultLimit['maximum']) {
                return intval($defaultLimit['maximum']);
            }

            return intval($defaultLimit['default']);
        }

        if (intval($originalValue) > $defaultLimit['maximum']) {
            return intval($defaultLimit['maximum']);
        }

        return intval($originalValue);
    }

    /**
     * Clean the sort value
     *
     * @param array $sort
     * @return array
     */
    public function cleanSortValue($sort = null)
    {
        if(is_null($sort)) {
            return array();
        }

        foreach ($sort as $name => $value) {
            if (null === $value || $value === array()) {
                unset($sort[$name]);

                continue;
            }

            if (is_array($value)) {
                $sort[$name] = $this->cleanSortValue($value);
            }
        }
        
        return $sort;
    }

    /**
     * Define the sort values according to the original value and the defined configuration
     *
     * @param mixed $originalValue
     * @return array
     */
    public function initDefaultSortValue($originalValue = null)
    {
        $defaultSort = $this->configuration['pagination']['sort'];
        $allowed_orders = array(self::ORDER_ASC, self::ORDER_DESC);

        if (is_null($originalValue)) {
            return array(
                $defaultSort['field'] => $defaultSort['order']
            );
        }
        
        foreach($originalValue as $field => $order) {
            if(!in_array($order, $allowed_orders)) {
                unset($originalValue[$field]);
            }
        }

        return $originalValue;
    }
 
    /**
     * Define the page according to the original value and the defined configuration
     *
     * @param mixed $originalValue
     * @return integer
     */
    public function initDefaultPageValue($originalValue = null)
    {
        $defaultPage = $this->configuration['pagination']['page'];
        if(is_null($originalValue)) {
            return intval($defaultPage['default']);
        }

        return intval($originalValue);
    }
    
    /**
     * Define the offset according to the original value and the defined configuration
     *
     * @param mixed $originalValue
     * @return integer
     */
    public function initDefaultOffsetValue($originalValue = null)
    {
        $defaultOffset = $this->configuration['pagination']['offset'];
        if (is_null($originalValue)) {
            return intval($defaultOffset['default']);
        }

        return intval($originalValue);
    }
}
