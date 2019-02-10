<?php

namespace Bitty\Container;

use Psr\Container\ContainerInterface as PsrContainerInterface;

interface ContainerAwareInterface
{
    /**
     * Sets the container.
     *
     * @param PsrContainerInterface|null $container
     */
    public function setContainer(?PsrContainerInterface $container = null): void;

    /**
     * Gets the container.
     *
     * @return PsrContainerInterface|null
     */
    public function getContainer(): ?PsrContainerInterface;
}
