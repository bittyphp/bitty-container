<?php

namespace Bitty\Container;

use Bitty\Container\ContainerAwareInterface;
use Bitty\Container\ContainerInterface;
use Bitty\Container\Exception\InvalidArgumentException;
use Bitty\Container\Exception\NotFoundException;
use Interop\Container\ServiceProviderInterface;
use Psr\Container\ContainerInterface as PsrContainerInterface;

class Container implements ContainerInterface, \ArrayAccess
{
    /**
     * @var mixed[]
     */
    protected $data = [];

    /**
     * @var mixed[]
     */
    protected $cache = [];

    /**
     * @param mixed[] $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $id, $value): void
    {
        if (isset($this->cache[$id])) {
            unset($this->cache[$id]);
        }

        $this->data[$id] = $value;
    }

    /**
     * @param string $id
     *
     * @return bool
     */
    public function has($id): bool
    {
        return isset($this->data[$id]);
    }

    /**
     * @param string $id
     *
     * @return mixed
     */
    public function get($id)
    {
        if (isset($this->cache[$id])) {
            return $this->cache[$id];
        }

        if (!isset($this->data[$id])) {
            throw new NotFoundException(
                sprintf('Container entry "%s" not found.', $id)
            );
        }

        $value = $this->data[$id];
        if (!$value instanceof \Closure) {
            return $value;
        }

        $this->cache[$id] = $value($this);
        if ($this->cache[$id] instanceof ContainerAwareInterface) {
            $this->cache[$id]->setContainer($this);
        }

        return $this->cache[$id];
    }

    /**
     * {@inheritDoc}
     */
    public function remove(string $id): void
    {
        if (!isset($this->data[$id])) {
            return;
        }

        unset($this->data[$id]);

        if (isset($this->cache[$id])) {
            unset($this->cache[$id]);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function extend(string $id, \Closure $closure): void
    {
        if (!isset($this->data[$id])) {
            $this->data[$id] = $closure;

            return;
        }

        $factory = $this->data[$id];
        if (!$factory instanceof \Closure) {
            throw new InvalidArgumentException(
                sprintf(
                    'Container entry "%s" is a parameter; it cannot be extended.',
                    $id
                )
            );
        }

        $this->data[$id] = function (PsrContainerInterface $container) use ($factory, $closure) {
            $previous = $factory($container);

            return $closure($container, $previous);
        };
    }

    /**
     * {@inheritDoc}
     */
    public function register(ServiceProviderInterface $provider): void
    {
        $this->data = array_merge(
            $this->data,
            $provider->getFactories()
        );

        $extensions = $provider->getExtensions();
        foreach ($extensions as $id => $extension) {
            if ($extension instanceof \Closure) {
                $this->extend($id, $extension);
            }
        }
    }

    /**
     * {@inheritDoc}
     *
     * @param string $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value): void
    {
        $this->set($offset, $value);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $offset
     *
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return $this->has($offset);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $offset
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $offset
     */
    public function offsetUnset($offset): void
    {
        $this->remove($offset);
    }
}
