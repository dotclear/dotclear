<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
if (!defined('DC_RC_PATH')) {
    return;
}

/**
 * Utility class for admin context.
 */
class dcAdmin
{
    // User-defined - experimental (may be changed in near future)
    protected $properties = [];

    // Constants

    // Menu sections
    public const MENU_FAVORITES = 'Favorites';
    public const MENU_BLOG      = 'Blog';
    public const MENU_SYSTEM    = 'System';
    public const MENU_PLUGINS   = 'Plugins';

    /**
     * Constructs a new instance.
     *
     * @throws     Exception  (if not admin context)
     */
    public function __construct()
    {
        if (!defined('DC_CONTEXT_ADMIN')) {
            throw new Exception('Application is not in administrative context.', 500);
        }
    }

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
        return array_key_exists($identifier, $this->properties) ? $this->properties[$identifier] : null;
    }

    /**
     * Test if a property exists
     *
     * @param      string  $identifier  The identifier
     *
     * @return     bool
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
