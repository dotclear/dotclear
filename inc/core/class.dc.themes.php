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
if (!defined('DC_RC_PATH')) {
    return;
}

class dcThemes extends dcModules
{
    protected static $type = 'theme';

    /**
     * This method registers a theme in modules list. You should use this to
     * register a new theme.
     *
     * <var>$parent</var> is a optional value to indicate them inheritance.
     * If <var>$parent</var> is null / not set, we simply fall back to
     * the standard behavior, by using 'default'.
     *
     * <var>$priority</var> is an integer. Modules are sorted by priority and name.
     * Lowest priority comes first. This property is currently ignored when dealing
     * with themes.
     *
     * @param      string  $name        The name
     * @param      string  $desc        The description
     * @param      string  $author      The author
     * @param      string  $version     The version
     * @param      array   $properties  The properties
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

    public function cloneModule($id)
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

        $counter = 0;
        $new_dir = sprintf('%s-copy', $this->modules[$id]['root']);
        while (is_dir($new_dir)) {
            $new_dir = sprintf('%s-copy-%s', $this->modules[$id]['root'], ++$counter);
        }
        $new_name = $this->modules[$id]['name'] . ($counter ? sprintf(__(' (copy #%s)'), $counter) : __(' (copy)'));

        if (!is_dir($new_dir)) {
            try {
                // Create destination folder named $new_dir in themes folder
                files::makeDir($new_dir, false);
                // Copy files
                $content = files::getDirList($this->modules[$id]['root']);
                foreach ($content['dirs'] as $dir) {
                    $rel = substr($dir, strlen($this->modules[$id]['root']));
                    if ($rel !== '') {
                        files::makeDir($new_dir . $rel);
                    }
                }
                foreach ($content['files'] as $file) {
                    $rel = substr($file, strlen($this->modules[$id]['root']));
                    copy($file, $new_dir . $rel);
                    if ($rel === '/_define.php') {
                        $buf = file_get_contents($new_dir . $rel);
                        // Find offset of registerModule function call
                        $pos = strpos($buf, '$this->registerModule');
                        // Change theme name to $new_name in _define.php
                        if (preg_match('/(\$this->registerModule\(\s*)((\s*|.*)+?)(\s*\);+)/m', $buf, $matches)) {
                            // Change only first occurence in registerModule parameters (should be the theme name)
                            $matches[2] = preg_replace('/' . preg_quote($this->modules[$id]['name']) . '/', $new_name, $matches[2], 1);
                            $buf        = substr($buf, 0, $pos) . $matches[1] . $matches[2] . $matches[4];
                            $buf .= sprintf("\n\n// Cloned on %s from %s theme.\n", date('c'), $this->modules[$id]['name']);
                            file_put_contents($new_dir . $rel, $buf);
                        } else {
                            throw new Exception(__('Unable to modify _config.php'));
                        }
                    }
                    if (substr($rel, -4) === '.php') {
                        // Change namespace in *.php
                        // ex: namespace themes\berlin; → namespace themes\berlinClone;
                        $buf = file_get_contents($new_dir . $rel);
                        if (preg_match('/^namespace\s*themes\\\([^;].*);$/m', $buf, $matches)) {
                            $pos     = strpos($buf, $matches[0]);
                            $rel_dir = substr($new_dir, strlen($root));
                            $ns      = preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(['-', '.'], '', ucwords($rel_dir, '_-.')));
                            $buf     = substr($buf, 0, $pos) .
                                'namespace themes\\' . $ns . ';' .
                                substr($buf, $pos + strlen($matches[0]));
                            file_put_contents($new_dir . $rel, $buf);
                        }
                    }
                }
            } catch (Exception $e) {
                files::deltree($new_dir);

                throw new Exception($e->getMessage());
            }
        } else {
            throw new Exception(__('Destination folder already exist'));
        }
    }

    /**
     * Loads namespace <var>$ns</var> specific file for module with ID <var>$id</var>
     * Note: currently, only 'public' namespace is supported with themes.
     *
     * @param      string  $id     Module ID
     * @param      string  $ns     Namespace name
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
