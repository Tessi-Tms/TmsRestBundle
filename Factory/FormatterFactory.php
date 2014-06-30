<?php

/**
 *
 * @author:  Gabriel BONDAZ <gabriel.bondaz@idci-consulting.fr>
 * @author:  Pierre FERROLLIET <pierre.ferrolliet@idci-consulting.fr>
 * @license: GPL
 *
 */

namespace Tms\Bundle\RestBundle\Factory;

use Tms\Bundle\RestBundle\Formatter\DoctrineCollectionHypermediaFormatter;
use Tms\Bundle\RestBundle\Formatter\DoctrineItemHypermediaFormatter;
use Tms\Bundle\RestBundle\Criteria\CriteriaBuilder;
use Symfony\Component\Routing\Router;
use JMS\Serializer\Serializer;

class FormatterFactory
{
    private $formatterProviders = array();

    // Services
    protected $serializer;
    protected $router;
    protected $criteriaBuilder;

    /**
     * Constructor
     *
     */
    public function __construct(Router $router, CriteriaBuilder $criteriaBuilder, Serializer $serializer)
    {
        $this->router = $router;
        $this->criteriaBuilder = $criteriaBuilder;
        $this->serializer = $serializer;
    }

    /**
     * Instantiate a DoctrineCollectionHypermediaFormatter (TO DO: DELETE)
     *
     * @param string $currentRouteName
     * @param string $format
     *
     * @return DoctrineCollectionHypermediaFormatter
     */
    public function buildDoctrineCollectionHypermediaFormatter($currentRouteName, $format)
    {
        return new DoctrineCollectionHypermediaFormatter(
            $this->router,
            $this->criteriaBuilder,
            $this->serializer,
            $currentRouteName,
            $format
        );
    }

    /**
     * Instantiate a DoctrineItemHypermediaFormatter (TO DO: DELETE)
     *
     * @param string $currentRouteName
     * @param string $format
     * @param mixed  $objectPKValue
     * @param mixed  $objectPK
     *
     * @return DoctrineItemHypermediaFormatter
     */
    public function buildDoctrineItemHypermediaFormatter($currentRouteName, $format, $objectPKValue, $objectPK = 'id')
    {
        return new DoctrineItemHypermediaFormatter(
            $this->router,
            $this->criteriaBuilder,
            $this->serializer,
            $currentRouteName,
            $format,
            $objectPKValue,
            $objectPK
        );
    }

    /**
     * Add a formatter provider.
     *
     * @param string $id                The id of the formatter provider.
     * @param string $formatterProvider The formatter provider.
     */
    public function addFormatterProvider($id, $formatterProvider)
    {
        $this->formatterProviders[$id] = $formatterProvider;
    }

    /**
     * Add a formatter provider.
     *
     * @param string $id       The id of the formatter provider.
     * @param string $provider The provider.
     */
    public function getFormatterProvider($id, $provider)
    {
        if (!isset($this->formatterProviders[$id])) {
            throw new \LogicException(sprintf(
                'The provider "%s" is not defined.',
                $id
            ));
        }

        return $this->formatterProviders[$id];
    }

    /**
     * Create and return a hypermedia formatter.
     *
     * @param string $providerId The id of the formatter provider.
     * @param mixed  params      The list of arguments to pass to the constructor of the formatter.
     *
     * @return object A formatter.
     */
    public function create($providerId)
    {
        $provider = $this->getFormatterProvider($providerId);

        $arguments = func_get_args();
        array_shift($arguments);

        return $provider->create($arguments);
    }
}
