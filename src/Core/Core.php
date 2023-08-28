<?php

namespace Dotclear\Core;

use dcBlog;
use Exception;

final class Core
{
    /** @var Core   Core unique instance */
    private static Core $instance;

    /** @var array<string,mixed> Unique instances stack */
    private array $stack = [];

    /** @var    null|dcBlog     dcBlog instance */
    public ?dcBlog $blog;

    /**
     * Constructor.
     *
     * @param   string  $factory_class  The Core factory class name
     */
    public function __construct(
        protected string $factory_class
    ) {
        // Singleton mode
        if (isset(self::$instance)) {
            throw new Exception('Application can not be started twice.', 500);
        }
        // Factory class, implement all methods of Core, 
        // third party Core factory MUST implements CoreFactoryInterface and SHOULD extends CoreFactory
        if (!class_exists($this->factory_class) || !is_subclass_of($this->factory_class, CoreFactoryInterface::class)) {
            throw new Exception('Core factory class ' . $this->factory_class . ' does not inherit CoreFactoryInterface.');
        }
        self::$instance = $this;
    }

    /// @name Container methods
    //@{
    /**
     * Get Core unique instance
     *
     * @return  Core
     */
    public static function app(): Core
    {
        return self::$instance;
    }

    /**
     * Static alias of self::get().
     *
     * @param   string  $id The object ID.
     */
    public static function from(string $id)
    {
        return self::$instance->get($id);
    }

    /**
     * Magic alias of self::get().
     *
     * @param   string  $id The object ID.
     */
    public function __get(string $id)
    {
        return $this->get($id);
    }

    /**
     * Get unique instance of a core object.
     *
     * @param   string  $id The object ID.
     */
    public function get(string $id)
    {
        if ($this->has($id)) {
            return $this->stack[$id] ?? $this->stack[$id] = (new $this->factory_class($this))->{$id}();
        }

        throw new Exception('Can not call ' . $id . ' on Core factory class ' . $this->factory_class);
    }

    public function has(string $id): bool
    {
        return method_exists($this->factory_class, $id);
    }
    //@}

    /// @name Blog init methods
    //@{
    /**
     * Sets the blog to use.
     *
     * @param      string  $id     The blog ID
     */
    public function setBlog($id): void
    {
        $this->blog = new dcBlog($id);
    }

    /**
     * Unsets blog property
     */
    public function unsetBlog(): void
    {
        $this->blog = null;
    }
    //@}
}