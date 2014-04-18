TmsRestBundle
=============

A service which brings support for "hypermedia" links for REST web services
(based on FosRestBundle) and pagination.

How to use
----------

The first things to do is calling the formatter service in the controller
and give all the parameters to the service to format in hypermedia the object.

Let's take an example with Offer object.

Usually yo have two different needs :
* List all objects in hypermedia mode
* Retrieve an object in hypermedia mode

All the magical is in tms.rest.formatter.hypermedia service.

This service is able to :
* build collection/single formatter
* set the required object manager
* set all optionnal parameters
* format object in hypermedia mode

List all offers
---------------

```php
    /**
     * [GET] /offers
     * Retrieve a set of offers
     *
     * @QueryParam(name="name", nullable=true, description="(optional) Offer name")
     * @QueryParam(name="reference", nullable=true, description="(optional) Offer reference.")
     * @QueryParam(name="status", nullable=true, description="(optional) Offer status")
     * @QueryParam(name="limit", requirements="\d+", strict=true, nullable=true, description="(optional) Pagination limit")
     * @QueryParam(name="offset", requirements="\d+", strict=true, nullable=true, description="(optional) Pagination offset")
     * @QueryParam(name="page", requirements="\d+", strict=true, nullable=true, description="(optional) Page number")
     * @QueryParam(name="sort_field", nullable=true, description="(optional) Sort field")
     * @QueryParam(name="sort_order", nullable=true, description="(optional) Sort order")
     * 
     * @param string $name
     * @param string $reference
     * @param string $status
     * @param string $limit
     * @param string $offset
     * @param string $page
     * @param string $sort_field
     * @param string $sort_order
     */
    public function getOffersAction($name = null, $reference = null, $status = null, $limit = null, $offset = null, $page = null, $sort_field = null, $sort_order = null)
    {
        $view = $this->view(
            $this
                ->get('tms.rest.formatter.hypermedia') // hypermedia serfice
                ->buildCollectionFormatter(
                    $this->getRequest()->get('_route'),
                    $this->getRequest()->getRequestFormat()
                )
                // To build a collection formatter, the current route 
                // and format are required
                ->setObjectManager(
                    $this->get('doctrine.orm.entity_manager'),
                    $this
                        ->get('tms_operation.manager.offer')
                        ->getEntityClass() // Return Offer object class
                )
                // The object manager (odm OR orm) and the object namespace
                // are required to set the object manager
                ->setCriteria(array(
                    'name'      => $name,
                    'reference' => $reference,
                    'status'    => $status
                ))
                ->setLimit($limit)
                ->setSort(array(
                    'reference' => 'desc',
                    'id'        => 'asc'
                ))
                ->setOffset($offset)
                ->setPage($page)
                // criteria, limit, offset, sort and page are
                // optionnal parameters : if not given they are
                // guessed by the default configuration
                ->format()
                // magical function which is able to format hypermedia
            ,
            Codes::HTTP_OK
        );

        $serializationContext = SerializationContext::create()
            ->setGroups(array(
                HypermediaFormatter::SERIALIZER_CONTEXT_GROUP_COLLECTION
            ))
        ;
        $view->setSerializationContext($serializationContext);

        return $this->handleView($view);
    }
```

Retrieve one offer
------------------
```php
    /**
     * [GET] /offers/{id}
     * Retrieve an offer
     *
     * @param string $id
     */
    public function getOfferAction($id)
    {
        try {
            $view = $this->view(
            $this
                ->get('tms.rest.formatter.hypermedia')
                ->buildSingleFormatter(
                    $this->getRequest()->get('_route'),
                    $this->getRequest()->getRequestFormat(),
                    $id
                )
                // To build a collection formatter, the current route, format
                // and object ID are required
                ->setObjectManager(
                    $this->get('doctrine.orm.entity_manager'),
                    $this
                        ->get('tms_operation.manager.offer')
                        ->getEntityClass()
                )
                // Same parameters as for a collection
                ->addEmbedded(
                    'products', // embedded collection name
                    'api_products_get_product', // embedded single route
                    'api_offers_get_offer_products' // embedded collection route
                ) // example with related products of an offer
                ->addEmbedded(
                    'customers',
                    'api_customers_get_customer',
                    'api_offers_get_offer_customers'
                ) // example with related customers of an offer
                // addEmbedded() require 3 parameters :
                // embedded collection name, embedded single route, embedded collection route
                ->format(),
                Codes::HTTP_OK
            );
            
            $serializationContext = SerializationContext::create()
                ->setGroups(array(
                    HypermediaFormatter::SERIALIZER_CONTEXT_GROUP_SINGLE
                ))
            ;
            $view->setSerializationContext($serializationContext);

            return $this->handleView($view);

        } catch(NotFoundHttpException $e) {
            return $this->handleView($this->view(
                array(),
                $e->getStatusCode()
            ));
        }
    }
```

As you can see, two contexts of serialization are available :
* HypermediaFormatter::SERIALIZER_CONTEXT_GROUP_COLLECTION
* HypermediaFormatter::SERIALIZER_CONTEXT_GROUP_SINGLE

Theses contexts are useful to define which fields of an object will be
serialized in the choosen context (single/collection).
You can define them in serializer folder:

```yml
# Tms\Bundle\OperationBundle\Resources\config\serializer\Entity.Offer.yml
Tms\Bundle\OperationBundle\Entity\Offer:
    properties:
        id:
            expose: true
            groups: [list, details]
            # the ID property will be render in hypermedia
            # in "list" AND "details" serialization context

        name:
            expose: true
            groups: [list]
            # the NAME property will be render in hypermedia
            # ONLY in "list" serialization context
```

NB : "list" / "details" will be replaced respectively
by "embedded" / "single" shortly

The defaults values are available in REST Bundle Configuration.php file :
```php
namespace Tms\Bundle\RestBundle\DependencyInjection;
// ...
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        // ...

        $rootNode
            ->children()
                ->arrayNode('default')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('pagination')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->arrayNode('limit')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->integerNode('default')->defaultValue(20)->min(1)->end()
                                        ->integerNode('maximum')->defaultValue(50)->min(1)->end()
                                    ->end()
                                ->end()
                                ->arrayNode('sort')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->scalarNode('field')->defaultValue('id')->end()
                                        ->scalarNode('order')->defaultValue('asc')->end()
                                    ->end()
                                ->end()
                                ->arrayNode('offset')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->integerNode('default')->defaultValue(0)->end()
                                    ->end()
                                ->end()
                                ->arrayNode('page')
                                    ->addDefaultsIfNotSet()
                                    ->children()
                                        ->integerNode('default')->defaultValue(1)->min(1)->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            // ...
    }
}
```

So, if you did not have define a configuration for a particular route,
a call to the API will return these results
(thanks to the default configuration):

* /api/rest/offers.json  => 20 items from 0 to 20 ordered by ID column ASC
* /api/rest/offers.json?limit=100  => 50 items from 0 to 50 ordered by ID column ASC

You can override all pagination configuration parameters in two places:
* In the config.yml of the application :
```yml
tms_rest:
    default: # defaults pagination parameters can be override in config.yml file
        pagination:
            limit:
                default: X
                maximum: Y
    routes:
        api_offers_get_offers: # override pagination parameters for the route api_offers_get_offers
            pagination:
                limit:
                    default: X
                    maximum: Y
                offset: X
                sort:
                    field: X
                    order: Y
```

* In the controller :
```php
<?php

namespace MyApp\Bundle\MyBundle\Controller\Rest;

// ...
class MyController extends FOSRestController
{
    /**
     * // ...
     * @QueryParam(name="limit", default=10, requirements="\d+", strict=true, nullable=true, description="(optional) Pagination limit")
```   

Here is an example for defining the behavior of the pagination of a list of
offers (using FOS RestBundle): /api/rest/offers.json
```yml
# app/config/config.yml

# TMS Rest
tms_rest:
    default:
        pagination:
            limit: 
                default: 10    # Default value for the QueryParam named "limit"
                maximum: 30    # Maximum value allowed for the QueryParam named "limit"
    routes:
        api_offers_get_offers: # Example of a route name
            pagination:
                limit:
                    default: 15
                    maximum: 50
```

Then, a call to the API with the provided route (api_offers_get_offers)
will return these results:

* /api/rest/offers.json   => 50 items from 0 to 50
* /api/rest/offers.json?offset=30&limit=100  => 100 items from 30 to 130
* /api/rest/offers.json?limit=400  => 200 items from 0 to 200 (limit max=200 in our previous configuration)
* /api/rest/offers.json?sort_field=reference  => 50 items ordered by REFERENCE column ASC
* api/rest/offers.json?sort_order=desc  => 50 items ordered by ID column DESC

All overloadable pagination parameters
--------------------------------------

* limit
* offset
* page
* sort_field
* sort_order

