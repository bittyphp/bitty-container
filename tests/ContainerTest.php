<?php

namespace Bitty\Tests\Container;

use Bitty\Container\Container;
use Bitty\Container\ContainerAwareInterface;
use Bitty\Container\ContainerInterface;
use Bitty\Container\Exception\InvalidArgumentException;
use Bitty\Container\Exception\NotFoundException;
use Interop\Container\ServiceProviderInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface as PsrContainerInterface;

class ContainerTest extends TestCase
{
    /**
     * @var Container
     */
    private $fixture = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixture = new Container();
    }

    public function testInstanceOf(): void
    {
        self::assertInstanceOf(ContainerInterface::class, $this->fixture);
        self::assertInstanceOf(PsrContainerInterface::class, $this->fixture);
        self::assertInstanceOf(\ArrayAccess::class, $this->fixture);
    }

    /**
     * @param \Closure[] $callables
     * @param string $name
     * @param bool $expected
     *
     * @dataProvider sampleHas
     */
    public function testHas(array $callables, string $name, bool $expected): void
    {
        $this->fixture = new Container($callables);

        $actual = $this->fixture->has($name);

        self::assertSame($expected, $actual);
    }

    /**
     * @param \Closure[] $callables
     * @param string $name
     * @param bool $expected
     *
     * @dataProvider sampleHas
     */
    public function testOffsetExists(array $callables, string $name, bool $expected): void
    {
        $this->fixture = new Container($callables);

        $actual = $this->fixture->offsetExists($name);

        self::assertSame($expected, $actual);
    }

    public function sampleHas(): array
    {
        $name = uniqid();

        return [
            'has true' => [
                'callables' => [
                    $name => function () {
                    },
                ],
                'name' => $name,
                'expected' => true,
            ],
            'has false' => [
                'callables' => [
                    $name => function () {
                    },
                ],
                'name' => uniqid(),
                'expected' => false,
            ],
        ];
    }

    public function testGet(): void
    {
        $name   = uniqid();
        $object = new \stdClass();
        $this->fixture->set($name, function () use ($object) {
            return $object;
        });

        $actual = $this->fixture->get($name);

        self::assertSame($object, $actual);
    }

    public function testOffsetGet(): void
    {
        $name   = uniqid();
        $object = new \stdClass();
        $this->fixture->offsetSet($name, function () use ($object) {
            return $object;
        });

        $actual = $this->fixture->offsetGet($name);

        self::assertSame($object, $actual);
    }

    public function testGetCachesValue(): void
    {
        $name = uniqid();
        $this->fixture->set($name, function () {
            return new \stdClass();
        });

        $actualA = $this->fixture->get($name);
        $actualB = $this->fixture->get($name);

        self::assertSame($actualA, $actualB);
    }

    public function testOffsetGetCachesValue(): void
    {
        $name = uniqid();
        $this->fixture->offsetSet($name, function () {
            return new \stdClass();
        });

        $actualA = $this->fixture->offsetGet($name);
        $actualB = $this->fixture->offsetGet($name);

        self::assertSame($actualA, $actualB);
    }

    /**
     * @param string $id
     * @param mixed $value
     * @param mixed $expected
     *
     * @dataProvider sampleSet
     */
    public function testSet(string $id, $value, $expected): void
    {
        $this->fixture->set($id, $value);

        $actual = $this->fixture->get($id);

        self::assertEquals($expected, $actual);
    }

    /**
     * @param string $id
     * @param mixed $value
     * @param mixed $expected
     *
     * @dataProvider sampleSet
     */
    public function testOffsetSet(string $id, $value, $expected): void
    {
        $this->fixture->offsetSet($id, $value);

        $actual = $this->fixture->offsetGet($id);

        self::assertEquals($expected, $actual);
    }

    public function sampleSet(): array
    {
        $object = (object) [uniqid('a') => uniqid('b')];
        $bool   = (bool) rand(0, 1);
        $int    = rand();
        $string = uniqid();
        $float  = pi();
        $array  = [uniqid()];

        return [
            'closure' => [
                'id' => uniqid(),
                'value' => function () use ($object) {
                    return $object;
                },
                'expected' => $object,
            ],
            'bool' => [
                'id' => uniqid(),
                'value' => $bool,
                'expected' => $bool,
            ],
            'int' => [
                'id' => uniqid(),
                'value' => $int,
                'expected' => $int,
            ],
            'string' => [
                'id' => uniqid(),
                'value' => $string,
                'expected' => $string,
            ],
            'float' => [
                'id' => uniqid(),
                'value' => $float,
                'expected' => $float,
            ],
            'array' => [
                'id' => uniqid(),
                'value' => $array,
                'expected' => $array,
            ],
            'object' => [
                'id' => uniqid(),
                'value' => $object,
                'expected' => $object,
            ],
        ];
    }

    public function testSetResetsCache(): void
    {
        $name   = uniqid();
        $object = new \stdClass();
        $this->fixture->set($name, function () use ($object) {
            return $object;
        });

        $actualA = $this->fixture->get($name);

        $this->fixture->set($name, function () {
            return new \stdClass();
        });

        $actualB = $this->fixture->get($name);

        self::assertNotSame($actualA, $actualB);
    }

    public function testOffsetSetResetsCache(): void
    {
        $name = uniqid();

        $this->fixture->offsetSet($name, function () {
            return new \stdClass();
        });

        $actualA = $this->fixture->offsetGet($name);

        $this->fixture->offsetSet($name, function () {
            return new \stdClass();
        });

        $actualB = $this->fixture->offsetGet($name);

        self::assertNotSame($actualA, $actualB);
    }

    public function testGetSetsContainerOnContainerAwareService(): void
    {
        $name    = uniqid();
        $service = $this->createMock(ContainerAwareInterface::class);

        $this->fixture->set($name, function () use ($service) {
            return $service;
        });

        $service->expects(self::once())
            ->method('setContainer')
            ->with($this->fixture);

        $this->fixture->get($name);
    }

    public function testOffsetGetSetsContainerOnContainerAwareService(): void
    {
        $name    = uniqid();
        $service = $this->createMock(ContainerAwareInterface::class);

        $this->fixture->offsetSet($name, function () use ($service) {
            return $service;
        });

        $service->expects(self::once())
            ->method('setContainer')
            ->with($this->fixture);

        $this->fixture->offsetGet($name);
    }

    public function testGetThrowsException(): void
    {
        $name = uniqid();

        $message = 'Container entry "'.$name.'" not found.';
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage($message);

        $this->fixture->get($name);
    }

    public function testOffsetGetThrowsException(): void
    {
        $name = uniqid();

        $message = 'Container entry "'.$name.'" not found.';
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage($message);

        $this->fixture->offsetGet($name);
    }

    public function testRemove(): void
    {
        $name = uniqid();
        $this->fixture->set($name, function () {
            return new \stdClass();
        });

        $this->fixture->get($name);
        $this->fixture->remove($name);

        $actual = $this->fixture->has($name);

        self::assertFalse($actual);
    }

    public function testRemoveNonExistentService(): void
    {
        try {
            $this->fixture->remove(uniqid());
        } catch (\Exception $e) {
            self::fail($e->getMessage());
        }

        self::assertTrue(true);
    }

    public function testOffsetUnset(): void
    {
        $name = uniqid();
        $this->fixture->offsetSet($name, function () {
            return new \stdClass();
        });

        $this->fixture->offsetGet($name);
        $this->fixture->offsetUnset($name);

        $actual = $this->fixture->offsetExists($name);

        self::assertFalse($actual);
    }

    public function testExtendNonExistentId(): void
    {
        $name   = uniqid();
        $object = new \stdClass();

        $this->fixture->extend(
            $name,
            function (PsrContainerInterface $container, $previous = null) use ($object) {
                self::assertNull($previous);

                return $object;
            }
        );

        $actual = $this->fixture->get($name);

        self::assertSame($object, $actual);
    }

    public function testExtendExistingId(): void
    {
        $name    = uniqid();
        $objectA = new \stdClass();
        $objectB = new \stdClass();

        $this->fixture->set($name, function () use ($objectA) {
            return $objectA;
        });

        $this->fixture->extend(
            $name,
            function (PsrContainerInterface $container, $previous = null) use ($objectA, $objectB) {
                self::assertSame($objectA, $previous);

                return $objectB;
            }
        );

        $actual = $this->fixture->get($name);

        self::assertSame($objectB, $actual);
    }

    public function testExtendParameter(): void
    {
        $name = uniqid();

        $this->fixture->set($name, uniqid());

        self::expectException(InvalidArgumentException::class);
        self::expectExceptionMessage(
            'Container entry "'.$name.'" is a parameter; it cannot be extended.'
        );

        $this->fixture->extend(
            $name,
            function () {
            }
        );
    }

    public function testRegisterSingleProviderMultipleFactories(): void
    {
        $nameA   = uniqid();
        $nameB   = uniqid();
        $objectA = new \stdClass();
        $objectB = new \stdClass();

        $provider = $this->createConfiguredMock(
            ServiceProviderInterface::class,
            [
                'getFactories' => [
                    $nameA => function (PsrContainerInterface $container) use ($objectA) {
                        return $objectA;
                    },
                    $nameB => function (PsrContainerInterface $container) use ($objectB) {
                        return $objectB;
                    },
                ],
                'getExtensions' => [],
            ]
        );

        $this->fixture->register($provider);

        $actualA = $this->fixture->get($nameA);
        $actualB = $this->fixture->get($nameB);

        self::assertSame($objectA, $actualA);
        self::assertSame($objectB, $actualB);
    }

    public function testRegisterSingleProviderMultipleExtensions(): void
    {
        $nameA   = uniqid();
        $nameB   = uniqid();
        $objectA = new \stdClass();
        $objectB = new \stdClass();

        $this->fixture->set($nameA, function () use ($objectB) {
            return $objectB;
        });

        $provider = $this->createConfiguredMock(
            ServiceProviderInterface::class,
            [
                'getFactories' => [],
                'getExtensions' => [
                    $nameA => function (PsrContainerInterface $container, $previous = null) use ($objectA, $objectB) {
                        self::assertSame($objectB, $previous);

                        return $objectA;
                    },
                    $nameB => function (PsrContainerInterface $container, $previous = null) use ($objectB) {
                        self::assertNull($previous);

                        return $objectB;
                    },
                    uniqid() => uniqid(),
                ],
            ]
        );

        $this->fixture->register($provider);

        $actualA = $this->fixture->get($nameA);
        $actualB = $this->fixture->get($nameB);

        self::assertSame($objectA, $actualA);
        self::assertSame($objectB, $actualB);
    }
}
