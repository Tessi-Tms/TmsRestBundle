<?php

/**
 *
 * @author:  Gabriel BONDAZ <gabriel.bondaz@idci-consulting.fr>
 * @author:  Pierre FERROLLIET <pierre.ferrolliet@idci-consulting.fr>
 * @license: GPL
 *
 */

namespace Tms\Bundle\RestBundle\Formatter;

use Doctrine\Common\Persistence\ObjectManager;
use Tms\Bundle\RestBundle\Criteria\CriteriaBuilder;
use Symfony\Component\Routing\Router;
use JMS\Serializer\Serializer;

abstract class AbstractDoctrineHypermediaFormatter extends AbstractHypermediaFormatter
{
    protected $objectManager;
    protected $objectNamespace;

    /**
     * Dependency injection to set object manager to the formatter
     *
     * @param ObjectManager $objectManager
     * @param string $objectNamespace
     * 
     * @return array
     */
    public function setObjectManager(ObjectManager $objectManager, $objectNamespace)
    {
        $this->objectManager = $objectManager;
        $this->objectNamespace = $objectNamespace;

        return $this;
    }

    /**
     * Give a class metadata collection thanks to the
     * object manager and the object class namespace
     *
     * @return ClassMetadataCollection
     */
    public function getClassMetadata($namespace = null)
    {
        $namespace = $namespace ? $namespace : $this->objectNamespace;

        return $this
            ->objectManager
            ->getClassMetadata($namespace);
    }

    /**
     * Give a class namespace
     *
     * @return string
     */
    public function getClassNamespace($namespace = null)
    {
        $namespace = $namespace ? $namespace : $this->objectNamespace;

        return $this->getClassMetadata($namespace)->getName();
    }

    /**
     * Get Cleaned Name
     *
     * @param $namespace
     * @return string
     */
    protected function getCleanedObjectName($namespace = null)
    {
        $explodedClassName = $namespace ? $namespace : $this->getClassNamespace();
        $explodedClassName = explode(':', $explodedClassName);
        $explodedClassName = explode('\\', array_pop($explodedClassName));

        return array_pop($explodedClassName);
    }

    /**
     * Give a class identifier
     *
     * @return string
     */
    public function getClassIdentifier($namespace = null)
    {
        $identifiers = $this->getClassMetadata($namespace)->getIdentifier();

        return $identifiers[0];
    }

    /**
     * Give object type
     *
     * @return string
     */
    public function getType()
    {
        return $this->getClassNamespace();
    }

    /**
     * {@inheritdoc }
     */
    public function format()
    {
        $this->getObjectsFromRepository();

        return parent::format();
    }

    abstract public function getObjectsFromRepository();
}
