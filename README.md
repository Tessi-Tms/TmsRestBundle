RestBundle
===========================

Symfony2 REST bundle


Installation
------------

Add dependencies in your `composer.json` file:
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

Install these new dependencies of your application:
```sh
$ php composer.phar update
```

Enable the bundle in your application kernel:
```php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new Tms\Bundle\RestBundle\TmsRestBundle(),
    );
}
```

Import the bundle configuration:
```yml
# app/config/config.yml

imports:
    - { resource: @TmsRestBundle/Resources/config/config.yml }
```

To check if every thing seem to be ok, you can execute this command:
```sh
$ php app/console container:debug
```

You'll get this result:
```sh
...
tms_rest.criteria_builder   container Tms\Bundle\RestBundle\Criteria\CriteriaBuilder
tms_rest.entity_handler     container Tms\Bundle\RestBundle\EntityHandler\EntityHandler
tms_rest.sort_builder       container Tms\Bundle\RestBundle\Sort\SortBuilder
...
```


Documentation
-------------

[Read the Documentation](Resources/doc/index.md)


Tests
-----

Install bundle dependencies:
```sh
$ php composer.phar update
```

To execute unit tests:
```sh
$ phpunit --coverage-text
```
