<?php

namespace Bitty\Tests\Container;

use Bitty\Container\ContainerAwareTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ContainerAwareTraitTest extends TestCase
{
    /**
     * @var ContainerAwareTrait|MockObject
     */
    protected $fixture = null;

    protected function setUp()
    {
        parent::setUp();

        $this->fixture = $this->getObjectForTrait(ContainerAwareTrait::class);
    }

    public function testContainer()
    {
        $container = $this->createMock(ContainerInterface::class);

        $this->fixture->setContainer($container);
        $actual = $this->fixture->getContainer();

        $this->assertSame($container, $actual);
    }
}
