<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear;

use Dotclear\Interface\ConfigInterface;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\Network\Http;

/**
 * @brief   The helper to serve file.
 *
 * This class checks request URI to find pf and vf queries and serve related file.
 * It is limited as it is loaded before dcCore to speed up requests.
 *
 * @since   2.27
 */
class FileServer
{
    /**
     * Supported types of resource.
     *
     * @var     array<int, string>  DEFAULT_TYPES
     */
    public const DEFAULT_TYPES = [
        'plugin',
        'theme',
        'core',
        'var',
    ];

    /**
     * Supported file extension.
     *
     * @var     array<int, string>  DEFAULT_EXTENSIONS
     */
    public const DEFAULT_EXTENSIONS = [
        'css',
        'eot',
        'gif',
        'html',
        'jpeg',
        'jpg',
        'js',
        'mjs',
        'json',
        'otf',
        'png',
        'svg',
        'swf',
        'ttf',
        'txt',
        'webp',
        'avif',
        'woff',
        'woff2',
        'xml',
    ];

    /**
     * Supported core base folder.
     *
     * @var     array<int, string>  DEFAULT_CORE_LIMITS
     */
    public const DEFAULT_CORE_LIMITS = [
        'js',
        'css',
        'img',
        'smilies',
    ];

    /**
     * Supported minifield file extension.
     *
     * @var     array<int, string>  DEFAULT_MINIFIED
     */
    public const DEFAULT_MINIFIED = [
        'css',
        'js',
        'mjs',
    ];

    /**
     * File extension that does not need cache in dev mode.
     *
     * @var     array<int, string>  DEFAULT_NOCACHE
     */
    public const DEFAULT_NOCACHE = [
        'css',
        'js',
        'mjs',
        'html',
    ];

    /**
     * Default cache ttl (one week).
     */
    public static int $cache_ttl = 604800;

    /**
     * Debug mode.
     */
    protected bool $debug = false;

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

    /**
     * Check URL query to find file request.
     *
     * @param   ConfigInterface     $config     The configuration handler
     */
    public static function check(ConfigInterface $config): void
    {
        if (!empty($_GET['pf']) && is_string($_GET['pf'])) {
            new self($config, 'plugin', $_GET['pf']);
        } elseif (!empty($_GET['vf']) && is_string($_GET['vf'])) {
            new self($config, 'var', $_GET['vf']);
        }
    }

    /**
     * Constructor does all job.
     *
     * @param   ConfigInterface     $config     The configuration handler
     * @param   string              $type       The resource type
     * @param   string              $resource   The resource path
     */
    public function __construct(
        protected ConfigInterface $config,
        protected string $type,
        string $resource
    ) {
        $this->debug     = $this->config->debugMode() || $this->config->devMode();
        $this->resource  = Path::clean($resource);
        $this->extension = Files::getExtension($this->resource);

        $this->checkEnv();

        match ($this->type) {
            'plugin' => $this->findPluginFile(),
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

        // Only $_GET['pf'] is allowed in URL
        if (count($_GET) > 1) {
            self::p403();
        }

        unset($_GET['pf'], $_GET['vf']);

        if (!$this->config->dotclearRoot() || !$this->config->pluginsRoot() || !$this->config->varRoot()) {
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
        $paths = array_reverse(explode(PATH_SEPARATOR, $this->config->pluginsRoot()));

        foreach ($paths as $path) {
            $file = Path::real($path . '/' . $this->resource);

            if ($file !== false && $this->setFile($file)) {
                return;
            }
        }

        // not in modules, try in core
        $this->findCorefile();
    }

    /**
     * Find file in core.
     */
    protected function findCoreFile(): void
    {
        foreach (self::DEFAULT_CORE_LIMITS as $folder) {
            if ($this->setFile(implode(DIRECTORY_SEPARATOR, [$this->config->dotclearRoot(), 'inc', $folder, $this->resource]))) {
                break;
            }
        }
    }

    /**
     * Find file in var.
     */
    protected function findVarFile(): void
    {
        $file = Path::real($this->config->varRoot() . '/' . $this->resource);

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

        /**
         * @var        array<string>
         */
        $mod_files = [
            $this->file,
            ...get_included_files(),
        ];
        Http::cache($mod_files);

        header('Content-Type: ' . Files::getMimeType((string) $this->file));
        readfile((string) $this->file);
        exit;
    }

    /**
     * Ouput a HTTP 404.
     */
    protected static function p404(): void
    {
        header('Content-Type: text/plain');
        Http::head(404, 'Not Found');
        exit;
    }

    /**
     * Ouput a HTTP 403.
     */
    protected static function p403(): void
    {
        header('Content-Type: text/plain');
        Http::head(403, 'Forbidden');
        exit;
    }
}
