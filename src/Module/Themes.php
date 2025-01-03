<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Module;

use Dotclear\App;
use Dotclear\Helper\File\Files;
use Dotclear\Interface\Core\ThemesInterface;
use Exception;

/**
 * @brief   Themes specific handler.
 *
 * An instance of this class is provided by App::themes()
 * and used for themes.
 *
 * @since   2.6
 */
class Themes extends Modules implements ThemesInterface
{
    protected ?string $type = 'theme';

    protected function loadModulesContext(array $ignored, string $ns, ?string $lang): void
    {
        if ($ns === 'admin' && App::blog()->isDefined()) {
            // Load current theme Backend process (and its parent)
            $this->loadNsFile((string) App::blog()->settings()->system->theme, 'admin');
        }
    }

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
        if (!$this->id) {
            return;
        }

        $define = new ModuleDefine($this->id);

        $define
            ->set('name', $name)
            ->set('desc', $desc)
            ->set('author', $author)
            ->set('version', $version)
        ;

        if (is_array($properties)) {
            foreach ($properties as $k => $v) {
                $define->set((string) $k, $v);
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

    protected function defineModule(ModuleDefine $define): void
    {
        // Themes specifics properties
        $define->set('permissions', App::auth()->makePermissions([
            App::auth()::PERMISSION_ADMIN,
        ]));

        parent::defineModule($define);
    }

    public function cloneModule(string $id): void
    {
        $module = $this->getDefine($id);

        $root = (string) end($this->path); // Use last folder set in folders list (should be only one for theme)
        if (!is_dir($root) || !is_readable($root)) {
            throw new Exception(__('Themes folder unreachable'));
        }
        $root = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if ((@dir($root)) === false) {
            throw new Exception(__('Themes folder unreadable'));
        }

        $counter = 0;
        $new_dir = sprintf('%s_copy', $module->get('root'));
        while (is_dir($new_dir)) {
            $new_dir = sprintf('%s_copy_%s', $module->get('root'), ++$counter);
        }
        $new_name = $module->get('name') . ($counter !== 0 ? sprintf(__(' (copy #%s)'), $counter) : __(' (copy)'));

        if (!is_dir($new_dir)) {
            try {
                // Create destination folder named $new_dir in themes folder
                Files::makeDir($new_dir, false);

                // Clone directories and files

                $content = Files::getDirList($module->get('root'));

                if (is_array($content)) {
                    // Create sub directories if necessary
                    foreach ($content['dirs'] as $dir) {
                        $rel = substr($dir, strlen((string) $module->get('root')));
                        if ($rel !== '') {
                            Files::makeDir($new_dir . $rel);
                        }
                    }

                    // Copy files from source to destination
                    foreach ($content['files'] as $file) {
                        // Copy file
                        $rel = substr($file, strlen((string) $module->get('root')));
                        copy($file, $new_dir . $rel);

                        if ($rel === (DIRECTORY_SEPARATOR . self::MODULE_FILE_DEFINE)) {
                            $buf = (string) file_get_contents($new_dir . $rel);
                            // Find offset of registerModule function call
                            $pos = (int) strpos($buf, '$this->registerModule');
                            // Change theme name to $new_name in _define.php
                            if (preg_match('/(\$this->registerModule\(\s*)((\s*|.*)+?)(\s*\);+)/m', $buf, $matches)) {
                                // Change only first occurence in registerModule parameters (should be the theme name)
                                $matches[2] = preg_replace('/' . preg_quote((string) $module->get('name')) . '/', $new_name, $matches[2], 1);    // @phpstan-ignore-line
                                $buf        = substr($buf, 0, $pos) . $matches[1] . $matches[2] . $matches[4];
                                $buf .= sprintf("\n\n// Cloned on %s from %s theme.\n", date('c'), $module->get('name'));
                                file_put_contents($new_dir . $rel, $buf);
                            } else {
                                throw new Exception(__('Unable to modify _define.php'));
                            }
                        }

                        if (str_ends_with($rel, '.php')) {
                            // Change namespace in *.php
                            $buf      = (string) file_get_contents($new_dir . $rel);
                            $prefixes = [
                                'themes\\',             // ex: namespace themes\berlin; → namespace themes\berlin_Copy; Dotclear <= 2.24
                                'Dotclear\Theme\\',     // ex: namespace Dotclear\Theme\Berlin; → namespace Dotclear\Theme\Berlin_Copy;
                            ];
                            foreach ($prefixes as $prefix) {
                                if (preg_match('/^namespace\s*' . preg_quote($prefix) . '([^;].*);$/m', $buf, $matches)) {  // @phpstan-ignore-line
                                    $pos     = (int) strpos($buf, $matches[0]);
                                    $rel_dir = substr($new_dir, strlen($root));
                                    $ns      = preg_replace('/\W/', '', str_replace(['-', '.'], '', $rel_dir));
                                    $buf     = substr($buf, 0, $pos) .
                                        'namespace ' . $prefix . $ns . ';' .
                                        substr($buf, $pos + strlen($matches[0]));
                                    file_put_contents($new_dir . $rel, $buf);

                                    break;
                                }
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                Files::deltree($new_dir);

                throw new Exception($e->getMessage(), (int) $e->getCode(), $e);
            }
        } else {
            throw new Exception(__('Destination folder already exist'));
        }
    }

    /**
     * Loads namespace <var>$ns</var> specific file for module with ID <var>$id</var>.
     *
     * Note: currently, only 'public' namespace is supported with themes.
     *
     * @param      string  $id     Module ID
     * @param      string  $ns     Namespace name
     */
    public function loadNsFile(string $id, ?string $ns = null): void
    {
        $define = $this->getDefine($id, ['state' => ModuleDefine::STATE_ENABLED]);
        if (!$define->isDefined() || !in_array($ns, ['admin', 'public'])) {
            return;
        }

        $parent = $this->getDefine((string) $define->get('parent'), ['state' => ModuleDefine::STATE_ENABLED]);

        switch ($ns) {
            case 'admin':
                $class = self::MODULE_CLASS_ADMIN;
                $file  = self::MODULE_FILE_ADMIN;

                break;
            case 'public':
                $class = self::MODULE_CLASS_PUPLIC;
                $file  = self::MODULE_FILE_PUBLIC;

                break;
            default:
                return;
        }

        if ($parent->isDefined() && $this->loadNsClass($parent->getId(), $class) === '') {
            // by file name rather than by class name
            $this->loadModuleFile($parent->get('root') . DIRECTORY_SEPARATOR . $file, true);
        }

        if ($this->loadNsClass($id, $class) === '') {
            // by file name rather than by class name
            $this->loadModuleFile($define->get('root') . DIRECTORY_SEPARATOR . $file, true);
        }
    }
}
