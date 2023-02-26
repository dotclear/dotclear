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
class dcThemes extends dcModules
{
    /**
     * Module type
     *
     * @var        string
     */
    protected $type = 'theme';

    /**
     * This method registers a theme in modules list.
     *
     * <var>$parent</var> is a optional value to indicate them inheritance.
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
        $define = new dcModuleDefine($this->id);

        $define
            ->set('name', $name)
            ->set('desc', $desc)
            ->set('author', $author)
            ->set('version', $version)
        ;

        if (is_array($properties)) {
            foreach ($properties as $k => $v) {
                $define->set($k, $v);
            }
        }

        // Fallback to legacy registerModule parameters
        if (!is_array($properties)) {
            $args       = func_get_args();
            $properties = [];
            if (isset($args[4])) {
                $define->set('parent', $args[4]);
            }
            if (isset($args[5])) {
                $define->set('priority', (int) $args[5]);
            }
        }

        $this->defineModule($define);
    }

    protected function defineModule(dcModuleDefine $define)
    {
        // Themes specifics properties
        $define->set('permissions', dcCore::app()->auth->makePermissions([
            dcAuth::PERMISSION_ADMIN,
        ]));

        parent::defineModule($define);
    }

    /**
     * Clone a theme module
     *
     * @param      string     $id     The identifier
     *
     * @throws     Exception
     */
    public function cloneModule(string $id): void
    {
        $module = $this->getDefine($id);

        $root = end($this->path); // Use last folder set in folders list (should be only one for theme)
        if (!is_dir($root) || !is_readable($root)) {
            throw new Exception(__('Themes folder unreachable'));
        }
        $root = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if ((@dir($root)) === false) {
            throw new Exception(__('Themes folder unreadable'));
        }

        $counter = 0;
        $new_dir = sprintf('%s_copy', $module->root);
        while (is_dir($new_dir)) {
            $new_dir = sprintf('%s_copy_%s', $module->root, ++$counter);
        }
        $new_name = $module->name . ($counter ? sprintf(__(' (copy #%s)'), $counter) : __(' (copy)'));

        if (!is_dir($new_dir)) {
            try {
                // Create destination folder named $new_dir in themes folder
                files::makeDir($new_dir, false);

                // Clone directories and files

                $content = files::getDirList($module->root);

                // Create sub directories if necessary
                foreach ($content['dirs'] as $dir) {
                    $rel = substr($dir, strlen($module->root));
                    if ($rel !== '') {
                        files::makeDir($new_dir . $rel);
                    }
                }

                // Copy files from source to destination
                foreach ($content['files'] as $file) {
                    // Copy file
                    $rel = substr($file, strlen($module->root));
                    copy($file, $new_dir . $rel);

                    if ($rel === (DIRECTORY_SEPARATOR . self::MODULE_FILE_DEFINE)) {
                        $buf = (string) file_get_contents($new_dir . $rel);
                        // Find offset of registerModule function call
                        $pos = strpos($buf, '$this->registerModule');
                        // Change theme name to $new_name in _define.php
                        if (preg_match('/(\$this->registerModule\(\s*)((\s*|.*)+?)(\s*\);+)/m', $buf, $matches)) {
                            // Change only first occurence in registerModule parameters (should be the theme name)
                            $matches[2] = preg_replace('/' . preg_quote($module->name) . '/', $new_name, $matches[2], 1);
                            $buf        = substr($buf, 0, $pos) . $matches[1] . $matches[2] . $matches[4];
                            $buf .= sprintf("\n\n// Cloned on %s from %s theme.\n", date('c'), $module->name);
                            file_put_contents($new_dir . $rel, $buf);
                        } else {
                            throw new Exception(__('Unable to modify _define.php'));
                        }
                    }

                    if (substr($rel, -4) === '.php') {
                        // Change namespace in *.php
                        $buf      = (string) file_get_contents($new_dir . $rel);
                        $prefixes = [
                            'themes\\',             // ex: namespace themes\berlin; → namespace themes\berlin_Copy; Dotclear <= 2.24
                            'Dotclear\Theme\\',     // ex: namespace Dotclear\Theme\Berlin; → namespace Dotclear\Theme\Berlin_Copy;
                        ];
                        foreach ($prefixes as $prefix) {
                            if (preg_match('/^namespace\s*' . preg_quote($prefix) . '([^;].*);$/m', $buf, $matches)) {
                                $pos     = strpos($buf, $matches[0]);
                                $rel_dir = substr($new_dir, strlen($root));
                                $ns      = preg_replace('/\W/', '', str_replace(['-', '.'], '', ucwords($rel_dir, '_-.')));
                                $buf     = substr($buf, 0, $pos) .
                                    'namespace ' . $prefix . $ns . ';' .
                                    substr($buf, $pos + strlen($matches[0]));
                                file_put_contents($new_dir . $rel, $buf);

                                break;
                            }
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
    public function loadNsFile(string $id, ?string $ns = null): void
    {
        $define = $this->getDefine($id, ['state' => dcModuleDefine::STATE_ENABLED]);
        if (!$define->isDefined()) {
            return;
        }

        switch ($ns) {
            case 'public':
                $parent = $this->getDefine($id, ['state' => dcModuleDefine::STATE_ENABLED])->parent;
                if ($parent) {
                    // This is not a real cascade - since we don't call loadNsFile -,
                    // thus limiting inclusion process.
                    // TODO : See if we have to change this.

                    // by class name
                    if ($this->loadNsClass($parent, self::MODULE_CLASS_PUPLIC) === '') {
                        // by file name
                        $this->loadModuleFile($parent->root . DIRECTORY_SEPARATOR . self::MODULE_FILE_PUBLIC);
                    }
                }

                // by class name
                if ($this->loadNsClass($id, self::MODULE_CLASS_PUPLIC) === '') {
                    // by file name
                    $this->loadModuleFile($define->root . DIRECTORY_SEPARATOR . self::MODULE_FILE_PUBLIC);
                }

                break;
        }
    }
}
