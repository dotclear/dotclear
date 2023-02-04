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
    protected $type = 'plugin';

    /**
     * This method registers a plugin in modules list.
     *
     * <var>$permissions</var> is a comma separated list of permissions for your
     * module. If <var>$permissions</var> is null, only super admin has access to
     * this module.
     *
     * <var>$priority</var> is an integer. Modules are sorted by priority and name.
     * Lowest priority comes first.
     *
     * @param      string  $name        The module name
     * @param      string  $desc        The module description
     * @param      string  $author      The module author
     * @param      string  $version     The module version
     * @param      mixed   $properties  The properties
     */
    public function registerModule(string $name, string $desc, string $author, string $version, $properties = [])
    {
        $define = new dcModuleDefine($this->id);

        $define
            ->set('name', $name)
            ->set('desc', $desc)
            ->set('author', $author)
            ->set('version', $version)
        ;

        if (is_array($properties)) {
            foreach($properties as $k => $v) {
                $define->set($k, $v);
            }
        }

        // Fallback to legacy registerModule parameters
        if (!is_array($properties)) {
            $args       = func_get_args();
            $properties = [];
            if (isset($args[4])) {
                $define->set('permissions', $args[4]);
            }
            if (isset($args[5])) {
                $define->set('priority', (int) $args[5]);
            }
        }

        $this->defineModule($define);
    }
}
