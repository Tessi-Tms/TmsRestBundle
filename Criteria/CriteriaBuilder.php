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
     * Define the criteria according to the original value and the defined configuration
     *
     * @param array $criteria
     * @return array
     */
    public function defineCriteriaValue($criteria = null)
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
                foreach ($value as $k => $v) {
                    if (null === $v) {
                        unset($criteria[$name][$k]);
                        continue;
                    } else {
                        try {
                            $criteria[$name][$k] = unserialize($v);
                        } catch(\Exception $e) {
                            continue;
                        }
                    }
                }
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
    public function defineLimitValue($originalValue = null)
    {
        $defaultLimit = $this->configuration['pagination']['limit'];
        if (is_null($originalValue)) {
            if ($defaultLimit['default'] > $defaultLimit['maximum']) {
                return $defaultLimit['maximum'];
            }

            return $defaultLimit['default'];
        }

        if (intval($originalValue) > $defaultLimit['maximum']) {
            return $defaultLimit['maximum'];
        }

        return $originalValue;
    }
    
    /**
     * Define the sort values according to the original value and the defined configuration
     *
     * @param mixed $originalValue
     * @return array
     */
    public function defineSortValue($originalValue = null)
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
    public function definePageValue($originalValue = null)
    {
        $defaultPage = $this->configuration['pagination']['page'];
        if(is_null($originalValue)) {
            return $defaultPage['default'];
        }

        return $originalValue;
    }
    
    /**
     * Define the offset according to the original value and the defined configuration
     *
     * @param mixed $originalValue
     * @return integer
     */
    public function defineOffsetValue($originalValue = null)
    {
        $defaultOffset = $this->configuration['pagination']['offset'];
        if (is_null($originalValue)) {
            return $defaultOffset['default'];
        }

        return $originalValue;
    }
}
