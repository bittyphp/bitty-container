# Bitty Container

[![Build Status](https://travis-ci.org/bittyphp/bitty-container.svg?branch=master)](https://travis-ci.org/bittyphp/bitty-container)
[![Codacy Badge](https://api.codacy.com/project/badge/Coverage/de162d6c119b48d3bf72bc7d93ecb2d9)](https://www.codacy.com/app/bittyphp/bitty-container)
[![Total Downloads](https://poser.pugx.org/bittyphp/bitty-container/downloads)](https://packagist.org/packages/bittyphp/bitty-container)
[![License](https://poser.pugx.org/bittyphp/bitty-container/license)](https://packagist.org/packages/bittyphp/bitty-container)

Bitty comes with a [PSR-11](http://www.php-fig.org/psr/psr-11/) compliant container. The container supports registering service providers that follow the (experimental) [service provider standard](https://github.com/container-interop/service-provider).

## Installation

It's best to install using [Composer](https://getcomposer.org/).

```sh
$ composer require bittyphp/bitty-container
```

## Checking for an Entry

If needed, you can check if a container entry exists before requesting the container for it.

```php
<?php

use Bitty\Container\Container;

$container = new Container();

if ($container->has('some_thing')) {
    echo 'some_thing is available';
}

// Or use array access
if (isset($container['some_thing'])) {
    echo 'some_thing is available';
}
```

## Getting an Entry

Getting an entry from the container is also easy. However, if the entry doesn't exist, the container will throw a `Bitty\Container\NotFoundException`.

```php
<?php

use Bitty\Container\Container;

$container = new Container();

$someThing = $container->get('some_thing');

// Or use array access
$someThing = $container['some_thing'];
```

## Adding an Entry

You can add entries to Bitty's container one of two ways: via the container `set` method or via a service provider.

The container is set up to support adding both services and parameters. All services must be built using an anonymous function (a `\Closure`). Any other value will be considered a parameter.

When you first request a service from the container, it calls the anonymous function and caches the result. The result can be anything - a string, an array, an object - you name it. On subsequent calls, you are given the same value each time.

The function will always be called with the container itself as the first argument. This allows you to reference other items from the container, if needed. If you do not need access to the container, you can omit the parameter from the function signature.

```php
<?php

use Acme\MyClass;
use Psr\Container\ContainerInterface;

// Access dependencies from the container
$service = function (ContainerInterface $container) {
    $dependency = $container->get('some_service');

    return new MyClass($dependency);
};

// Omit the container parameter if not needed
$parameter = function () {
    return 'some-value';
};
```

### Via a Setter

Set services and parameters directly on the container.

```php
<?php

use Acme\MyClass;
use Acme\MyOtherClass;
use Bitty\Container\Container;
use Psr\Container\ContainerInterface;

$container = new Container();

$container->set('my_service', function () {
    return new MyClass();
});

$container->set('my_other_service', function (ContainerInterface $container) {
    $myService = $container->get('my_service');

    return new MyOtherClass($myService);
});

$container->set('my_parameter', 'some value');

// Or use array access
$container['some_service'] = function () {
    return new MyClass();
};
$container['some_parameter'] = 'some value';
```

### Via a Service Provider

The more extensible option is to build a service provider using  `Interop\Container\ServiceProviderInterface` and register it using the `register()` method.

You can build a service provider to load service settings from anywhere - an XML file, YAML file, or maybe even JSON. More information is available in the Service Provider section.

## Extending an Entry

You can extend a container entry using the `extend()` method. Extensions can also be used to check for an existing entry and create one if not found. The function signature is similar to adding entries, except now there will be a second (nullable) parameter passed in.

You can also extend entries using service providers.

```php
<?php

use Acme\MyClass;
use Acme\MyOtherClass;
use Bitty\Container\Container;
use Psr\Container\ContainerInterface;

$container = new Container(
    [
        'my_service' => function () {
            return new MyClass();
        },
    ]
);

$container->extend('my_service', function (ContainerInterface $container, $previous = null) {
    if (null === $previous) {
        $previous = new MyClass();
    }

    return new MyOtherClass($previous);
});
```

## Service Providers

Service providers allow you to build or extend container services more easily and in a more portable way. It supports any implementation of `Interop\Container\ServiceProviderInterface` which means you can easily register the same service in different applications or projects.

WARNING: This is based on a [developing standard](https://github.com/container-interop/service-provider) and may change.

```php
<?php

namespace Acme;

use Interop\Container\ServiceProviderInterface;

class MyServiceProvider implements ServiceProviderInterface
{
    public function getFactories()
    {
        return [
            'my_service' => function () {
                return new MyClass();
            },
        ];
    }

    public function getExtensions()
    {
        return [
            'my_service' => function (ContainerInterface $container, $previous = null) {
                $myParam = $container->get('some_parameter');

                return new MyOtherClass($myParam, $previous);
            },
        ];
    }
}
```

Then register your provider with the container.

```php
<?php

use Acme\MyServiceProvider;
use Bitty\Container\Container;

$container = new Container();

$container->register(new MyServiceProvider());
```

## Making Container Aware Services

You can make any service automatically aware of the container by making your service implement the `ContainerAwareInterface`. When the service is built, it will be passed a reference to the container. There's a `ContainerAwareTrait` you can add to classes to make implementing the interface easier.
