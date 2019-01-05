<?php

namespace Bitty\Container;

use Psr\Container\ContainerInterface as PsrContainerInterface;

trait ContainerAwareTrait
{
    /**
     * @var PsrContainerInterface|null
     */
    protected $container = null;

    /**
     * {@inheritDoc}
     */
    public function setContainer(PsrContainerInterface $container = null): void
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function getContainer(): ?PsrContainerInterface
    {
        return $this->container;
    }
}
