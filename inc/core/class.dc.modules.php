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

use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;

class dcModules
{
    // Constants

    /**
     * Return code for package installation
     *
     * @var        int
     */
    public const PACKAGE_INSTALLED = 1;
    public const PACKAGE_UPDATED   = 2;

    /**
     * Module's files
     *
     * @var        string
     */
    public const MODULE_FILE_INSTALL  = '_install.php';
    public const MODULE_FILE_INIT     = '_init.php';
    public const MODULE_FILE_DEFINE   = '_define.php';
    public const MODULE_FILE_PREPEND  = '_prepend.php';
    public const MODULE_FILE_ADMIN    = '_admin.php';
    public const MODULE_FILE_CONFIG   = '_config.php';
    public const MODULE_FILE_MANAGE   = 'index.php';
    public const MODULE_FILE_PUBLIC   = '_public.php';
    public const MODULE_FILE_XMLRPC   = '_xmlrpc.php';
    public const MODULE_FILE_DISABLED = '_disabled';

    /**
     * Module's class
     *
     * @var        string
     */
    public const MODULE_CLASS_DIR     = 'src';
    public const MODULE_CLASS_PREPEND = 'Prepend';      // Common (ex _prepend.php)
    public const MODULE_CLASS_INSTALL = 'Install';      // Installation (ex _install.php)
    public const MODULE_CLASS_ADMIN   = 'Backend';      // Backend common (ex _admin.php)
    public const MODULE_CLASS_CONFIG  = 'Config';       // Module configuration (ex _config.php)
    public const MODULE_CLASS_MANAGE  = 'Manage';       // Module backend (ex index.php)
    public const MODULE_CLASS_PUPLIC  = 'Frontend';     // Module frontend (ex _public.php)
    public const MODULE_CLASS_XMLRPC  = 'Xmlrpc';       // Module XMLRPC services (ex _xmlrpc.php) - obsolete since 2.24

    // Properties

    /**
     * Safe mode activated?
     *
     * @var bool
     */
    protected $safe_mode = false;

    /**
     * Stack of modules paths
     *
     * @var array
     */
    protected $path;

    /**
     * Stack of modules
     *
     * @var        array
     */
    protected $defines = [];

    /**
     * Stack of error messages
     *
     * @var        array<string>
     */
    protected $errors = [];

    /**
     * Stack of modules ids
     *
     * @var        array
     */
    protected $modules_ids          = [];
    protected static $modules_files = ['init' => []];

    /**
     * Current deactivation mode
     *
     * @var        bool
     */
    protected $disabled_mode = false;

    /**
     * Current dc namespace
     *
     * @var string
     */
    protected $ns;

    /**
     * Current module
     *
     * @var dcModuleDefine
     */
    protected $define;

    /**
     * Current module identifier
     *
     * @var string|null
     */
    protected $id;

    /**
     * Module root path (where _define.php is located)
     *
     * @var string|null
     */
    protected $mroot;

    /**
     * Current module php namespace
     *
     * @var string|null
     */
    protected $namespace;

    /**
     * Inclusion variables
     *
     * @var        array
     */
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

    /**
     * Superglobals array keys
     *
     * @var        array<string>
     */
    protected static $_k;

    /**
     * Superglobals key name
     *
     * @var        string
     */
    protected static $_n;

    /**
     * Module type to work with
     *
     * @var string|null
     */
    protected $type = null;

    /**
     * Get first ocurrence of a module's defined properties.
     *
     * This method always returns a dcModuleDefine class,
     * if module definition does not exist, it is created on the fly
     * with default properties.
     *
     * @param   string  $id         The module identifier
     * @param   array   $search     The search parameters
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
     * @param   array   $search     The search parameters
     * @param   bool    $to_array   Return arrays of modules properties
     *
     * @return  array<dcModuleDefine>   The modules defines or properties
     */
    public function getDefines(array $search = [], bool $to_array = false): array
    {
        $list = [];
        foreach ($this->defines as $module) {
            $add_it = true;
            foreach ($search as $key => $value) {
                if (substr($value, 0, 1) == '!') {
                    if ($module->get($key) === substr($value, 0, 1)) {
                        $add_it = false;

                        break;
                    }
                } elseif ($module->get($key) !== $value) {
                    $add_it = false;

                    break;
                }
            }
            if ($add_it) {
                if ($to_array) {
                    $list[$module->id] = $module->dump();
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
        // Sanitize current Dotclear version
        $dc_version = preg_replace('/\-dev.*$/', '', DC_VERSION);

        foreach ($this->getDefines() as $module) {
            if (!empty($module->requires)) {
                foreach ($module->requires as $dep) {
                    if (!is_array($dep)) {
                        $dep = [$dep];
                    }
                    $found = $this->getDefine($dep[0]);
                    // grab missing dependencies
                    if (!$found->isDefined() && $dep[0] != 'core') {
                        // module not present
                        $module->addMissing($dep[0], sprintf(__('Requires %s module which is not installed'), $dep[0]));
                    } elseif ((count($dep) > 1) && version_compare(($dep[0] == 'core' ? $dc_version : $found->version), $dep[1]) == -1) {
                        // module present, but version missing
                        if ($dep[0] == 'core') {
                            $module->addMissing($dep[0], sprintf(
                                __('Requires Dotclear version %s, but version %s is installed'),
                                $dep[1],
                                $dc_version
                            ));
                        } else {
                            $module->addMissing($dep[0], sprintf(
                                __('Requires %s module version %s, but version %s is installed'),
                                $dep[0],
                                $dep[1],
                                $found->version
                            ));
                        }
                    } elseif (($dep[0] != 'core') && $found->state != dcModuleDefine::STATE_ENABLED) {
                        // module disabled
                        $module->addMissing($dep[0], sprintf(__('Requires %s module which is disabled'), $dep[0]));
                    }
                    $found->addImplies($module->getId());
                }
            }
        }
        // Check modules that cannot be disabled
        foreach ($this->getDefines() as $module) {
            if (!empty($module->getImplies()) && $module->state == dcModuleDefine::STATE_ENABLED) {
                foreach ($module->getImplies() as $im) {
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
            dcPage::addWarningNotice($message, ['divtag' => true, 'with_ts' => false]);
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
     * @return     array   List of modules, may be an empty array
     */
    protected function parsePathModules(string $root): array
    {
        if (!is_dir($root) || !is_readable($root)) {
            return [];
        }

        $root = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (($d = @dir($root)) === false) {
            return [];
        }

        // Dir cache
        $stack = [];
        while (($entry = $d->read()) !== false) {
            $full_entry = $root . $entry;
            if ($entry !== '.' && $entry !== '..' && is_dir($full_entry) && file_exists($full_entry . DIRECTORY_SEPARATOR . self::MODULE_FILE_DEFINE)) {
                $stack[] = $entry;
            }
        }
        $d->close();

        return $stack;
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
                $full_entry = $root . $entry;
                if (!in_array($entry, self::$modules_files['init']) && file_exists($full_entry . DIRECTORY_SEPARATOR . self::MODULE_FILE_INIT)) {
                    self::$modules_files['init'][] = $entry;
                    ob_start();
                    require $full_entry . DIRECTORY_SEPARATOR . self::MODULE_FILE_INIT;
                    ob_end_clean();
                }
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
                dcCore::app()->autoload->addNamespace($this->namespace, $this->mroot . DIRECTORY_SEPARATOR . self::MODULE_CLASS_DIR);

                $module_disabled = file_exists($full_entry . DIRECTORY_SEPARATOR . self::MODULE_FILE_DISABLED);
                $module_enabled  = !$module_disabled && !$this->safe_mode;
                if (!$module_enabled) {
                    $this->disabled_mode = true;
                }

                ob_start();
                require $full_entry . DIRECTORY_SEPARATOR . self::MODULE_FILE_DEFINE;
                ob_end_clean();

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

        // Context loop
        foreach ($this->getDefines(['state' => dcModuleDefine::STATE_ENABLED]) as $module) {
            # Load translation and _prepend
            $ret = '';

            // by class name
            $class = $module->namespace . Autoloader::NS_SEP . self::MODULE_CLASS_PREPEND;
            if (!empty($module->namespace) && class_exists($class)) {
                $ret = $class::init() ? $class::process() : '';
            // by file name
            } elseif (file_exists($module->root . DIRECTORY_SEPARATOR . self::MODULE_FILE_PREPEND)) {
                $ret = $this->loadModuleFile($module->root . DIRECTORY_SEPARATOR . self::MODULE_FILE_PREPEND);
            }

            if (is_null($ret)) {
                // If _prepend.php file returns null (ie. it has a void return statement)
                $ignored[] = $module->getId();

                continue;
            }
            unset($ret);

            $this->loadModuleL10N($module->getId(), $lang, 'main');
            if ($ns == 'admin') {
                $this->loadModuleL10Nresources($module->getId(), $lang);
                dcCore::app()->adminurl->register('admin.plugin.' . $module->getId(), 'plugin.php', ['p' => $module->getId()]);
            }
        }

        // Give opportunity to do something before loading context (admin,public,xmlrpc) files
        dcCore::app()->callBehavior('coreBeforeLoadingNsFilesV2', $this, $lang);

        // Load module context
        foreach ($this->getDefines(['state' => dcModuleDefine::STATE_ENABLED]) as $module) {
            if (!in_array($module->getId(), $ignored)) {
                // Load ns_file
                $this->loadNsFile($module->getId(), $ns);
            }
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
            ob_start();
            if (!in_array($id, self::$modules_files['init']) && file_exists($dir . DIRECTORY_SEPARATOR . self::MODULE_FILE_INIT)) {
                self::$modules_files['init'][] = $id;
                require $dir . DIRECTORY_SEPARATOR . self::MODULE_FILE_INIT;
            }
            require $dir . DIRECTORY_SEPARATOR . self::MODULE_FILE_DEFINE;
            ob_end_clean();
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
        $zip = new fileUnzip($zip_file);
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
                if ($cur_define->isDefined() && (defined('DC_DEV') && DC_DEV === true || dcUtils::versionsCompare($new_defines[0]->get('version'), $cur_define->get('version'), '>', true))) {
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

                throw new Exception(sprintf(__('Unable to read new %s file', self::MODULE_FILE_DEFINE)));
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
                $install = $this->loadModuleFile($module->root . DIRECTORY_SEPARATOR . self::MODULE_FILE_INSTALL);
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
        $module = $this->getDefine($id, ['state' => dcModuleDefine::STATE_ENABLED]);
        if ($lang && $module->isDefined()) {
            $lfile = $module->root . DIRECTORY_SEPARATOR . 'locales' . DIRECTORY_SEPARATOR . '%s' . DIRECTORY_SEPARATOR . '%s';
            if (l10n::set(sprintf($lfile, $lang, $file)) === false && $lang != 'en') {
                l10n::set(sprintf($lfile, 'en', $file));
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
            if ($file = l10n::getFilePath($module->root . DIRECTORY_SEPARATOR . 'locales', 'resources.php', $lang)) {
                $this->loadModuleFile($file);
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
     * @param      string  $ns
     */
    public function loadNsFiles(?string $ns = null): void
    {
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
        if (!$module->isDefined() || !in_array($ns, ['admin', 'public', 'xmlrpc'])) {
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
            $this->loadModuleFile($module->root . DIRECTORY_SEPARATOR . $file);
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
        if (!class_exists($class) || !is_subclass_of($class, 'dcNsProcess', true)) {
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
     * @return     array  The errors.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Loads a module file.
     *
     * @param      string  $________  Module filename
     * @param      bool    $catch     Should catch output to prevent hacked/corrupted modules
     *
     * @return     mixed
     */
    protected function loadModuleFile(string $________, bool $catch = true)
    {
        if (!file_exists($________)) {
            return;
        }

        self::$_k = array_keys($GLOBALS);

        foreach (self::$_k as self::$_n) {
            if (!in_array(self::$_n, self::$superglobals)) {
                global ${self::$_n};
            }
        }

        if ($catch) {
            // Catch ouput to prevents hacked or corrupted modules
            ob_start();
            $ret = require $________;
            ob_end_clean();

            return $ret;
        }

        return require $________;
    }
}
