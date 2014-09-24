<?php

/**
 *
 * @author:  Gabriel BONDAZ <gabriel.bondaz@idci-consulting.fr>
 * @author:  Pierre FERROLLIET <pierre.ferrolliet@idci-consulting.fr>
 * @license: GPL
 *
 */

namespace Tms\Bundle\RestBundle\Formatter;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\Router;
use Symfony\Component\Config\Loader\LoaderInterface;
use JMS\Serializer\Serializer;
use Tms\Bundle\RestBundle\Request\ParamReaderProviderInterface;
use Tms\Bundle\RestBundle\Request\RequestProviderInterface;

class DoctrineItemHypermediaFormatter extends AbstractDoctrineHypermediaFormatter
{
    protected $routeParameters = array();
    protected $objectCriteria  = array();
    protected $object          = null;
    protected $embeddeds       = null;

    /**
     * Constructor
     */
    public function __construct(
        Router $router,
        Serializer $serializer,
        LoaderInterface $routeLoader,
        ParamReaderProviderInterface $paramReaderProvider,
        RequestProviderInterface $requestProvider,
        $currentRouteName,
        $format,
        $routeParameters = array(),
        $objectCriteria = array()
    )
    {
        $this->routeParameters = $routeParameters;
        $this->objectCriteria  = $objectCriteria;
        if (count($objectCriteria) == 0) {
            $this->objectCriteria  = $routeParameters;
        }

        parent::__construct(
            $router,
            $serializer,
            $routeLoader,
            $paramReaderProvider,
            $requestProvider,
            $currentRouteName,
            $format
        );
    }

    /**
     * Format data into a given layout for hypermedia
     *
     * @return array
     */
    public function formatData()
    {
        return $this->object;
    }

    /**
     * Format links into a given layout for hypermedia
     *
     * @return array
     */
    protected function formatLinks()
    {
        $routeParameters = $this->routeParameters;
        if (count($this->routeParameters) == 0) {
            $classIdentifier = $this->getClassIdentifier(get_class($this->object));
            $getMethod = sprintf("get%s", ucfirst($classIdentifier));
            $routeParameters = array($classIdentifier => $this->object->$getMethod());
        }

        return array(
            'self' => array(
                'rel' => 'self',
                'href' => $this->generateLink(
                    $this->currentRouteName,
                    $routeParameters
                )
            ),
            'embeddeds' => $this->formatEmbeddeds()
        );
    }

    /**
     * Format embedded data of 1st depth into a given layout for hypermedia
     * array(
     *      'data'     => X,
     *      'metadata' => X
     *      'links'    => X,
     *      'embedded' => $this->formatEmbedded()
     * )
     *
     * @return array
     */
    protected function formatEmbeddeds()
    {
        return $this->embeddeds;
    }

    /**
     * Find single object from repository with objectCriteria
     *
     * @return Object
     */
    protected function getObjectsFromRepository()
    {
        if(!$this->object) {
            $object = $this->objectManager
                ->getRepository($this->objectNamespace)
                ->findOneBy($this->objectCriteria)
            ;

            if (!$object) {
                throw new NotFoundHttpException();
            }

            $this->object = $object;
        }

        return $this;
    }

    /**
     * Generate a link
     * 
     * @param string  $routeName
     * @param array   $parameters
     * 
     * @return Collection
     */
    protected function generateLink($routeName, array $parameters = array())
    {
        return $this->router->generate(
            $routeName,
            array_merge(
                array('_format' => $this->format),
                $this->routeParameters,
                $parameters
            ),
            true
        );
    }

    /**
     * Check if a requested embedded element is actually
     * mapped by the single object
     *
     * @param string $embeddedName
     * 
     * @return boolean
     */
    protected function isEmbeddedMappedBySingleEntity($embeddedName)
    {
        return array_key_exists(
            $embeddedName,
            $this->getClassMetadata()->associationMappings
        );
    }

    /**
     * {@inheritdoc }
     */
    protected function getSerializerContextGroup()
    {
        return AbstractHypermediaFormatter::SERIALIZER_CONTEXT_GROUP_ITEM;
    }

    /**
     * Add an embedded element to a single hypermedia object
     * You can chain this method easily
     *
     * @param string $embeddedName
     * @param string $embeddedCollectionRoute
     * @param array  $parameters
     * 
     * @return $this
     */
    public function addEmbedded($embeddedName, $embeddedCollectionRoute, array $parameters = array())
    {
        $this->getObjectsFromRepository();

        if($this->isEmbeddedMappedBySingleEntity($embeddedName) || !empty($parameters)) {
            $this->embeddeds[$embeddedName] = array(
                'rel'  => 'embedded',
                'href' => $this->generateLink(
                    $embeddedCollectionRoute,
                    $parameters
                )
            );
        }

        return $this;
    }

    /**
     * {@inheritdoc }
     */
    protected function retrieveRoutePath(Route $route)
    {
        $path = $route->getPath();

        $classIdentifier = $this->getClassIdentifier(get_class($this->object));
        $id = sprintf('{%s}', $classIdentifier);

        if (strpos($path, $id) === false) {
            return null;
        }

        $getMethod = sprintf("get%s", ucfirst($classIdentifier));

        return str_replace(
            array($id, '{_format}'),
            array($this->object->$getMethod(), $this->format),
            $path
        );
    }
}
