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
    protected $id;
    protected $name;
    protected $options;

    /**
     * Constructs a new instance.
     *
     * @param      string  $id       The identifier
     * @param      string  $name     The name
     * @param      array   $options  The options
     */
    public function __construct($id, $name, $options = [])
    {
        $this->id      = (string) $id;
        $this->name    = (string) $name;
        $this->options = (array) $options;
    }

    /**
     * Get ID.
     *
     * @return string    ID
     */
    public function id()
    {
        return $this->id;
    }

    /**
     * Get name.
     *
     * @return string    Name
     */
    public function name()
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
     * @return     string  Option value
     */
    public function option($key)
    {
        return $this->options[$key] ?? null;
    }

    /* @ignore */
    public function __get($key)
    {
        return $this->option($key);
    }

    /* @ignore */
    public function __isset($key)
    {
        return isset($this->options[$key]);
    }
}
