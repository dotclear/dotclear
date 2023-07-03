<?php
/**
 * @brief Modules handler
 *
 * Provides an object to handle modules (themes or plugins).
 *
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

use Dotclear\Core\Backend\Page;
use Dotclear\Core\Process;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\File\Zip\Unzip;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\L10n;
use Dotclear\Helper\Network\Http;

class dcModules
{
    // Constants

    /** @var    int     Return code for package installation */
    public const PACKAGE_INSTALLED = 1;

    /** @var    int     Return code for package update */
    public const PACKAGE_UPDATED = 2;

    /** @var    string  Name of module old style installation file */
    public const MODULE_FILE_INSTALL = '_install.php';

    /** @var    string  Name of module old style initialization file */
    public const MODULE_FILE_INIT = '_init.php';

    /** @var    string  Name of module define file */
    public const MODULE_FILE_DEFINE = '_define.php';

    /** @var    string  Name of module old style prepend file */
    public const MODULE_FILE_PREPEND = '_prepend.php';

    /** @var    string  Name of module old style backend file */
    public const MODULE_FILE_ADMIN = '_admin.php';

    /** @var    string  Name of module old style configuration file */
    public const MODULE_FILE_CONFIG = '_config.php';

    /** @var    string  Name of module old style manage file */
    public const MODULE_FILE_MANAGE = 'index.php';

    /** @var    string  Name of module old style frontend file */
    public const MODULE_FILE_PUBLIC = '_public.php';

    /** @var    string  Name of module old style xmlrpc file */
    public const MODULE_FILE_XMLRPC = '_xmlrpc.php';

    /** @var    string  Name of module hard deactivation file */
    public const MODULE_FILE_DISABLED = '_disabled';

    /** @var    string  The update locked file name */
    public const MODULE_FILE_LOCKED = '_locked';

    /** @var    string  Directory for module namespace */
    public const MODULE_CLASS_DIR = 'src';

    /** @var    string  Name of module prepend class (ex _prepend.php) */
    public const MODULE_CLASS_PREPEND = 'Prepend';

    /** @var    string  Name of module installation class (ex _install.php) */
    public const MODULE_CLASS_INSTALL = 'Install';

    /** @var    string  Name of module backend class (ex _admin.php) */
    public const MODULE_CLASS_ADMIN = 'Backend';

    /** @var    string  Name of module configuration class (ex _config.php) */
    public const MODULE_CLASS_CONFIG = 'Config';

    /** @var    string  Name of module manage class (ex index.php) */
    public const MODULE_CLASS_MANAGE = 'Manage';

    /** @var    string  Name of module frontend class (ex _public.php) */
    public const MODULE_CLASS_PUPLIC = 'Frontend';

    /** @var    string  Name of module XMLRPC services class (ex _xmlrpc.php) - obsolete since 2.24 */
    public const MODULE_CLASS_XMLRPC = 'Xmlrpc';

    // Properties

    /** @var    bool    Safe mode execution */
    protected $safe_mode = false;

    /** @var    array<int,string>   Stack of modules paths */
    protected $path = [];

    /** @var    array<int,dcModuleDefine>   Stack of modules */
    protected $defines = [];

    /** @var    array<int,string>   Stack of error messages */
    protected $errors = [];

    /** @var    array<string,string>   Stack of modules id|version pairs */
    protected $modules_ids = [];

    /** @var    array<string,array<int,string>> Stack of modules paths (used as internal cache) */
    protected $modules_paths = [];

    /** @var    array<int,string>   Stack of loaded modules _init files (prevent twice load)*/
    protected static $modules_init = [];

    /** @var    bool    Current deactivation mode */
    protected $disabled_mode = false;

    /** @var    string|null     Current dc namespace */
    protected $ns = null;

    /** @var    dcModuleDefine  Current module Define */
    protected $define;

    /** @var    string|null     Current module identifier */
    protected $id = null;

    /** @var    string|null     Current module root path (where _define.php is located) */
    protected $mroot = null;

    /** @var    string|null     Current module php namespace */
    protected $namespace = null;

    /** @var    array<int,string>   Inclusion variables */
    protected static $superglobals = [
        'GLOBALS',
        '_SERVER',
        '_GET',
        '_POST',
        '_COOKIE',
        '_FILES',
        '_ENV',
        '_REQUEST',
        '_SESSION',
    ];

    /** @var    array<int,string>   Superglobals array keys */
    protected static $_k;

    /** @var    string  Superglobals key name */
    protected static $_n;

    /** @var    string|null     Module type to work with */
    protected $type = null;

    /**
     * Get first ocurrence of a module's defined properties.
     *
     * This method always returns a dcModuleDefine class,
     * if module definition does not exist, it is created on the fly
     * with default properties.
     *
     * @param   string                  $id         The module identifier
     * @param   array<string,mixed>     $search     The search parameters
     *
     * @return  dcModuleDefine   The first matching module define or properties
     */
    public function getDefine(string $id, array $search = []): dcModuleDefine
    {
        $found = $this->getDefines(array_merge($search, ['id' => $id]));

        return empty($found) ? new dcModuleDefine($id) : $found[0];
    }

    /**
     * Get modules defined properties.
     *
     * More than one module can have same id in this stack.
     *
     * @param   array<string,mixed>     $search     The search parameters
     * @param   bool                    $to_array   Return arrays of modules properties
     *
     * @return  array   The modules defines or properties
     */
    public function getDefines(array $search = [], bool $to_array = false): array
    {
        // only compare some types of values
        $to_string = fn ($value): ?string => is_bool($value) || is_int($value) || is_string($value) ? (string) $value : null;

        $list = [];
        foreach ($this->defines as $module) {
            $add_it = true;
            foreach ($search as $key => $value) {
                // check types
                if (!is_string($key) || is_null($module->get($key))) {
                    continue;
                }
                // compare string format
                $value  = $to_string($value);
                $source = $to_string($module->get($key));
                if (is_null($source) || is_null($value)) {
                    continue;
                }
                if (substr($value, 0, 1) === '!') {
                    if ($source === substr($value, 1)) {
                        $add_it = false;

                        break;
                    }
                } elseif ($source !== $value) {
                    $add_it = false;

                    break;
                }
            }
            if ($add_it) {
                if ($to_array) {
                    $list[$module->getId()] = $module->dump();
                } else {
                    $list[] = $module;
                }
            }
        }

        return $list;
    }

    /**
     * Checks all modules dependencies
     *
     * Fills in the following information in module :
     *
     *  - missing : list reasons why module cannot be enabled. Not set if module can be enabled
     *
     *  - using : list reasons why module cannot be disabled. Not set if module can be disabled
     *
     *  - implies : reverse dependencies
     */
    public function checkDependencies(): void
    {
        // Grab current Dotclear and PHP version
        $special = [
            'core' => preg_replace('/\-dev.*$/', '', DC_VERSION),
            'php'  => phpversion(),
        ];

        $modules    = $this->getDefines();
        $optionnals = [];

        foreach ($modules as $module) {
            // module has required modules
            if (!empty($module->requires)) {
                foreach ($module->requires as $dep) {
                    if (!is_array($dep)) {
                        $dep = [$dep];
                    }
                    // optionnal minimum dependancy
                    $optionnal = false;
                    if (substr($dep[0], -1) == '?') {
                        $dep[0]                                = substr($dep[0], 0, -1);
                        $optionnals[$module->getId()][$dep[0]] = true;
                    }
                    // search required module
                    $found = $this->getDefine($dep[0]);
                    // grab missing dependencies
                    if (!$found->isDefined() && !isset($special[$dep[0]]) && !isset($optionnals[$module->getId()][$dep[0]])) {
                        // module not present, nor php or dotclear, nor optionnal
                        $module->addMissing($dep[0], sprintf(__('Requires %s module which is not installed'), $dep[0]));
                    } elseif ((count($dep) > 1) && version_compare(($special[$dep[0]] ?? $found->version), $dep[1]) == -1) {
                        // module present, but version missing
                        if ($dep[0] == 'php') {
                            $dep[0] = 'PHP';
                            $dep_v  = $special['php'];
                        } elseif ($dep[0] == 'core') {
                            $dep[0] = 'Dotclear';
                            $dep_v  = $special['core'];
                        } else {
                            $dep_v = $found->version;
                        }
                        $module->addMissing($dep[0], sprintf(
                            __('Requires %s version %s, but version %s is installed'),
                            $dep[0],
                            $dep[1],
                            $dep_v
                        ));
                    } elseif (!isset($special[$dep[0]]) && $found->state != dcModuleDefine::STATE_ENABLED) {
                        // module disabled
                        $module->addMissing($dep[0], sprintf(__('Requires %s module which is disabled'), $dep[0]));
                    }
                    $found->addImplies($module->getId());
                }
            }
        }
        // Check modules that cannot be disabled
        foreach ($modules as $module) {
            if (!empty($module->getImplies()) && $module->state == dcModuleDefine::STATE_ENABLED) {
                foreach ($module->getImplies() as $im) {
                    if (isset($optionnals[$im][$module->getId()])) {
                        continue;
                    }
                    foreach ($this->getDefines(['id' => $im]) as $found) {
                        if ($found->state == dcModuleDefine::STATE_ENABLED) {
                            $module->addUsing($im);
                        }
                    }
                }
            }
        }
    }

    /**
     * Checks all modules dependencies, and disable unmet dependencies
     */
    /**
     * Disables the dep modules.
     *
     * @param  string $redirect_url URL to redirect if modules are to disable
     *
     * @return bool  true if a redirection has been performed
     */
    public function disableDepModules(string $redirect_url): bool
    {
        if (isset($_GET['dep'])) {
            // Avoid infinite redirects
            return false;
        }
        $reason = [];
        foreach ($this->getDefines() as $module) {
            if (empty($module->getMissing()) || $module->state != dcModuleDefine::STATE_ENABLED) {
                continue;
            }

            try {
                $this->deactivateModule($module->getId());
                $reason[] = sprintf('<li>%s : %s</li>', $module->name, join(',', $module->getMissing()));
            } catch (Exception $e) {
                // Ignore exceptions
            }
        }
        if (count($reason)) {
            $message = sprintf(
                '<p>%s</p><ul>%s</ul>',
                __('The following extensions have been disabled :'),
                join('', $reason)
            );
            Page::addWarningNotice($message, ['divtag' => true, 'with_ts' => false]);
            $url = $redirect_url . (strpos($redirect_url, '?') ? '&' : '?') . 'dep=1';
            Http::redirect($url);

            return true;
        }

        return false;
    }

    /**
     * Should run in safe mode?
     *
     * @param      null|bool   $mode   Mode, null to read current mode
     *
     * @return     bool
     */
    public function safeMode(?bool $mode = null): bool
    {
        if (is_bool($mode)) {
            $this->safe_mode = $mode;
        }

        return $this->safe_mode;
    }

    /**
     * Get list of modules in a directory
     *
     * @param      string  $root   The root modules directory to parse
     *
     * @return     array<int,string>    List of modules, may be an empty array
     */
    protected function parsePathModules(string $root): array
    {
        // already scan
        if (isset($this->modules_paths[$root])) {
            return $this->modules_paths[$root];
        }

        if (!is_dir($root) || !is_readable($root)) {
            return [];
        }

        $root = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (($d = @dir($root)) === false) {
            return [];
        }

        // Dir cache
        $this->modules_paths[$root] = [];
        while (($entry = $d->read()) !== false) {
            $full_entry = $root . $entry;
            if ($entry !== '.' && $entry !== '..' && is_dir($full_entry) && file_exists($full_entry . DIRECTORY_SEPARATOR . self::MODULE_FILE_DEFINE)) {
                $this->modules_paths[$root][] = $entry;
            }
        }
        $d->close();

        return $this->modules_paths[$root];
    }

    /**
     * Loads modules. <var>$path</var> could be a separated list of paths
     * (path separator depends on your OS).
     *
     * <var>$ns</var> indicates if an additionnal file needs to be loaded on plugin
     * load, value could be:
     * - admin (loads module's _admin.php)
     * - public (loads module's _public.php)
     * - xmlrpc (loads module's _xmlrpc.php)
     *
     * <var>$lang</var> indicates if we need to load a lang file on plugin
     * loading.
     *
     * @param      string   $path   The path
     * @param      string   $ns     The namespace (context as 'public', 'admin', ...)
     * @param      string   $lang   The language
     */
    public function loadModules(string $path, ?string $ns = null, ?string $lang = null): void
    {
        $this->path      = explode(PATH_SEPARATOR, $path);
        $this->ns        = $ns;
        $this->safe_mode = isset($_SESSION['sess_safe_mode']) && $_SESSION['sess_safe_mode'];

        $ignored = [];

        // First loop to init
        foreach ($this->path as $root) {
            $stack = $this->parsePathModules($root);

            // Init loop
            $root = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            foreach ($stack as $entry) {
                $this->loadModuleInit($entry, $root . $entry);
            }
        }

        // Second loop to register
        foreach ($this->path as $root) {
            $stack = $this->parsePathModules($root);

            // Register loop
            $root = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            foreach ($stack as $entry) {
                $full_entry  = $root . $entry;
                $this->id    = $entry;
                $this->mroot = $full_entry;

                // Module namespace
                $this->namespace = implode(Autoloader::NS_SEP, ['', 'Dotclear', ucfirst($this->type ?? dcModuleDefine::DEFAULT_TYPE), $this->id]);
                Autoloader::me()->addNamespace($this->namespace, $this->mroot . DIRECTORY_SEPARATOR . self::MODULE_CLASS_DIR);

                $module_disabled = file_exists($full_entry . DIRECTORY_SEPARATOR . self::MODULE_FILE_DISABLED);
                $module_enabled  = !$module_disabled && !$this->safe_mode;
                if (!$module_enabled) {
                    $this->disabled_mode = true;
                }

                $this->loadModuleFile($full_entry . DIRECTORY_SEPARATOR . self::MODULE_FILE_DEFINE);

                if (!$module_enabled) {
                    $this->disabled_mode = false;
                    $this->define->state = $module_disabled ? dcModuleDefine::STATE_HARD_DISABLED : dcModuleDefine::STATE_SOFT_DISABLED;
                }
                $this->id        = null;
                $this->mroot     = null;
                $this->namespace = null;
            }
        }
        $this->checkDependencies();

        // Sort plugins by priority
        uasort($this->defines, fn ($a, $b) => $a->get('priority') <=> $b->get('priority'));

        $modules = $this->getDefines(['state' => dcModuleDefine::STATE_ENABLED]);

        // Prepend loop
        foreach ($modules as $module) {
            // Do not load anything if module has missing dependencies
            if (!empty($module->getMissing())) {
                $ignored[] = $module->getId();

                continue;
            }

            $ret = true;

            // by class name
            $class = $module->namespace . Autoloader::NS_SEP . self::MODULE_CLASS_PREPEND;
            if (!empty($module->namespace) && class_exists($class)) {
                $ret = $class::init() ? $class::process() : false;
                // by file name
            } elseif (file_exists($module->root . DIRECTORY_SEPARATOR . self::MODULE_FILE_PREPEND)) {
                $ret = $this->loadModuleFile($module->root . DIRECTORY_SEPARATOR . self::MODULE_FILE_PREPEND, true);
            }

            if ($ret !== true) {
                // If _prepend.php file returns null (ie. it has a void return statement)
                $ignored[] = $module->getId();

                continue;
            }
        }

        // Load all modules main translation (new loop as it may required Proxy plugin)
        foreach($this->getDefines() as $module) {
            $this->loadModuleL10N($module->getId(), $lang, 'main');
        }

        // Load modules context
        if (!empty($ns)) {
            // Give opportunity to do something before loading context (admin,public,xmlrpc) files
            # --BEHAVIOR-- coreBeforeLoadingNsFilesV2 -- dcModules, string|null
            dcCore::app()->callBehavior('coreBeforeLoadingNsFilesV2', $this, $lang);

            $this->loadModulesContext($ignored, $ns, $lang);
        }
    }

    /**
     * Load modules context.
     *
     * @param      array<int,string>    $ignored    The modules to ignore
     * @param      null|string          $ns         The namespace (context as 'public', 'admin', ...)
     * @param      null|string          $lang       The language
     */
    protected function loadModulesContext(array $ignored, string $ns, ?string $lang): void
    {
        foreach ($this->getDefines() as $module) {
            if (in_array($module->getId(), $ignored)) {
                continue;
            }
            if ($ns == 'admin') {
                $this->loadModuleL10Nresources($module->getId(), $lang);
                dcCore::app()->adminurl->register('admin.plugin.' . $module->getId(), dcCore::app()->adminurl->get('admin.plugin'), ['p' => $module->getId()]);
            }
            // Load ns_file
            $this->loadNsFile($module->getId(), $ns);
        }
    }

    /**
     * Load the _define.php file of the given module
     *
     * @param      string  $dir    The dir
     * @param      string  $id     The module identifier
     */
    public function requireDefine(string $dir, string $id)
    {
        $this->id = $id;
        if (file_exists($dir . DIRECTORY_SEPARATOR . self::MODULE_FILE_DEFINE)) {
            $this->loadModuleInit($id, $dir);
            $this->loadModuleFile($dir . DIRECTORY_SEPARATOR . self::MODULE_FILE_DEFINE);
        }
        $this->id = null;
    }

    /**
     * This method registers a module in modules list.
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
            foreach ($properties as $k => $v) {
                $define->set($k, $v);
            }
        }

        $this->defineModule($define);
    }

    protected function defineModule(dcModuleDefine $define)
    {
        $this->define = $define;

        $this->define
            ->set('state', $this->disabled_mode ? dcModuleDefine::STATE_INIT_DISABLED : dcModuleDefine::STATE_ENABLED)
            ->set('root', $this->mroot)
            ->set('namespace', $this->namespace)
            ->set('root_writable', !is_null($this->mroot) && is_writable($this->mroot))
        ;

        // dc < 2.25, module type was optionnal
        if ($this->define->type == dcModuleDefine::DEFAULT_TYPE) {
            $this->define->set('type', $this->type);
        }

        // dc < 2.26, priority could be negative
        if ((int) $this->define->get('priority') < 0) {
            $this->define->set('priority', 1);
        }

        $this->define->set('distributed', in_array($this->define->getId(), explode(',', $this->define->type == 'theme' ? DC_DISTRIB_THEMES : DC_DISTRIB_PLUGINS)));

        if ($this->disabled_mode) {
            $this->defines[] = $this->define;

            return;
        }

        // try to extract dc_min for easy reading
        if (empty($this->define->dc_min) && !empty($this->define->requires)) {
            foreach ($this->define->requires as $dep) {
                if (is_array($dep) && count($dep) == 2 && $dep[0] == 'core') {
                    $this->define->dc_min = $dep[1];
                }
            }
        }

        // Check module type
        if ($this->type !== null && $this->define->type !== $this->type) {
            $this->errors[] = sprintf(
                __('Module "%s" has type "%s" that mismatch required module type "%s".'),
                '<strong>' . Html::escapeHTML($this->define->name) . '</strong>',
                '<em>' . Html::escapeHTML($this->define->type) . '</em>',
                '<em>' . Html::escapeHTML($this->type) . '</em>'
            );

            return;
        }

        // Check module perms on admin side
        $permissions = $this->define->permissions;
        if ($this->ns === 'admin') {
            if (($permissions == '' && !dcCore::app()->auth->isSuperAdmin()) || (!dcCore::app()->auth->check($permissions, dcCore::app()->blog->id))) {
                return;
            }
        }

        # Check module install on multiple path
        if ($this->id) {
            $module_exists    = array_key_exists($this->id, $this->modules_ids);
            $module_overwrite = $module_exists ? version_compare($this->modules_ids[$this->id], $this->define->version, '<') : false;
            if (!$module_exists || $module_overwrite) {
                $this->modules_ids[$this->id] = $this->define->version;
                $this->defines[]              = $this->define;
            } else {
                $path1 = Path::real($this->moduleInfo($this->id, 'root') ?? '');
                $path2 = Path::real($this->mroot ?? '');

                $this->errors[] = sprintf(
                    __('Module "%s" is installed twice in "%s" and "%s".'),
                    '<strong>' . $this->define->name . '</strong>',
                    '<em>' . $path1 . '</em>',
                    '<em>' . $path2 . '</em>'
                );
            }
        }
    }

    /**
     * Reset modules list
     */
    public function resetModulesList(): void
    {
        $this->defines     = [];
        $this->modules_ids = [];
        $this->errors      = [];
    }

    /**
     * Install a Package
     *
     * @param      string     $zip_file  The zip file
     * @param      dcModules  $modules   The modules
     *
     * @throws     Exception
     *
     * @return     int
     */
    public static function installPackage(string $zip_file, dcModules &$modules): int
    {
        $zip = new Unzip($zip_file);
        $zip->getList(false, '#(^|/)(__MACOSX|\.svn|\.hg.*|\.git.*|\.DS_Store|\.directory|Thumbs\.db)(/|$)#');

        $zip_root_dir = $zip->getRootDir();
        $define       = '';
        if ($zip_root_dir != false) {
            $target      = dirname($zip_file);
            $destination = $target . DIRECTORY_SEPARATOR . $zip_root_dir;
            $define      = $zip_root_dir . '/' . self::MODULE_FILE_DEFINE;
            $init        = $zip_root_dir . '/' . self::MODULE_FILE_INIT;
            $has_define  = $zip->hasFile($define);
        } else {
            $target      = dirname($zip_file) . DIRECTORY_SEPARATOR . preg_replace('/\.([^.]+)$/', '', basename($zip_file));
            $destination = $target;
            $define      = self::MODULE_FILE_DEFINE;
            $init        = self::MODULE_FILE_INIT;
            $has_define  = $zip->hasFile($define);
        }

        if ($zip->isEmpty()) {
            $zip->close();
            unlink($zip_file);

            throw new Exception(__('Empty module zip file.'));
        }

        if (!$has_define) {
            $zip->close();
            unlink($zip_file);

            throw new Exception(__('The zip file does not appear to be a valid Dotclear module.'));
        }

        $ret_code        = self::PACKAGE_INSTALLED;
        $module_disabled = false;

        if (!is_dir($destination)) {
            // New plugin
            try {
                Files::makeDir($destination, true);

                $sandbox = clone $modules;
                // Force normal mode
                $sandbox->safeMode(false);

                if ($zip->hasFile($init)) {
                    $zip->unzip($init, $target . DIRECTORY_SEPARATOR . self::MODULE_FILE_INIT);
                }

                $zip->unzip($define, $target . DIRECTORY_SEPARATOR . self::MODULE_FILE_DEFINE);

                $sandbox->resetModulesList();
                $sandbox->requireDefine($target, basename($destination));
                if ($zip->hasFile($init)) {
                    unlink($target . DIRECTORY_SEPARATOR . self::MODULE_FILE_INIT);
                }

                unlink($target . DIRECTORY_SEPARATOR . self::MODULE_FILE_DEFINE);

                $new_errors = $sandbox->getErrors();
                if (!empty($new_errors)) {
                    $new_errors = implode(" \n", $new_errors);

                    throw new Exception($new_errors);
                }

                Files::deltree($destination);
            } catch (Exception $e) {
                $zip->close();
                unlink($zip_file);
                Files::deltree($destination);

                throw new Exception($e->getMessage());
            }
        } else {
            //
            $sandbox = clone $modules;
            // Force normal mode
            $sandbox->safeMode(false);

            if ($zip->hasFile($init)) {
                $zip->unzip($init, $target . DIRECTORY_SEPARATOR . self::MODULE_FILE_INIT);
            }

            $zip->unzip($define, $target . DIRECTORY_SEPARATOR . self::MODULE_FILE_DEFINE);

            $sandbox->resetModulesList();
            $sandbox->requireDefine($target, basename($destination));
            if ($zip->hasFile($init)) {
                unlink($target . DIRECTORY_SEPARATOR . self::MODULE_FILE_INIT);
            }

            unlink($target . DIRECTORY_SEPARATOR . self::MODULE_FILE_DEFINE);

            $new_defines = $sandbox->getDefines();

            if (count($new_defines) == 1) {
                // Check if module is disabled
                $module_disabled = file_exists($destination . DIRECTORY_SEPARATOR . self::MODULE_FILE_DISABLED);

                $cur_define = $modules->getDefine($new_defines[0]->getId());
                // current module update is locked
                if ($cur_define->updLocked()) {
                    $zip->close();
                    unlink($zip_file);

                    throw new Exception(sprintf(__('Unable to upgrade "%s". (update locked)'), basename($destination)));
                } elseif ($cur_define->isDefined() && (defined('DC_DEV') && DC_DEV === true || dcUtils::versionsCompare($new_defines[0]->get('version'), $cur_define->get('version'), '>', true))) {
                    // delete old module
                    if (!Files::deltree($destination)) {
                        throw new Exception(__('An error occurred during module deletion.'));
                    }

                    $ret_code = self::PACKAGE_UPDATED;
                } else {
                    $zip->close();
                    unlink($zip_file);

                    throw new Exception(sprintf(__('Unable to upgrade "%s". (older or same version)'), basename($destination)));
                }
            } else {
                $zip->close();
                unlink($zip_file);

                throw new Exception(sprintf(__('Unable to read new %s file'), self::MODULE_FILE_DEFINE));
            }
        }
        $zip->unzipAll($target);
        $zip->close();
        unlink($zip_file);

        // Restore hard disabled status if necessary
        if ($module_disabled) {
            if (@file_put_contents($destination . DIRECTORY_SEPARATOR . self::MODULE_FILE_DISABLED, '')) {
                throw new Exception(__('Cannot deactivate plugin.'));
            }
        }

        // reset server cache (opcache) before refresh modules dirs
        Path::resetServerCache();

        return $ret_code;
    }

    /**
     * This method installs all modules having a _install file.
     *
     * @see dcModules::installModule
     *
     * @return     array
     */
    public function installModules(): array
    {
        $res = [
            'success' => [],
            'failure' => [],
        ];
        $msg = '';
        foreach ($this->getDefines(['state' => dcModuleDefine::STATE_ENABLED]) as $module) {
            $ret = $this->installModule($module->getId(), $msg);
            if ($ret === true) {
                $res['success'][$module->getId()] = true;
            } elseif ($ret === false) {
                $res['failure'][$module->getId()] = $msg;
            }
        }

        return $res;
    }

    /**
     * This method installs module with ID <var>$id</var> and having a _install
     * file. This file should throw exception on failure or true if it installs
     * successfully.
     *
     * <var>$msg</var> is an out parameter that handle installer message.
     *
     * @param      string  $id     The identifier
     * @param      string  $msg    The message
     *
     * @return     mixed
     */
    public function installModule(string $id, string &$msg)
    {
        $module = $this->getDefine($id, ['state' => dcModuleDefine::STATE_ENABLED]);

        if (!$module->isDefined()) {
            return;
        }

        try {
            // by class name
            $install = !empty($this->loadNsClass($id, self::MODULE_CLASS_INSTALL));
            // by file name
            if (!$install) {
                $install = $this->loadModuleFile($module->root . DIRECTORY_SEPARATOR . self::MODULE_FILE_INSTALL, true);
            }

            if ($install === true || $install === null) {
                // Register new version if necessary
                $old_version = dcCore::app()->getVersion($id);
                $new_version = $module->version;
                if (version_compare((string) $old_version, $new_version, '<')) {
                    // Register new version
                    dcCore::app()->setVersion($id, $new_version);
                }

                if ($install === true) {
                    return true;
                }
            }
        } catch (Exception $e) {
            $msg = $e->getMessage();

            return false;
        }
    }

    /**
     * Delete a module
     *
     * @param      string     $id        The module identifier
     * @param      bool       $disabled  Is module disabled
     *
     * @throws     Exception
     */
    public function deleteModule(string $id, bool $disabled = false): void
    {
        $module = $this->getDefine($id, ['state' => ($disabled ? '!' : '') . dcModuleDefine::STATE_ENABLED]);

        if (!$module->isDefined()) {
            throw new Exception(__('No such module.'));
        }

        if (!Files::deltree($module->root)) {
            throw new Exception(__('Cannot remove module files'));
        }
    }

    /**
     * Deactivate a module
     *
     * @param      string     $id     The identifier
     *
     * @throws     Exception
     */
    public function deactivateModule(string $id): void
    {
        $module = $this->getDefine($id, ['state' => $this->safe_mode ? dcModuleDefine::STATE_SOFT_DISABLED : dcModuleDefine::STATE_ENABLED]);

        if (!$module->isDefined()) {
            throw new Exception(__('No such module.'));
        }

        if (!$module->root_writable) {
            throw new Exception(__('Cannot deactivate plugin.'));
        }

        if (@file_put_contents($module->root . DIRECTORY_SEPARATOR . self::MODULE_FILE_DISABLED, '')) {
            throw new Exception(__('Cannot deactivate plugin.'));
        }
    }

    /**
     * Activate a module
     *
     * @param      string     $id     The identifier
     *
     * @throws     Exception
     */
    public function activateModule(string $id): void
    {
        $module = $this->getDefine($id, ['state' => '!' . dcModuleDefine::STATE_ENABLED]);

        if (!$module->isDefined()) {
            throw new Exception(__('No such module.'));
        }

        if (!$module->root_writable) {
            throw new Exception(__('Cannot activate plugin.'));
        }

        if (@unlink($module->root . DIRECTORY_SEPARATOR . self::MODULE_FILE_DISABLED) === false) {
            throw new Exception(__('Cannot activate plugin.'));
        }
    }

    /**
     * Clone a module
     *
     * @param      string  $id     The module identifier
     */
    public function cloneModule(string $id): void
    {
    }

    /**
     * This method will search for file <var>$file</var> in language
     * <var>$lang</var> for module <var>$id</var>.
     *
     * <var>$file</var> should not have any extension.
     *
     * @param      string  $id     The module identifier
     * @param      string  $lang   The language code
     * @param      string  $file   The filename (without extension)
     */
    public function loadModuleL10N(string $id, ?string $lang, string $file): void
    {
        $module = $this->getDefine($id);//, ['state' => dcModuleDefine::STATE_ENABLED]);
        if ($lang && $module->isDefined()) {
            $lfile = $module->root . DIRECTORY_SEPARATOR . 'locales' . DIRECTORY_SEPARATOR . '%s' . DIRECTORY_SEPARATOR . '%s';
            if (L10n::set(sprintf($lfile, $lang, $file)) === false && $lang != 'en') {
                L10n::set(sprintf($lfile, 'en', $file));
            }
        }
    }

    /**
     * Loads module l10n resources.
     *
     * @param      string  $id     The module identifier
     * @param      string  $lang   The language code
     */
    public function loadModuleL10Nresources(string $id, ?string $lang): void
    {
        $module = $this->getDefine($id, ['state' => dcModuleDefine::STATE_ENABLED]);
        if ($lang && $module->isDefined()) {
            if ($file = L10n::getFilePath($module->root . DIRECTORY_SEPARATOR . 'locales', 'resources.php', $lang)) {
                $this->loadModuleFile($file, true);
            }
        }
    }

    /**
     * Returns all modules associative array or only one module if <var>$id</var> is present.
     *
     * @deprecated since 2.26 Use self::getDefines()
     *
     * @param      string  $id     The optionnal module identifier
     *
     * @return     array  The module(s).
     */
    public function getModules(?string $id = null): array
    {
        dcDeprecated::set('dcModules::getDefines()', '2.26');

        $modules = $this->getDefines(['state' => $this->safe_mode ? dcModuleDefine::STATE_SOFT_DISABLED : dcModuleDefine::STATE_ENABLED], true);

        return $id && isset($modules[$id]) ? $modules[$id] : $modules;
    }

    /**
     * Gets all modules (whatever are their statuses) or only one module if <var>$id</var> is present.
     *
     * @deprecated since 2.26 Use self::getDefines()
     *
     * @param      string  $id     The optionnal module identifier
     *
     * @return     array  The module(s).
     */
    public function getAnyModules(?string $id = null): array
    {
        dcDeprecated::set('dcModules::getDefines()', '2.26');

        $modules = $this->getDefines([], true);

        return $id && isset($modules[$id]) ? $modules[$id] : $modules;
    }

    /**
     * Determines if module exists and is enabled.
     *
     * @param      string  $id     The module identifier
     *
     * @return     bool  True if module exists, False otherwise.
     */
    public function moduleExists(string $id): bool
    {
        return $this->getDefine($id, ['state' => dcModuleDefine::STATE_ENABLED])->isDefined();
    }

    /**
     * Gets the disabled modules.
     *
     * @deprecated since 2.26 Use self::getDefines()
     *
     * @return     array  The disabled modules.
     */
    public function getDisabledModules(): array
    {
        dcDeprecated::set('dcModules::getDefines()', '2.26');

        return $this->getDefines(['state' => '!' . dcModuleDefine::STATE_ENABLED], true);
    }

    /**
     * Gets the hard disabled modules.
     *
     * @deprecated since 2.26 Use self::getDefines()
     *
     * @return     array  The hard disabled modules.
     */
    public function getHardDisabledModules(): array
    {
        dcDeprecated::set('dcModules::getDefines()', '2.26');

        return $this->getDefines(['state' => dcModuleDefine::STATE_HARD_DISABLED], true);
    }

    /**
     * Gets the soft disabled modules (safe mode and not hard disabled).
     *
     * @deprecated since 2.26 Use self::getDefines()
     *
     * @return     array  The soft disabled modules.
     */
    public function getSoftDisabledModules(): array
    {
        dcDeprecated::set('dcModules::getDefines()', '2.26');

        return $this->getDefines(['state' => dcModuleDefine::STATE_SOFT_DISABLED], true);
    }

    /**
     * Returns root path for module with ID <var>$id</var>.
     *
     * @deprecated since 2.26 Use self::moduleInfo()
     *
     * @param      string  $id     The module identifier
     *
     * @return     mixed
     */
    public function moduleRoot(string $id)
    {
        dcDeprecated::set('dcModules::moduleInfo()', '2.26');

        return $this->moduleInfo($id, 'root');
    }

    /**
     * Returns a module information that could be:
     * - root
     * - name
     * - desc
     * - author
     * - version
     * - permissions
     * - priority
     * - …
     *
     * @param      string  $id     The module identifier
     * @param      string  $info   The information
     *
     * @return     mixed
     */
    public function moduleInfo(string $id, string $info)
    {
        return $this->getDefine($id, ['state' => dcModuleDefine::STATE_ENABLED])->get($info);
    }

    /**
     * Loads namespace <var>$ns</var> specific files for all modules.
     *
     * @deprecated since 2.27 Use nothing instead !
     *
     * @param      string  $ns
     */
    public function loadNsFiles(?string $ns = null): void
    {
        dcDeprecated::set('nothing', '2.27');

        foreach ($this->getDefines(['state' => dcModuleDefine::STATE_ENABLED]) as $module) {
            $this->loadNsFile($module->getId(), $ns);
        }
    }

    /**
     * Loads namespace <var>$ns</var> specific file for module with ID
     * <var>$id</var>
     *
     * @param      string  $id     The module identifier
     * @param      string  $ns     Namespace name
     */
    public function loadNsFile(string $id, ?string $ns = null): void
    {
        $module = $this->getDefine($id, ['state' => dcModuleDefine::STATE_ENABLED]);
        if (!empty($module->getMissing()) || !$module->isDefined() || !in_array($ns, ['admin', 'public', 'xmlrpc'])) {
            return;
        }

        switch ($ns) {
            case 'admin':
                $class = self::MODULE_CLASS_ADMIN;
                $file  = self::MODULE_FILE_ADMIN;

                break;
            case 'public':
                $class = self::MODULE_CLASS_PUPLIC;
                $file  = self::MODULE_FILE_PUBLIC;

                break;
            case 'xmlrpc':
                $class = self::MODULE_CLASS_XMLRPC;
                $file  = self::MODULE_FILE_XMLRPC;

                break;
            default:
                return;
        }

        // by class name
        if ($this->loadNsClass($id, $class) === '') {
            // by file name
            $this->loadModuleFile($module->root . DIRECTORY_SEPARATOR . $file, true);
        }
    }

    /**
     * Initialise <var>$ns</var> specific namespace for module with ID
     * <var>$id</var>
     *
     * @param      string  $id      The module identifier
     * @param      string  $ns      Process name
     * @param      bool    $process Execute process
     *
     * @return     string  The fully qualified class name on success. Empty string on fail.
     */
    public function loadNsClass(string $id, string $ns, bool $process = true): string
    {
        $module = $this->getDefine($id, ['state' => dcModuleDefine::STATE_ENABLED]);

        // unknown module
        if (!$module->isDefined()) {
            return '';
        }

        // unknown class
        $class = $module->namespace . Autoloader::NS_SEP . ucfirst($ns);
        if (!is_subclass_of($class, Process::class)) {
            return '';
        }

        // initilisation failed
        if (!$class::init()) {
            return '';
        }

        // need process but failed
        if ($process && !$class::process()) {
            return '';
        }

        // ok, return fully qualified class name
        return $class;
    }

    /**
     * Gets the errors.
     *
     * @return  array<int,string>   The errors.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Require once module init file.
     *
     * @param   string  $id     The moduile id
     * @param   string  $dir    The module path
     */
    protected function loadModuleInit(string $id, string $dir): void
    {
        if (!in_array($id, self::$modules_init) && file_exists($dir . DIRECTORY_SEPARATOR . self::MODULE_FILE_INIT)) {
            self::$modules_init[] = $id;
            $this->loadModuleFile($dir . DIRECTORY_SEPARATOR . self::MODULE_FILE_INIT);
        }
    }

    /**
     * Loads a module file.
     *
     * @param      string  $________  Module filename
     * @param      bool    $globals   Should include globals variables
     * @param      bool    $catch     Should catch output to prevent hacked/corrupted modules
     *
     * @return     mixed
     */
    protected function loadModuleFile(string $________, bool $globals = false, bool $catch = true)
    {
        if (!file_exists($________)) {
            return;
        }

        // Add globals
        if ($globals) {
            self::$_k = array_keys($GLOBALS);

            foreach (self::$_k as self::$_n) {
                if (!in_array(self::$_n, self::$superglobals)) {
                    global ${self::$_n};
                }
            }
        }

        // Require file catching output
        if ($catch) {
            $ret = null;

            if (file_exists($________)) {
                ob_start();
                $ret = require $________;
                ob_end_clean();
            }

            return $ret;
        }

        // Or just require file
        return require $________;
    }
}
