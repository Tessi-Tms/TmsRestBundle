<?php

/**
 *
* @author:  Gabriel BONDAZ <gabriel.bondaz@idci-consulting.fr>
* @author:  Jean-Philippe CHATEAU <jp.chateau@trepia.fr>
* @license: GPL
*
*/

namespace Tms\Bundle\RestBundle\EntityHandler;

class EntityHandler
{
    /**
     * Get the given sub-resource of an entity
     *
     * @param Object $entity
     * @param string $resourceName
     * @return array
     */
    public function getSubResource($entity, $resourceName)
    {
        $methodName = sprintf('get%s', self::camelize($resourceName));
        if (!method_exists($entity, $methodName)) {
            throw new \RuntimeException(sprintf(
                'The method %s does not exist in class %s.',
                $methodName,
                get_class($entity)
            ));
        }

        $data = array();
        $elements = $entity->$methodName();
        foreach ($elements as $element) {
            array_push($data, $element);
        }

        return $data;
    }

    /**
     * Parse an array of parameters and use the matching setters
     *
     * @param Object $entity
     * @param array $parameters
     * @return boolean
     */
    public function fromArray($entity, array $parameters)
    {
        if (!count($parameters)) {
            return false;
        }

        foreach ($parameters as $name => $value) {
            $methodName = sprintf('set%s', self::camelize($name));
            if (!method_exists($entity, $methodName)) {
                throw new \RuntimeException(sprintf(
                    'The method %s does not exist in class %s.',
                    $methodName,
                    get_class($entity)
                ));
            }
            $entity->$methodName($value);
        }

        return true;
    }

    /**
     * Link two entities according to the given mapping
     *
     * @param Object $entity
     * @param Object $linkEntity
     * @param array $linkMapping
     * @return boolean
     */
    public function link($entity, $linkEntity, $linkMapping)
    {
        $isLinked = false;
        $className = get_class($linkEntity);
        if (isset($linkMapping[$className])) {
            if (!$entity->$linkMapping[$className]['has']($linkEntity)) {
                $entity->$linkMapping[$className]['add']($linkEntity);
                $isLinked = true;
            }
        }

        return $isLinked;
    }

    /**
     * Unlink two entities according to the given mapping
     *
     * @param Object $entity
     * @param Object $linkEntity
     * @param array $linkMapping
     * @return boolean
     */
    public function unlink($entity, $linkEntity, $linkMapping)
    {
        $isUnlinked = false;
        $className = get_class($linkEntity);
        if (isset($linkMapping[$className])) {
            if ($entity->$linkMapping[$className]['has']($linkEntity)) {
                $entity->$linkMapping[$className]['delete']($linkEntity);
                $isUnlinked = true;
            }
        }

        return $isUnlinked;
    }

    /**
     * Returns given word as CamelCased
     *
     * Converts a word like "send_email" to "SendEmail". It
     * will remove non alphanumeric character from the word, so
     * "who's online" will be converted to "WhoSOnline"
     *
     * @access public
     * @static
     * @see variablize
     * @param    string    $word    Word to convert to camel case
     * @return string UpperCamelCasedWord
     */
    private static function camelize($word)
    {
        return str_replace(' ', '', ucwords(preg_replace('/[^A-Z^a-z^0-9]+/', ' ', $word)));
    }
}