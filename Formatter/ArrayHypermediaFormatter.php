<?php

namespace Tms\Bundle\RestBundle\Formatter;

use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\Route;
use Symfony\Component\Config\Loader\LoaderInterface;
use JMS\Serializer\Serializer;
use Tms\Bundle\RestBundle\Request\ParamReaderProviderInterface;
use Tms\Bundle\RestBundle\Request\RequestProviderInterface;

class ArrayHypermediaFormatter extends AbstractHypermediaFormatter
{
    /**
     * Array of data
     *
     * @var array
     */
    protected $data;


    /**
     * Constructor
     *
     * @param Router                       $router              Instance of Router
     * @param Serializer                   $serializer          Instance of Serializer
     * @param LoaderInterface              $routeLoader         Instance of LoaderInterface
     * @param ParamReaderProviderInterface $paramReaderProvider Instance of ParamReaderProviderInterface
     * @param RequestProviderInterface     $requestProvider     Instance of RequestProviderInterface
     * @param string                       $currentRouteName    The current route name
     * @param string                       $format              The output format
     * @param array                        $data                The data
     */
    public function __construct(
        Router $router,
        Serializer $serializer,
        LoaderInterface $routeLoader,
        ParamReaderProviderInterface $paramReaderProvider,
        RequestProviderInterface $requestProvider,
        $currentRouteName,
        $format,
        array $data = array()
    )
    {
        $this->setData($data);

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
     * Set the array of data
     *
     * @param array $array An array of data
     * @return ArrayHypermediaFormatter
     */
    public function setData(array $data = array())
    {
        $this->data = $data;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function formatData()
    {
        return is_array($this->data) ? $this->data : array();
    }

    /**
     * Format raw data to have hypermedia links in output
     *
     * @return array
     */
    protected function formatLinks()
    {
        return array();
    }

    /**
     * Give object type
     *
     * @return string
     */
    protected function getType()
    {
        return 'array';
    }

    /**
     * {@inheritdoc }
     */
    protected function getSerializerContextGroup()
    {
        return AbstractHypermediaFormatter::SERIALIZER_CONTEXT_GROUP_ARRAY;
    }
}
