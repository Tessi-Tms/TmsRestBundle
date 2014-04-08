TmsRestBundle
=============

A service which brings support for "hypermedia" links for REST web services
(based on FosRestBundle) and pagination.

How to use
----------

You can define for each route two parameters to filter the API results :
* default limit : if no limit is given
* max limit : prevent user to query too many results

The defaults values are available in Configuration.php file :
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
                        ->arrayNode('pagination_limit')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->integerNode('default')->*defaultValue(20)*->min(1)->end()
                                ->integerNode('maximum')->*defaultValue(50)*->min(1)->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
            // ...
    }
}
```

So, if you did not have define a configuration for a particular route, a call to the API will return these results:

* /api/rest/offers.json  => 20 items from 0 to 20 ordered by ID column ASC
* /api/rest/offers.json?limit=100  => 50 items from 0 to 50 ordered by ID column ASC

You can override limits in two levels:
* In the config.yml of the application :
```yml
tms_rest:
    default:
        pagination_limit:
            default: X
            maximum: X
    routes:
        api_offers_get_offers:
            pagination_limit:
                default: X
                maximum: X
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
     * @QueryParam(name="limit", requirements="\d+", strict=true, nullable=true, description="(optional) Pagination limit")
```   

Here is an example for defining the behavior of the pagination of a list of
offers (using FOS RestBundle): /api/rest/offers.json
```yml
# app/config/config.yml

# TMS Rest
tms_rest:
    default:
        pagination_limit:  # The name of the route
            default: 10    # Default value for the QueryParam named "limit"
            maximum: 30    # Maximum value allowed for the QueryParam named "limit"
    routes:
        api_offers_get_offers: # Example of a route name
            pagination_limit:
                default: 15
                maximum: 50
```

Then, a call to the API with the provided route (api_offers_get_offers)
will return these results:

* /api/rest/offers.json   => 50 items from 0 to 50
* /api/rest/offers.json?offset=30&limit=100  => 100 items from 30 to 130
* /api/rest/offers.json?limit=400  => 200 items from 0 to 200 (limit max=200 in our previous configuration)
* /api/rest/offers.json?orderbycolumn=reference  => 50 items ordered by REFERENCE column ASC
* api/rest/offers.json?orderbydirection=desc  => 50 items ordered by ID column DESC

Available parameters
--------------------

* orderbycolumn
* orderbydirection
* limit
* offset

