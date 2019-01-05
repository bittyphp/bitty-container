<?php

namespace Bitty\Tests\Container;

use Bitty\Container\ContainerAwareTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ContainerAwareTraitTest extends TestCase
{
    public function testContainer(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        /**
         * @var ContainerAwareTrait
         */
        $fixture = $this->getObjectForTrait(ContainerAwareTrait::class);
        $fixture->setContainer($container);

        $actual = $fixture->getContainer();

        self::assertSame($container, $actual);
    }
}
