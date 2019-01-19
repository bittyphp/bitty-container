<?php

namespace Bitty\Container;

use Bitty\Container\Exception\InvalidArgumentException;
use Interop\Container\ServiceProviderInterface;
use Psr\Container\ContainerInterface as PsrContainerInterface;

interface ContainerInterface extends PsrContainerInterface
{
    /**
     * Sets a callable to build a service or a value for a parameter.
     *
     * @param string $id ID of service or parameter to set.
     * @param mixed $value Callable to build a service or value for parameter.
     */
    public function set(string $id, $value): void;

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
     * @param \Closure $closure
     *
     * @throws InvalidArgumentException If unable to extend.
     */
    public function extend(string $id, \Closure $closure): void;

    /**
     * Registers a service provider.
     *
     * @param ServiceProviderInterface $provider
     */
    public function register(ServiceProviderInterface $provider): void;
}
