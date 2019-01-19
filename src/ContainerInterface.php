<?php

namespace Bitty\Container;

use Interop\Container\ServiceProviderInterface;
use Psr\Container\ContainerInterface as PsrContainerInterface;

interface ContainerInterface extends PsrContainerInterface
{
    /**
     * Sets a callable to build a service.
     *
     * @param string $id ID of service to build.
     * @param callable $callable Callable to build the service.
     */
    public function set(string $id, callable $callable): void;

    /**
     * Removes an entry from the container.
     *
     * @param string $id
     */
    public function remove(string $id): void;

    /**
     * Extends a callable.
     *
     * @param string $id
     * @param callable $callable
     */
    public function extend(string $id, callable $callable): void;

    /**
     * Registers a list of service providers.
     *
     * @param ServiceProviderInterface[] $providers
     */
    public function register(array $providers): void;
}
