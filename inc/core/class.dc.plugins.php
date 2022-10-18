<?php
/**
 * @brief Plugins specific handler
 *
 * An instance of this class is provided by dcCore $plugins property
 * and used for plugins.
 *
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 *
 * @since 2.6
 */
class dcPlugins extends dcModules
{
    protected static $type = 'plugin';

    /**
     * Should run in safe mode?
     *
     * @return     bool
     */
    public function safeMode(): bool
    {
        // We load all plugins only in standard mode
        return $this->safe_mode;
    }

    /**
     *  This method registers a plugin in modules list. You should use this to
     *  register a new plugin.
     *
     * <var>$priority</var> is an integer. Modules are sorted by priority and name.
     * Lowest priority comes first. This property is currently ignored when dealing
     * with themes.
     *
     * @param      string  $name        The name
     * @param      string  $desc        The description
     * @param      string  $author      The author
     * @param      string  $version     The version
     * @param      mixed   $properties  The properties
     */
    public function registerModule(string $name, string $desc, string $author, string $version, $properties = []): void
    {
        // Fallback to legacy registerModule parameters
        if (!is_array($properties)) {
            $args       = func_get_args();
            $properties = [];
            if (isset($args[4])) {
                $properties['permissions'] = $args[4];
            }
            if (isset($args[5])) {
                $properties['priority'] = (int) $args[5];
            }
        }

        parent::registerModule($name, $desc, $author, $version, $properties);
    }
}
