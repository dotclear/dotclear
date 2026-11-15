<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Container;

use Dotclear\Helper\Container\Container;
use Dotclear\Helper\Container\ContainerException;
use Dotclear\Helper\Container\Factory;
use Dotclear\Helper\Container\Factories;
use Dotclear\Helper\Container\NotFoundException;
use PHPUnit\Framework\TestCase;

interface ContainerTestDependencyInterface
{
}

class ContainerTestDependency implements ContainerTestDependencyInterface
{
}

class ContainerTestTarget
{
    public function __construct(
        public readonly ContainerTestDependencyInterface $dependency,
        public readonly ContainerTestContainer $container,
        public readonly string $name = 'default',
    ) {
    }
}

class ContainerTestUnionTarget
{
    public function __construct(
        public readonly ContainerTestDependency|ContainerTestUnionDependency $dependency,
    ) {
    }
}

class ContainerTestUnionDependency
{
}

class ContainerTestNoConstructor
{
}

abstract class ContainerTestNotInstantiable
{
}

class ContainerTestContainer extends Container
{
    public const CONTAINER_ID        = 'unit_container_test';
    public const CONTAINER_SINGLETON = false;
}

class ContainerTestDefaultServices extends Container
{
    public const CONTAINER_ID        = 'unit_container_defaults';
    public const CONTAINER_SINGLETON = false;

    public function getDefaultServices(): array
    {
        return [
            'callback'  => static fn (): string => 'from-default',
            'class'     => ContainerTestNoConstructor::class,
            'invalid'   => 'ContainerTestMissingClass',
            'preserved' => static fn (): string => 'from-default',
        ];
    }
}

class ContainerTestSingleton extends Container
{
    public const CONTAINER_ID = 'unit_container_singleton';
}

class ContainerTestServicesContainer extends Container
{
    public const CONTAINER_ID        = 'service';
    public const CONTAINER_SINGLETON = false;
}

class ContainerTest extends TestCase
{
    public function testFactoryAndFactories(): void
    {
        $id       = 'unit_factory_' . bin2hex(random_bytes(6));
        $factory  = new Factory($id, false);
        $callback = static fn (): string => 'first';

        $factory->set('service', ContainerTestNoConstructor::class);
        $factory->set('service', $callback);

        $this->assertSame(ContainerTestNoConstructor::class, $factory->get('service'));
        $this->assertNull($factory->get('missing'));
        $this->assertFalse($factory->has('missing'));
        $this->assertSame(['service' => ContainerTestNoConstructor::class], $factory->dump());
        $this->assertSame(
            ContainerTestNoConstructor::class,
            Factory::getStatistics()['factory'][$id]['service']
        );

        Factories::addContainer($id);
        $this->assertTrue(Factories::hasContainer($id));
        $this->assertFalse(Factories::hasService($id, 'service'));
        Factories::addContainer($id, true);
        Factories::addService($id, 'service', $callback);
        $this->assertTrue(Factories::hasService($id, 'service'));
        $this->assertSame($callback, Factories::getFactory($id)->get('service'));
        $this->assertFalse(Factories::hasService($id . '_absent', 'service'));

        $overwritableId = $id . '_overwritable';
        Factories::addContainer($overwritableId, true);
        Factories::addService($overwritableId, 'service', ContainerTestNoConstructor::class);
        Factories::addService($overwritableId, 'service', $callback);
        $this->assertSame($callback, Factories::getFactory($overwritableId)->get('service'));

        $missingId = $id . '_missing';
        $missingFactory = Factories::getFactory($missingId);
        $this->assertSame($missingId, $missingFactory->id);
        $this->assertFalse(Factories::hasContainer($missingId));
        $this->assertSame('closure', Factory::getStatistics()['factory'][$overwritableId]['service']);
    }

    public function testContainerServiceResolutionAndCaching(): void
    {
        $factory = new Factory(ContainerTestContainer::CONTAINER_ID);
        $factory->set(ContainerTestDependencyInterface::class, ContainerTestDependency::class);
        $factory->set(ContainerTestDependency::class, ContainerTestDependency::class);
        $factory->set(ContainerTestTarget::class, ContainerTestTarget::class);
        $factory->set(ContainerTestUnionTarget::class, ContainerTestUnionTarget::class);
        $factory->set(ContainerTestUnionDependency::class, ContainerTestUnionDependency::class);
        $calls = 0;
        $factory->set('callback', static function () use (&$calls): object {
            ++$calls;

            return new \stdClass();
        });

        $container = new ContainerTestContainer($factory);
        $loadsBefore = ContainerTestContainer::getLoadsCount();
        $requestsBefore = ContainerTestContainer::getRequestsCount();

        $target = $container->get(ContainerTestTarget::class);
        $this->assertInstanceOf(ContainerTestTarget::class, $target);
        $this->assertInstanceOf(ContainerTestDependency::class, $target->dependency);
        $this->assertSame($container, $target->container);
        $this->assertSame('default', $target->name);
        $this->assertSame($target, $container->get(ContainerTestTarget::class));

        $withArgument = $container->get(ContainerTestTarget::class, true, name: 'provided');
        $this->assertSame('provided', $withArgument->name);
        $union = $container->get(ContainerTestUnionTarget::class);
        $this->assertInstanceOf(ContainerTestDependency::class, $union->dependency);

        $first = $container->get('callback');
        $this->assertSame($first, $container->get('callback'));
        $this->assertSame(1, $calls);
        $reloaded = $container->get('callback', true);
        $this->assertNotSame($first, $reloaded);
        $this->assertSame($reloaded, $container->get('callback'));
        $this->assertSame(2, $calls);
        $temporary = $container->get('callback', null);
        $this->assertNotSame($reloaded, $temporary);
        $this->assertSame($reloaded, $container->get('callback'));
        $this->assertSame(3, $calls);

        $this->assertTrue($container->has('callback'));
        $this->assertFalse($container->has('missing'));
        $this->assertSame($factory->dump(), $container->dump());
        $this->assertSame($loadsBefore + 8, ContainerTestContainer::getLoadsCount());
        $this->assertSame($requestsBefore + 5, ContainerTestContainer::getRequestsCount());
    }

    public function testDefaultServicesAndInvalidServices(): void
    {
        $factory = new Factory(ContainerTestDefaultServices::CONTAINER_ID, false);
        $factory->set('preserved', static fn (): string => 'from-factory');

        $container = new ContainerTestDefaultServices($factory);

        $this->assertTrue($container->has('callback'));
        $this->assertTrue($container->has('class'));
        $this->assertFalse($container->has('invalid'));
        $this->assertSame('from-default', $container->get('callback'));
        $this->assertInstanceOf(ContainerTestNoConstructor::class, $container->get('class'));
        $this->assertSame('from-factory', $container->get('preserved'));
    }

    public function testServiceRequestStatistics(): void
    {
        $service = 'unit-statistics-' . bin2hex(random_bytes(6));
        $factory = new Factory(ContainerTestServicesContainer::CONTAINER_ID);
        $factory->set($service, static fn (): object => new \stdClass());
        $container = new ContainerTestServicesContainer($factory);

        $container->get($service);
        $container->get($service);

        $this->assertSame(2, ContainerTestServicesContainer::getServicesCount()[$service]);
    }

    public function testContainerErrorsAndSingletonWatchdog(): void
    {
        try {
            new ContainerTestContainer(new Factory('wrong-container-id'));
            $this->fail('Expected a factory ID mismatch to be rejected');
        } catch (ContainerException $exception) {
            $this->assertSame('Container is loaded with wrong factory.', $exception->getMessage());
        }

        $container = new ContainerTestContainer(new Factory(ContainerTestContainer::CONTAINER_ID));
        foreach ([
            'missing' => 'Call to undefined factory service missing',
        ] as $service => $message) {
            try {
                $container->get($service);
                $this->fail('Expected an undefined service to be rejected');
            } catch (NotFoundException $exception) {
                $this->assertSame($message, $exception->getMessage());
            }
        }

        $factory = new Factory(ContainerTestContainer::CONTAINER_ID);
        $factory->set('missing-class', 'ContainerTestMissingClass');
        $factory->set('abstract', ContainerTestNotInstantiable::class);
        $factory->set(ContainerTestNotInstantiable::class, ContainerTestNotInstantiable::class);
        $container = new ContainerTestContainer($factory);

        foreach ([
            'missing-class' => 'Call to undefined factory service ContainerTestMissingClass',
            'abstract'      => 'Call to undefined factory service ' . ContainerTestNotInstantiable::class . ' argument ' . ContainerTestNotInstantiable::class,
        ] as $service => $message) {
            try {
                $container->get($service);
                $this->fail('Expected an invalid service definition to be rejected');
            } catch (ContainerException $exception) {
                $this->assertSame($message, $exception->getMessage());
            }
        }

        new ContainerTestSingleton(new Factory(ContainerTestSingleton::CONTAINER_ID));
        $this->expectException(\Dotclear\Exception\ContextException::class);
        new ContainerTestSingleton(new Factory(ContainerTestSingleton::CONTAINER_ID));
    }
}
