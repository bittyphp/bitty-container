<?php

namespace Bitty\Tests\Container;

use Bitty\Container\Container;
use Bitty\Container\ContainerAwareInterface;
use Bitty\Container\ContainerInterface;
use Bitty\Container\Exception\NotFoundException;
use Interop\Container\ServiceProviderInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface as PsrContainerInterface;

class ContainerTest extends TestCase
{
    /**
     * @var Container
     */
    protected $fixture = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fixture = new Container();
    }

    public function testInstanceOf(): void
    {
        self::assertInstanceOf(ContainerInterface::class, $this->fixture);
        self::assertInstanceOf(PsrContainerInterface::class, $this->fixture);
    }

    /**
     * @dataProvider sampleHas
     */
    public function testHas(array $callables, string $name, bool $expected): void
    {
        $this->fixture = new Container($callables);

        $actual = $this->fixture->has($name);

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

    public function testGetThrowsException(): void
    {
        $name = uniqid();

        $message = 'Service "'.$name.'" does not exist.';
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage($message);

        $this->fixture->get($name);
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

    public function testRegisterNoProviders(): void
    {
        try {
            $this->fixture->register([]);
        } catch (\Exception $e) {
            self::fail();
        }

        self::assertTrue(true);
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

        $this->fixture->register([$provider]);

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
                ],
            ]
        );

        $this->fixture->register([$provider]);

        $actualA = $this->fixture->get($nameA);
        $actualB = $this->fixture->get($nameB);

        self::assertSame($objectA, $actualA);
        self::assertSame($objectB, $actualB);
    }

    public function testRegisterMultipleProvidersMultipleFactories(): void
    {
        $nameA   = uniqid();
        $nameB   = uniqid();
        $nameC   = uniqid();
        $objectA = new \stdClass();
        $objectB = new \stdClass();
        $objectC = new \stdClass();
        $objectD = new \stdClass();

        $providerA = $this->createConfiguredMock(
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

        $providerB = $this->createConfiguredMock(
            ServiceProviderInterface::class,
            [
                'getFactories' => [
                    $nameC => function (PsrContainerInterface $container) use ($objectC) {
                        return $objectC;
                    },
                    $nameB => function (PsrContainerInterface $container) use ($objectD) {
                        return $objectD;
                    },
                ],
                'getExtensions' => [],
            ]
        );

        $this->fixture->register([$providerA, $providerB]);

        $actualA = $this->fixture->get($nameA);
        $actualB = $this->fixture->get($nameB);
        $actualC = $this->fixture->get($nameC);

        self::assertSame($objectA, $actualA);
        self::assertSame($objectD, $actualB);
        self::assertSame($objectC, $actualC);
    }

    public function testRegisterMultipleProvidersMultipleExtensions(): void
    {
        $nameA   = uniqid();
        $nameB   = uniqid();
        $objectA = new \stdClass();
        $objectB = new \stdClass();
        $objectC = new \stdClass();
        $objectD = new \stdClass();

        $providerA = $this->createConfiguredMock(
            ServiceProviderInterface::class,
            [
                'getFactories' => [
                    $nameA => function (PsrContainerInterface $container) use ($objectA) {
                        return $objectA;
                    },
                ],
                'getExtensions' => [
                    $nameB => function (PsrContainerInterface $container, $previous = null) use ($objectB) {
                        self::assertNull($previous);

                        return $objectB;
                    },
                ],
            ]
        );

        $providerB = $this->createConfiguredMock(
            ServiceProviderInterface::class,
            [
                'getFactories' => [],
                'getExtensions' => [
                    $nameA => function (PsrContainerInterface $container, $previous = null) use ($objectA, $objectC) {
                        self::assertSame($objectA, $previous);

                        return $objectC;
                    },
                    $nameB => function (PsrContainerInterface $container, $previous = null) use ($objectB, $objectD) {
                        self::assertSame($objectB, $previous);

                        return $objectD;
                    },
                ],
            ]
        );

        $this->fixture->register([$providerA, $providerB]);

        $actualA = $this->fixture->get($nameA);
        $actualB = $this->fixture->get($nameB);

        self::assertSame($objectC, $actualA);
        self::assertSame($objectD, $actualB);
    }
}
