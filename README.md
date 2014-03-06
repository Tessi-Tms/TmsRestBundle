TmsRestBundle
=============

A service which brings support for "hypermedia" links for REST web services (based on FosRestBundle) and pagination.


Installation
------------

To install this bundle please follow these steps:

First, add the dependencies in your `composer.json` file:

```json
"repositories": [
    ...,
    {
        "type": "vcs",
        "url": "https://github.com/Tessi-Tms/TmsRestBundle.git"
    }
],
"require": {
        ...,
        "tms/rest-bundle": "dev-master"
    },
```

Then, install the bundle with the command:

```sh
composer update
```

Enable the bundle in your application kernel:

```php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        //
        new Tms\Bundle\RestBundle\TmsRestBundle(),
    );
}
```

Import the configuration file of the bundle into your application.

```yml
# app/config/config.yml

- { resource: @TmsRestBundle/Resources/config/config.yml }
```


Pagination
----------


Edit the configuration file of your application in order to define some rules for the pagination of resources lists.

Here is an example for defining the behavior of the pagination of a list of templates (using FOS RestBundle): /api/rest/templates.json

```yml
# app/config/config.yml

# TMS Rest
tms_rest:
    rest_templates__get_templates:    # The name of the route
        pagination_limit:             
            default: 50               # Default value for the QueryParam named "limit"
            maximum: 100              # Maximum value allowed for the QueryParam named "limit"
```

Then, a call to the API will return these results:


/api/rest/templates.json           => 50 items

/api/rest/templates.json?limit=10  => 10 items

/api/rest/templates.json?limit=500 => 100 items


Furthermore, the Rest Bundle provides a default configuration you can overwrite in you config.yml file.

```yml
tms_rest:
    default_configuration:
        pagination_limit:             
            default: 20               # Default value for the QueryParam named "limit"
            maximum: 200              # Maximum value allowed for the QueryParam named "limit"
```

So, if you did not have define a configuration for a particular route, a call to the API will return these results:


/api/rest/templates.json           => 20 items

/api/rest/templates.json?limit=10  => 10 items

/api/rest/templates.json?limit=500 => 200 items


