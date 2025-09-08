<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
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
            if (is_callable($callback) || class_exists($callback)) {
                $this->factory->set((string) $service, $callback, false);
            }
        }
    }

    /**
     * Get instance of a service.
     *
     * By default, an object is instanciated once.
     *
     * @throws NotFoundExceptionInterface
     *
     * @param   string      $id         The object ID
     * @param   ?bool       $reload     Force reload the class if true, do not if false, load once and forget if null
     * @param   mixed       ...$args    The method arguments
     */
    public function get(string $id, ?bool $reload = false, ...$args)
    {
        $this->factory->increment($id); // staticstics

        // Service is already instanciated
        if ($reload === false && array_key_exists($id, $this->services)) {
            return $this->services[$id];
        }

        // Know service
        if ($this->has($id)) {
            $service = $this->factory->get($id);

            $resolve = is_callable($service) ?
                // callable service
                $service($this, ...$args) :
                // alias service, resolve alias and parse know container arguments
                $this->resolve((string) $service, $args);

            if ($reload !== null) {
                // Keep this service for further instance get
                $this->services[$id] = $resolve;
            }

            return $resolve;
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
     * @param   string                      $alias  The alias
     * @param   array<int|string, mixed>    $args   The arguments
     *
     * @throws  ContainerException
     */
    private function resolve(string $alias, array $args): object
    {
        if (!class_exists($alias)) {
            // Class does not exist
            throw new ContainerException('Call to undefined factory service ' . $alias);
        }
        $reflector = new \ReflectionClass($alias);

        if (!$reflector->isInstantiable()) {
            // Class is not instantiable
            throw new ContainerException('Call to undefined factory service ' . $alias . ' argument ' . $reflector->getName());
        }

        $constructor = $reflector->getConstructor();
        if (null === $constructor) {
            // Class has no constructor
            return $reflector->newInstance();
        }

        // Check class parameters (that could be container class)
        $params = $constructor->getParameters();
        foreach ($params as $parameter) {
            $class = null;
            $type  = $parameter->getType();
            if ($type instanceof \ReflectionUnionType) {
                // Get first level class of extended class
                $type = $type->getTypes()[0];
            }
            if ($type instanceof \ReflectionNamedType
                && !$type->isBuiltin()
                && ($this->factory->has($type->getName()) || is_subclass_of($type->getName(), self::class))
            ) {
                // Get class name
                $class = $type->getName();
            }

            if ($type instanceof \ReflectionNamedType && is_subclass_of($type->getName(), self::class)) {
                // Get self class
                $args[$parameter->name] = $this;
            } elseif (null !== $class) {
                // Get container class
                $args[$parameter->name] = $this->get($class);
            } elseif (isset($args[$parameter->name])) {
                // Keep given argument as is
            } elseif ($parameter->isDefaultValueAvailable()) {
                // Get default argument value
                $args[$parameter->name] = $parameter->getDefaultValue();
            }
        }

        return $reflector->newInstanceArgs($args);
    }

    public function dump(): array
    {
        return $this->factory->dump();
    }

    /**
     * Get default services definitions.
     *
     * Return array of service ID / service callback pairs.
     *
     * @return  array<string,string|callable>  The default services
     */
    protected function getDefaultServices(): array
    {
        return [];
    }
}
