<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

/**
 * @namespace   Dotclear.Helper.Container
 * @brief       Helpers for container handling
 */

namespace Dotclear\Helper\Container;

/**
 * @brief   The container helper.
 *
 * @since   2.28
 */
class Container implements ContainerInterface
{
    /**
     * Container ID.
     *
     * @var     string  CONTAINER_ID
     */
    public const CONTAINER_ID = 'undefined';

    /**
     * Stack of loaded services.
     *
     * @var    array<string,mixed>  $services
     */
    protected array $services = [];

    /**
     * Constructor gets container services.
     *
     * @throws  ContainerExceptionInterface
     *
     * @param   Factory     $factory    The factory (third party services)
     */
    public function __construct(
        protected Factory $factory
    ) {
        if ($this->factory->id !== static::CONTAINER_ID) {
            throw new ContainerException('Container is loaded with wrong factory.', 500);
        }

        // Add default services
        foreach ($this->getDefaultServices() as $service => $callback) {
            /* @phpstan-ignore-next-line */
            if (is_string($service) && (is_string($callback) || is_callable($callback))) {
                $this->factory->set($service, $callback);
            }
        }
    }

    /**
     * Get instance of a service.
     *
     * By default, an object is instanciated once.
     *
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     *
     * @param   string  $id         The object ID
     * @param   bool    $reload     Force reload of the class
     * @param   mixed   ...$args    The method arguments
     */
    public function get(string $id, bool $reload = false, ...$args)
    {
        // Service is already instanciated
        if (!$reload && array_key_exists($id, $this->services)) {
            return $this->services[$id];
        }

        // Know service
        if ($this->has($id)) {
            $service = $this->factory->get($id);

            return is_callable($service) ?
                // callable service
                $this->services[$id] = $service($this, ...$args) :
                // alias service, resolve alias and parse know container arguments
                $this->services[$id] = $this->resolve($service, $args);
        }

        // Unknow service
        throw new NotFoundException('Call to undefined factory service ' . $id);
    }

    public function has(string $id): bool
    {
        return $this->factory->has($id);
    }

    /**
     * Resolve class arguments.
     *
     * Retrieves and adds container services from class constructor arguments.
     *
     * @param   string  $alias  The alias
     * @param   mixed   $args   The arguments
     *
     * @throws  ContainerException
     *
     * @return  mixed
     */
    private function resolve(string $alias, $args)
    {
        try {
            $reflector = new \ReflectionClass($alias);  // @phpstan-ignore-line
        } catch (\ReflectionException $e) {
            throw new ContainerException(
                $e->getMessage(),
                $e->getCode()
            );
        }
        if (!$reflector->isInstantiable()) {
            throw new ContainerException('Call to undefined factory service ' . $alias . ' argument ' . $reflector->getName());
        }

        $constructor = $reflector->getConstructor();
        if (null === $constructor) {
            return $reflector->newInstance();
        }

        $params = $constructor->getParameters();
        foreach ($params as $parameter) {
            $type = $parameter->getType();
            if ($type instanceof \ReflectionUnionType) { //php 8.0+
                $type = $type->getTypes()[0];
            }
            $class = !$type || $type->isBuiltin() || !$this->factory->has($type->getName()) ? null : new \ReflectionClass($type->getName());

            if (null !== $class) {
                $args[$parameter->name] = $this->get(
                    $class->getName()
                );
            } elseif (isset($args[$parameter->name])) {
                // keep given arguments as is
            } elseif ($parameter->isDefaultValueAvailable()) {
                $args[$parameter->name] = $parameter->getDefaultValue();
            }
        }

        return $reflector->newInstanceArgs($args);
    }

    /**
     * Get default services definitions.
     *
     * Return array of service ID / service callback pairs.
     *
     * @return  array<string,callable>  The default services
     */
    protected function getDefaultServices(): array
    {
        return [];
    }
}
