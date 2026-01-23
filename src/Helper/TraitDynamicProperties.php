<?php

/**
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper;

/**
 * @brief Dotclear dynamic properties management trait.
 *
 * Dotclear trait to add dynamic properties management to a class.
 */
trait TraitDynamicProperties
{
    // User-defined - experimental (may be changed in future)

    /**
     * User-defined properties
     *
     * @var        array<string, mixed>
     */
    protected array $properties = [];

    /**
     * Magic function, store a property and its value
     *
     * @param      string  $identifier  The identifier
     * @param      mixed   $value       The value
     */
    public function __set(string $identifier, $value = null)
    {
        $this->properties[$identifier] = $value;
    }

    /**
     * Gets the specified property value (null if does not exist).
     *
     * @param      string  $identifier  The identifier
     *
     * @return     mixed
     */
    public function __get(string $identifier)
    {
        return $this->properties[$identifier] ?? null;
    }

    /**
     * Test if a property exists
     *
     * @param      string  $identifier  The identifier
     */
    public function __isset(string $identifier): bool
    {
        return isset($this->properties[$identifier]);
    }

    /**
     * Unset a property
     *
     * @param      string  $identifier  The identifier
     */
    public function __unset(string $identifier)
    {
        if (array_key_exists($identifier, $this->properties)) {
            unset($this->properties[$identifier]);
        }
    }
}
