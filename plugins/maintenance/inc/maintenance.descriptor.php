<?php
/**
 * @brief maintenance, a plugin for Dotclear 2
 *
 * @package Dotclear
 * @subpackage Plugins
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
if (!defined('DC_RC_PATH')) {
    return;
}

/**
@brief Simple descriptor for tabs, groups and more

At this time this class is used in same way an arrayObject
but in futur it could be completed with advance methods.
 */
class dcMaintenanceDescriptor
{
    /**
     * Descriptor ID
     *
     * @var string
     */
    protected $id;

    /**
     * Descriptor name
     *
     * @var string
     */
    protected $name;

    /**
     * Descriptor options
     *
     * @var array
     */
    protected $options;

    /**
     * Constructs a new instance.
     *
     * @param      string  $id       The identifier
     * @param      string  $name     The name
     * @param      array   $options  The options
     */
    public function __construct(string $id, string $name, array $options = [])
    {
        $this->id      = $id;
        $this->name    = $name;
        $this->options = $options;
    }

    /**
     * Get ID.
     *
     * @return string    ID
     */
    public function id(): string
    {
        return $this->id;
    }

    /**
     * Get name.
     *
     * @return string    Name
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
     * @param      string  $key    Option key
     *
     * @return     null|string  Option value
     */
    public function option(string $key): ?string
    {
        return $this->options[$key] ?? null;
    }

    /* @ignore */

    /**
     * Gets the specified key.
     *
     * @param      string  $key    The key
     *
     * @return     null|string  Option value
     */
    public function __get(string $key): ?string
    {
        return $this->option($key);
    }

    /**
     * Test if an option exists
     *
     * @param      string  $key    The key
     *
     * @return     bool
     */
    public function __isset(string $key): bool
    {
        return isset($this->options[$key]);
    }
}
