<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\maintenance;

/**
 * @brief   The maintenance descriptor.
 * @ingroup maintenance
 *
 * Simple descriptor for tabs, groups and more.
 * At this time this class is used in same way an arrayObject but in futur it could be completed with advance methods.
 */
class MaintenanceDescriptor
{
    /**
     * Construct a new instance.
     *
     * @param   string                  $id         The identifier
     * @param   string                  $name       The name
     * @param   array<string, string>   $options    The options
     */
    public function __construct(
        protected string $id,
        protected string $name,
        protected array $options = []
    ) {
    }

    /**
     * Get ID.
     *
     * @return  string  ID
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * Get name.
     *
     * @return  string  Name
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Get option.
     *
     * Option called "summary" and "description" are used.
     *
     * @param   string  $key    Option key
     *
     * @return  null|string     Option value
     */
    public function option(string $key): ?string
    {
        return $this->options[$key] ?? null;
    }

    /**
     * Get the specified key.
     *
     * @param   string  $key    The key
     *
     * @return  null|string  Option value
     */
    public function __get(string $key): ?string
    {
        return $this->option($key);
    }

    /**
     * Test if an option exists.
     *
     * @param   string  $key    The key
     */
    public function __isset(string $key): bool
    {
        return isset($this->options[$key]);
    }
}
