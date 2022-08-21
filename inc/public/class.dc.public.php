<?php
/**
 * @package Dotclear
 * @subpackage Frontend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
if (!defined('DC_RC_PATH')) {
    return;
}

/**
 * Utility class for public context.
 */
class dcPublic
{
    public $search;         // Searched term
    public $search_count;   // Search count

    protected $page_number; // Current page number

    // User-defined - experimental (may be changed in near future)
    protected $properties = [];

    /**
     * Constructs a new instance.
     *
     * @throws     Exception (if not public context)
     */
    public function __construct()
    {
        if (defined('DC_CONTEXT_ADMIN')) {
            throw new Exception('Application is not in public context.', 500);
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

    /**
     * Sets the page number.
     *
     * @param      int  $value  The value
     */
    public function setPageNumber(int $value)
    {
        $this->page_number = $value;

        // Obsolete since 2.24, may be removed in near future
        $GLOBALS['_page_number'] = $value;
    }

    /**
     * Gets the page number.
     *
     * @return     int   The page number.
     */
    public function getPageNumber(): int
    {
        return (int) $this->page_number;
    }
}
