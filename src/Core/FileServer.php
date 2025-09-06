<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Core;

use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\Network\Http;
use Dotclear\Interface\Core\FileServerInterface;

/**
 * @brief   The helper to serve file.
 *
 * This class checks request URI to find pf and vf queries and serve related file.
 * It is limited as it is loaded before dcCore to speed up requests.
 *
 * @since   2.27
 */
class FileServer implements FileServerInterface
{
    /**
     * Default cache ttl (one week).
     */
    public static int $cache_ttl = 604800;

    /**
     * Debug mode.
     */
    protected bool $debug = false;

    /**
     * The type of resource to find
     */
    protected string $type = '';

    /**
     * The resource to find.
     */
    protected string $resource = '';

    /**
     * The file extension.
     */
    protected string $extension = '';

    /**
     * The file.
     */
    protected ?string $file = null;

    public function __construct(
        protected Core $core
    ) {
        if (!empty($_GET['pf']) && is_string($_GET['pf'])) {
            $this->serve('plugin', $_GET['pf']);
        } elseif (!empty($_GET['vf']) && is_string($_GET['vf'])) {
            $this->serve('var', $_GET['vf']);
        } elseif (!empty($_GET['tf']) && is_string($_GET['tf'])) {
            $this->serve('theme', $_GET['tf']);
        }
    }

    /**
     * Search and serve file.
     *
     * @param   string  $type       The resource type
     * @param   string  $resource   The resource path
     */
    protected function serve(string $type, string $resource): void
    {
        $this->debug     = $this->core->config()->debugMode() || $this->core->config()->devMode();
        $this->type      = $type;
        $this->resource  = Path::clean($resource);
        $this->extension = Files::getExtension($this->resource);

        $this->checkEnv();

        match ($this->type) {
            'plugin' => $this->findPluginFile(),
            'theme'  => $this->findThemeFile(),
            'core'   => $this->findCoreFile(),
            'var'    => $this->findVarFile(),
            default  => '',
        };

        $this->readFile();
    }

    /**
     * Check environment requirements.
     */
    protected function checkEnv(): void
    {
        if ($this->resource === '') {
            self::p403();
        }

        if (str_contains($this->resource, '..')) {
            self::p403();
        }

        // $_GET['process'] : App process, but don't care of value
        if (isset($_GET['process'])) {
            unset($_GET['process']);
        }

        // $_GET['v'] : version in url to bypass cache in case of dotclear upgrade or in dev mode
        // but don't care of value
        if (isset($_GET['v'])) {
            unset($_GET['v']);
        }

        // $_GET['t'] : parameter given by CKEditor, but don't care of value
        if (isset($_GET['t'])) {
            unset($_GET['t']);
        }

        // $_GET['theme'] : theme in url to bypass cache parameter given by theme's resources loading
        if (isset($_GET['theme'])) {
            unset($_GET['theme']);
        }

        // Only $_GET['pf'] is allowed in URL
        if (count($_GET) > 1) {
            self::p403();
        }

        unset($_GET['pf'], $_GET['vf'], $_GET['tf']);

        if (!$this->core->config()->dotclearRoot() || !$this->core->config()->pluginsRoot() || !$this->core->config()->varRoot()) {
            self::p404();
        }

        if (!in_array($this->extension, self::DEFAULT_EXTENSIONS)) {
            self::p404();
        }
    }

    /**
     * Find file in plugins.
     */
    protected function findPluginFile(): void
    {
        $paths = array_reverse(explode(PATH_SEPARATOR, $this->core->config()->pluginsRoot()));

        foreach ($paths as $path) {
            $file = Path::real($path . '/' . $this->resource);

            if ($file !== false && $this->setFile($file)) {
                return;
            }
        }

        // not in modules, try in core
        $this->findCoreFile();
    }

    /**
     * Find file in theme.
     */
    protected function findThemeFile(): void
    {
        $paths = [];

        // Emulate public prepend
        $this->core->task()->addContext('FRONTEND');
        $this->core->frontend();

        // Get blog ID defined in index.php of blog (Frontend context)
        $blogId = $this->core->config()->blogId();
        if ($blogId === '') {
            // Get blog ID currently defined if any (whatever is the context)
            $blogId = $this->core->blog()->id();
            if ($blogId === '') {
                $this->core->session()->start();
                if ($this->core->auth()->sessionExists() && $this->core->auth()->checkSession()) {
                    // Try to get currently selected blog from session (Backend context)
                    if ($this->core->session()->get('sess_blog_id') != '') {
                        if ($this->core->auth()->getPermissions($this->core->session()->get('sess_blog_id')) !== false) {
                            $blogId = $this->core->session()->get('sess_blog_id');
                        }
                    } elseif (($b = $this->core->auth()->findUserBlog($this->core->auth()->getInfo('user_default_blog'), false)) !== false) {
                        // Finally get default blog for currently authenticated user
                        $blogId = $b;
                    }
                }
            }
        }

        if ($blogId) {
            // Load blog
            $this->core->blog()->loadFromBlog($blogId);
            $theme = $this->core->blog()->settings()->system->theme;
            if ($theme) {
                // Get current theme path
                $dir_theme = Path::real($this->core->blog()->themesPath() . '/' . $theme);
                if ($dir_theme) {
                    $paths[] = $dir_theme;

                    // Get current parent theme path if any
                    $parent_theme = $this->core->themes()->moduleInfo($theme, 'parent');
                    if ($parent_theme) {
                        $dir_parent_theme = Path::real($this->core->blog()->themesPath() . '/' . $parent_theme);
                        if ($dir_parent_theme) {
                            $paths[] = $dir_parent_theme;
                        }
                    }

                    // Check if there is a overloaded path for current theme
                    $custom_theme = Path::real($this->core->config()->varRoot() . '/themes/' . $blogId . '/' . $theme);

                    if ($custom_theme) {
                        // Set custom path at first (custom > theme > parent > core)
                        array_unshift($paths, $custom_theme);
                    }
                }
            }
        }

        foreach ($paths as $path) {
            $file = Path::real($path . '/' . $this->resource);
            if ($file !== false && $this->setFile($file)) {
                return;
            }
        }

        // not in theme, try in core
        $this->findCoreFile();
    }

    /**
     * Find file in core.
     */
    protected function findCoreFile(): void
    {
        foreach (self::DEFAULT_CORE_LIMITS as $folder) {
            if ($this->setFile(implode(DIRECTORY_SEPARATOR, [$this->core->config()->dotclearRoot(), 'inc', $folder, $this->resource]))) {
                break;
            }
        }
    }

    /**
     * Find file in var.
     */
    protected function findVarFile(): void
    {
        $file = Path::real($this->core->config()->varRoot() . '/' . $this->resource);

        if ($file !== false) {
            $this->setFile($file);
        }
    }

    /**
     * Find minified correponding file.
     */
    protected function findMinified(): void
    {
        // For JS and CSS, look if a minified version exists
        if (!is_null($this->file) && !$this->debug && in_array($this->extension, self::DEFAULT_MINIFIED)) {
            $minified_base = substr($this->file, 0, strlen($this->file) - strlen($this->extension) - 1);
            if (Files::getExtension($minified_base) !== 'min') {
                $this->setFile($minified_base . '.min.' . $this->extension);
            }
        }
    }

    /**
     * Check and set file.
     *
     * @param   string  $file   The file
     *
     * @return  bool    True if it is a readebale file.
     */
    protected function setFile(string $file): bool
    {
        if (is_file($file) && is_readable($file)) {
            $this->file = $file;

            return true;
        }

        return false;
    }

    /**
     * Output file.
     *
     * This return a 404 on error.
     */
    protected function readFile(): void
    {
        if ($this->file === null || $this->file === '') {
            self::p404();
        }

        $this->findMinified();

        // serve file
        Http::$cache_max_age = $this->debug && in_array($this->extension, self::DEFAULT_NOCACHE) ? 0 : self::$cache_ttl;

        $mod_files = [
            $this->file ?? '',
            ...get_included_files(),
        ];
        Http::cache($mod_files);

        header('Content-Type: ' . Files::getMimeType((string) $this->file));
        readfile((string) $this->file);
        dotclear_exit();
    }

    /**
     * Ouput a HTTP 404.
     */
    protected static function p404(): void
    {
        header('Content-Type: text/plain');
        Http::head(404, 'Not Found');
        dotclear_exit();
    }

    /**
     * Ouput a HTTP 403.
     */
    protected static function p403(): void
    {
        header('Content-Type: text/plain');
        Http::head(403, 'Forbidden');
        dotclear_exit();
    }
}
