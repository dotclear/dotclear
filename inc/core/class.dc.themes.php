<?php
/**
 * @brief Themes specific handler
 *
 * Provides an specialized object to handle themes. An instance of this
 * class should be created when needed.
 *
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

if (!defined('DC_RC_PATH')) {return;}

class dcThemes extends dcModules
{
    protected static $type = 'theme';

    /**
    This method registers a theme in modules list. You should use this to
    register a new theme.

    <var>$parent</var> is a optional value to indicate them inheritance.
    If <var>$parent</var> is null / not set, we simply fall back to
    the standard behavior, by using 'default'.

    <var>$priority</var> is an integer. Modules are sorted by priority and name.
    Lowest priority comes first. This property is currently ignored when dealing
    with themes.

    @param    name            <b>string</b>        Module name
    @param    desc            <b>string</b>        Module description
    @param    author        <b>string</b>        Module author name
    @param    version        <b>string</b>        Module version
    @param    properties    <b>array</b>        extra properties
    (currently available keys : parent, priority, standalone_config, type, tplset)
     */
    public function registerModule($name, $desc, $author, $version, $properties = [])
    {
        # Fallback to legacy registerModule parameters
        if (!is_array($properties)) {
            $args       = func_get_args();
            $properties = [];
            if (isset($args[4])) {
                $properties['parent'] = $args[4];
            }
            if (isset($args[5])) {
                $properties['priority'] = (integer) $args[5];
            }
        }
        # Themes specifics properties
        $properties = array_merge(
            ['parent' => null, 'tplset' => DC_DEFAULT_TPLSET],
            $properties,
            ['permissions' => 'admin']// force themes perms
        );

        parent::registerModule($name, $desc, $author, $version, $properties);
    }

    public function cloneModule($id, $new_name, $new_dir, $overwrite = false)
    {
        $root = end($this->path); // Use last folder set in folders list (should be only one for theme)
        if (!is_dir($root) || !is_readable($root)) {
            throw new Exception(__('Themes folder unreachable'));
        }
        if (substr($root, -1) != '/') {
            $root .= '/';
        }
        if (($d = @dir($root)) === false) {
            throw new Exception(__('Themes folder unreadable'));
        }

        if ($overwrite && is_dir($root . $new_dir)) {
            // Remove existing folder
            try {
                files::deltree($root . $new_dir);
            } catch (Exception $e) {
                throw new Exception($e->getMessage());
            }
        }

        if (!is_dir($root . $new_dir)) {
            try {
                // Create destination folder named $new_dir in themes folder
                files::makeDir($root . $new_dir, false);
                // Copy files
                $content = files::getDirList($this->modules[$id]['root']);
                foreach ($content['dirs'] as $dir) {
                    $rel = substr($dir, strlen($this->modules[$id]['root']));
                    if ($rel !== '') {
                        files::makeDir($root . $new_dir . $rel);
                    }
                }
                foreach ($content['files'] as $file) {
                    $rel = substr($file, strlen($this->modules[$id]['root']));
                    copy($file, $root . $new_dir . $rel);
                    if ($rel === '/_define.php') {
                        $buf = file_get_contents($root . $new_dir . $rel);
                        // Find offset of registerModule function call
                        $pos = strpos($buf, '$this->registerModule');
                        // Change theme name to $new_name in _define.php
                        if (preg_match('/(\$this->registerModule\(\s*)((\s*|.*)+?)(\s*\);+)/m', $buf, $matches)) {
                            $matches[2] = preg_replace('/' . $this->modules[$id]['name'] . '/', $new_name, $matches[2], 1);
                            $buf = substr($buf, 0, $pos) . $matches[1] . $matches[2] . $matches[4];
                            $buf .= sprintf("\n\n// Cloned on %s from %s theme.\n", date('c'), $this->modules[$id]['name']);
                            file_put_contents($root . $new_dir . $rel, $buf);
                        } else {
                            throw new Exception(__('Unable to modify _config.php'));
                        }
                    }
                    if (substr($rel, -4) === '.php') {
                        // Change namespace in *.php
                        // ex: namespace themes\berlin; → namespace themes\berlinClone;
                        $buf = file_get_contents($root . $new_dir . $rel);
                        if (preg_match('/^namespace\s*themes\\\([^;].*);$/m', $buf, $matches)) {
                            // $matches[0] = full line
                            $pos = strpos($buf, $matches[0]);
                            $ns = preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(['-', '.'], '', ucwords($new_dir, '_-.')));
                            $buf =
                                substr($buf, 0, $pos) .
                                'namespace themes\\' . $ns . ';' .
                                substr($buf, $pos + strlen($matches[0]));
                            file_put_contents($root . $new_dir . $rel, $buf);
                        }
                    }
                }
            } catch (Exception $e) {
                files::deltree($root . $new_dir);
                throw new Exception($e->getMessage());
            }
        } else {
            throw new Exception(__('Destination folder already exist'));
        }
    }

    /**
    Loads namespace <var>$ns</var> specific file for module with ID
    <var>$id</var>
    Note : actually, only 'public' namespace is supported with themes.

    @param    id        <b>string</b>        Module ID
    @param    ns        <b>string</b>        Namespace name
     */
    public function loadNsFile($id, $ns = null)
    {
        switch ($ns) {
            case 'public':
                $parent = $this->modules[$id]['parent'];
                if ($parent) {
                    // This is not a real cascade - since we don't call loadNsFile -,
                    // thus limiting inclusion process.
                    // TODO : See if we have to change this.
                    $this->loadModuleFile($this->modules[$parent]['root'] . '/_public.php');
                }
                $this->loadModuleFile($this->modules[$id]['root'] . '/_public.php');
                break;
        }
    }
}
