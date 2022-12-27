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
     * Default module priority
     *
     * @var        int
     */
    public const DEFAULT_PRIORITY = 1000;

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
    public const MODULE_FILE_PUBLIC   = '_public.php';
    public const MODULE_FILE_XMLRPC   = '_xmlrpc.php';
    public const MODULE_FILE_DISABLED = '_disabled';

    // Properties

    /**
     * Safe mode activated?
     *
     * @var bool
     */
    public $safe_mode = false;

    /**
     * Stack of modules paths
     *
     * @var array
     */
    protected $path;

    /**
     * Current namespace
     *
     * @var string
     */
    protected $ns;

    /**
     * Stack of enabled modules
     *
     * @var        array
     */
    protected $modules = [];

    /**
     * Stack of disabled modules
     *
     * @var        array
     */
    protected $disabled = [];

    /**
     * Stack of hard disabled modules (_disabled file in plugin root dir)
     *
     * @var        array
     */
    protected $hard_disabled = [];

    /**
     * Stack of soft disabled modules (safe mode enabled but no _disabled file in plugin root dir)
     *
     * @var        array
     */
    protected $soft_disabled = [];

    /**
     * Stack of error messages
     *
     * @var        array<string>
     */
    protected $errors = [];

    /**
     * Stack of modules name
     *
     * @var        array
     */
    protected $modules_names = [];

    /**
     * Stack of all modules
     *
     * @var        array
     */
    protected $all_modules = [];

    /**
     * Current deactivation mode
     *
     * @var        bool
     */
    protected $disabled_mode = false;

    /**
     * Current disabled module info
     *
     * @var        array
     */
    protected $disabled_meta = [];

    /**
     * Stack of modules to disable
     *
     * Each item contains ['name' => module ID, 'reason' => reason of deactivation]
     *
     * @var        array(<array>(<string>, <string>))
     */
    protected $to_disable = [];

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
     * Module type
     *
     * @var string|null
     */
    protected static $type = null;

    /**
     * Checks all modules dependencies
     *
     * Fills in the following information in module :
     *
     *  - cannot_enable : list reasons why module cannot be enabled. Not set if module can be enabled
     *
     *  - cannot_disable : list reasons why module cannot be disabled. Not set if module can be disabled
     *
     *  - implies : reverse dependencies
     */
    public function checkDependencies(): void
    {
        // Sanitize current Dotclear version
        $dc_version = preg_replace('/\-dev.*$/', '', DC_VERSION);

        $this->to_disable = [];
        foreach ($this->all_modules as $k => &$m) {
            if (isset($m['requires'])) {
                $missing = [];
                foreach ($m['requires'] as &$dep) {
                    if (!is_array($dep)) {
                        $dep = [$dep];
                    }
                    // grab missing dependencies
                    if (!isset($this->all_modules[$dep[0]]) && ($dep[0] != 'core')) {
                        // module not present
                        $missing[$dep[0]] = sprintf(__('Requires %s module which is not installed'), $dep[0]);
                    } elseif ((count($dep) > 1) && version_compare(($dep[0] == 'core' ? $dc_version : $this->all_modules[$dep[0]]['version']), $dep[1]) == -1) {
                        // module present, but version missing
                        if ($dep[0] == 'core') {
                            $missing[$dep[0]] = sprintf(
                                __('Requires Dotclear version %s, but version %s is installed'),
                                $dep[1],
                                $dc_version
                            );
                        } else {
                            $missing[$dep[0]] = sprintf(
                                __('Requires %s module version %s, but version %s is installed'),
                                $dep[0],
                                $dep[1],
                                $this->all_modules[$dep[0]]['version']
                            );
                        }
                    } elseif (($dep[0] != 'core') && !$this->all_modules[$dep[0]]['enabled']) {
                        // module disabled
                        $missing[$dep[0]] = sprintf(__('Requires %s module which is disabled'), $dep[0]);
                    }
                    $this->all_modules[$dep[0]]['implies'][] = $k;
                }
                if (count($missing)) {
                    $m['cannot_enable'] = $missing;
                    if ($m['enabled']) {
                        $this->to_disable[] = ['name' => $k, 'reason' => $missing];
                    }
                }
            }
        }
        // Check modules that cannot be disabled
        foreach ($this->modules as $k => &$m) {
            if (isset($m['implies']) && $m['enabled']) {
                foreach ($m['implies'] as $im) {
                    if (isset($this->all_modules[$im]) && $this->all_modules[$im]['enabled']) {
                        $m['cannot_disable'][] = $im;
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
        foreach ($this->to_disable as $module) {
            try {
                $this->deactivateModule($module['name']);
                $reason[] = sprintf('<li>%s : %s</li>', $module['name'], join(',', $module['reason']));
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
            http::redirect($url);

            return true;
        }

        return false;
    }

    /**
     * Should run in safe mode?
     *
     * @return     bool
     */
    public function safeMode(): bool
    {
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
        $this->path = explode(PATH_SEPARATOR, $path);
        $this->ns   = $ns;

        $this->safe_mode = isset($_SESSION['sess_safe_mode']) && $_SESSION['sess_safe_mode'];

        $ignored = [];

        // First loop to init
        foreach ($this->path as $root) {
            $stack = $this->parsePathModules($root);

            // Init loop
            $root = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            foreach ($stack as $entry) {
                $full_entry = $root . $entry;
                if (file_exists($full_entry . DIRECTORY_SEPARATOR . self::MODULE_FILE_INIT)) {
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
                $full_entry      = $root . $entry;
                $this->id        = $entry;
                $this->mroot     = $full_entry;
                $module_disabled = file_exists($full_entry . DIRECTORY_SEPARATOR . self::MODULE_FILE_DISABLED);
                $module_enabled  = !$module_disabled && !$this->safe_mode;
                if (!$module_enabled) {
                    $this->disabled_mode = true;
                }
                ob_start();
                require $full_entry . DIRECTORY_SEPARATOR . self::MODULE_FILE_DEFINE;
                ob_end_clean();
                if ($module_enabled) {
                    $this->all_modules[$entry] = &$this->modules[$entry];
                } else {
                    $this->disabled_mode       = false;
                    $this->disabled[$entry]    = $this->disabled_meta;
                    $this->all_modules[$entry] = &$this->disabled[$entry];
                    if ($module_disabled) {
                        // Add module in hard disabled stack (_disabled file exists)
                        $this->hard_disabled[$entry] = $this->disabled_meta;
                    } else {
                        // Add module in soft disabled stack (safe mode enabled)
                        $this->soft_disabled[$entry] = $this->disabled_meta;
                    }
                }
                $this->id    = null;
                $this->mroot = null;
            }
        }
        $this->checkDependencies();

        // Sort plugins
        //
        // Plugins with lower priority are loaded first (in alphabetic order for the same priority)
        // Plugins without priority are set to dcModules::DEFAULT_PRIORITY (1000)
        uasort($this->modules, [$this, 'sortModules']);

        // Context loop
        foreach ($this->modules as $id => $m) {
            # Load translation and _prepend
            if (isset($m['root']) && file_exists($m['root'] . DIRECTORY_SEPARATOR . self::MODULE_FILE_PREPEND)) {
                $r = $this->loadModuleFile($m['root'] . DIRECTORY_SEPARATOR . self::MODULE_FILE_PREPEND);

                if (is_null($r)) {
                    // If _prepend.php file returns null (ie. it has a void return statement)
                    $ignored[] = $id;

                    continue;
                }
                unset($r);
            }

            $this->loadModuleL10N($id, $lang, 'main');
            if ($ns == 'admin') {
                $this->loadModuleL10Nresources($id, $lang);
                dcCore::app()->adminurl->register('admin.plugin.' . $id, 'plugin.php', ['p' => $id]);
            }
        }

        // Give opportunity to do something before loading context (admin,public,xmlrpc) files
        dcCore::app()->callBehavior('coreBeforeLoadingNsFilesV2', $this, $lang);

        foreach (array_keys($this->modules) as $id) {
            if (!in_array($id, $ignored)) {
                // Load ns_file
                $this->loadNsFile($id, $ns);
            }
        }
    }

    /**
     * Sort callback
     *
     * @param      array      $first_module       1st module
     * @param      array      $second_module      2nd module
     *
     * @return     int
     */
    private function sortModules(?array $first_module, ?array $second_module): int
    {
        if (!$first_module || !$second_module) {
            // One or both of modules is not defined
            return 0;
        }

        $first  = $first_module['priority']  ?? dcModules::DEFAULT_PRIORITY;
        $second = $second_module['priority'] ?? dcModules::DEFAULT_PRIORITY;

        if ($first === $second) {
            // Use alphabetic order
            return strcasecmp($first_module['name'], $second_module['name']);
        }

        // Compare priorities
        return $first <=> $second;
    }

    /**
     * Load the _define.php file of the given module
     *
     * @param      string  $dir    The dir
     * @param      string  $id     The module identifier
     */
    public function requireDefine(string $dir, string $id)
    {
        if (file_exists($dir . DIRECTORY_SEPARATOR . self::MODULE_FILE_DEFINE)) {
            $this->id = $id;
            ob_start();
            if (file_exists($dir . DIRECTORY_SEPARATOR . self::MODULE_FILE_INIT)) {
                require $dir . DIRECTORY_SEPARATOR . self::MODULE_FILE_INIT;
            }
            require $dir . DIRECTORY_SEPARATOR . self::MODULE_FILE_DEFINE;
            ob_end_clean();
            $this->id = null;
        }
    }

    /**
     * This method registers a module in modules list. You should use this to
     * register a new module.
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
        if ($this->disabled_mode) {
            $this->disabled_meta = array_merge(
                $properties,
                [
                    'root'          => $this->mroot,
                    'name'          => $name,
                    'desc'          => $desc,
                    'author'        => $author,
                    'version'       => $version,
                    'enabled'       => false,
                    'root_writable' => is_writable($this->mroot),
                ]
            );

            return;
        }
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

        // Default module properties
        $properties = array_merge(
            [
                'permissions'       => null,
                'priority'          => dcModules::DEFAULT_PRIORITY,
                'standalone_config' => false,
                'type'              => null,
                'enabled'           => true,
                'requires'          => [],
                'settings'          => [],
                'repository'        => '',
            ],
            $properties
        );

        // Check module type
        if (self::$type !== null && $properties['type'] !== null && $properties['type'] !== self::$type) {
            $this->errors[] = sprintf(
                __('Module "%s" has type "%s" that mismatch required module type "%s".'),
                '<strong>' . html::escapeHTML($name) . '</strong>',
                '<em>' . html::escapeHTML($properties['type']) . '</em>',
                '<em>' . html::escapeHTML(self::$type) . '</em>'
            );

            return;
        }

        // Check module perms on admin side
        $permissions = $properties['permissions'];
        if ($this->ns === 'admin') {
            if (($permissions == '' && !dcCore::app()->auth->isSuperAdmin()) || (!dcCore::app()->auth->check($permissions, dcCore::app()->blog->id))) {
                return;
            }
        }

        # Check module install on multiple path
        if ($this->id) {
            $module_exists    = array_key_exists($name, $this->modules_names);
            $module_overwrite = $module_exists ? version_compare($this->modules_names[$name], $version, '<') : false;
            if (!$module_exists || $module_overwrite) {
                $this->modules_names[$name] = $version;
                $this->modules[$this->id]   = array_merge(
                    $properties,
                    [
                        'root'          => $this->mroot,
                        'name'          => $name,
                        'desc'          => $desc,
                        'author'        => $author,
                        'version'       => $version,
                        'root_writable' => is_writable($this->mroot ?? ''),
                    ]
                );
            } else {
                $path1 = path::real($this->moduleInfo($this->id, 'root') ?? '');
                $path2 = path::real($this->mroot ?? '');

                $this->errors[] = sprintf(
                    __('Module "%s" is installed twice in "%s" and "%s".'),
                    '<strong>' . $name . '</strong>',
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
        $this->modules       = [];
        $this->modules_names = [];
        $this->errors        = [];
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
            $has_define  = $zip->hasFile($define);
        } else {
            $target      = dirname($zip_file) . DIRECTORY_SEPARATOR . preg_replace('/\.([^.]+)$/', '', basename($zip_file));
            $destination = $target;
            $define      = self::MODULE_FILE_DEFINE;
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
                files::makeDir($destination, true);

                $sandbox = clone $modules;
                // Force normal mode
                $sandbox->safe_mode = false;

                if ($zip->hasFile($zip->getRootDir() . '/' . self::MODULE_FILE_INIT)) {
                    $zip->unzip($define, $target . DIRECTORY_SEPARATOR . self::MODULE_FILE_INIT);
                }
                $zip->unzip($define, $target . DIRECTORY_SEPARATOR . self::MODULE_FILE_DEFINE);

                $sandbox->resetModulesList();
                $sandbox->requireDefine($target, basename($destination));
                if ($zip->hasFile($zip->getRootDir() . '/' . self::MODULE_FILE_INIT)) {
                    unlink($target . DIRECTORY_SEPARATOR . self::MODULE_FILE_INIT);
                }
                unlink($target . DIRECTORY_SEPARATOR . self::MODULE_FILE_DEFINE);

                $new_errors = $sandbox->getErrors();
                if (!empty($new_errors)) {
                    $new_errors = implode(" \n", $new_errors);

                    throw new Exception($new_errors);
                }

                files::deltree($destination);
            } catch (Exception $e) {
                $zip->close();
                unlink($zip_file);
                files::deltree($destination);

                throw new Exception($e->getMessage());
            }
        } else {
            //
            $sandbox = clone $modules;
            // Force normal mode
            $sandbox->safe_mode = false;

            if ($zip->hasFile($zip->getRootDir() . '/' . self::MODULE_FILE_INIT)) {
                $zip->unzip($define, $target . DIRECTORY_SEPARATOR . self::MODULE_FILE_INIT);
            }
            $zip->unzip($define, $target . DIRECTORY_SEPARATOR . self::MODULE_FILE_DEFINE);

            $sandbox->resetModulesList();
            $sandbox->requireDefine($target, basename($destination));
            if ($zip->hasFile($zip->getRootDir() . '/' . self::MODULE_FILE_INIT)) {
                unlink($target . DIRECTORY_SEPARATOR . self::MODULE_FILE_INIT);
            }
            unlink($target . DIRECTORY_SEPARATOR . self::MODULE_FILE_DEFINE);
            $new_modules = $sandbox->getModules();

            if (!empty($new_modules)) {
                // Check if module is disabled
                $module_disabled = file_exists($destination . DIRECTORY_SEPARATOR . self::MODULE_FILE_DISABLED);

                $tmp        = array_keys($new_modules);
                $id         = $tmp[0];
                $cur_module = $modules->getAnyModules($id);
                if (!empty($cur_module) && (defined('DC_DEV') && DC_DEV === true || dcUtils::versionsCompare($new_modules[$id]['version'], $cur_module['version'], '>', true))) {
                    // delete old module
                    if (!files::deltree($destination)) {
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
        foreach (array_keys($this->modules) as $id) {
            $ret = $this->installModule($id, $msg);
            if ($ret === true) {
                $res['success'][$id] = true;
            } elseif ($ret === false) {
                $res['failure'][$id] = $msg;
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
        if (!isset($this->modules[$id])) {
            return;
        }

        try {
            $install = $this->loadModuleFile($this->modules[$id]['root'] . DIRECTORY_SEPARATOR . self::MODULE_FILE_INSTALL);
            if ($install === true || $install === null) {
                // Register new version if necessary
                $old_version = dcCore::app()->getVersion($id);
                $new_version = $this->modules[$id]['version'];
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
        if ($disabled) {
            $stack = $this->disabled;
        } else {
            $stack = $this->modules;
        }

        if (!isset($stack[$id])) {
            throw new Exception(__('No such module.'));
        }

        if (!files::deltree($stack[$id]['root'])) {
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
        if ($this->safe_mode) {
            $stack = &$this->soft_disabled;
        } else {
            $stack = &$this->modules;
        }

        if (!isset($stack[$id])) {
            throw new Exception(__('No such module.'));
        }

        if (!$stack[$id]['root_writable']) {
            throw new Exception(__('Cannot deactivate plugin.'));
        }

        if (@file_put_contents($stack[$id]['root'] . DIRECTORY_SEPARATOR . self::MODULE_FILE_DISABLED, '')) {
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
        if (!isset($this->disabled[$id])) {
            throw new Exception(__('No such module.'));
        }

        if (!$this->disabled[$id]['root_writable']) {
            throw new Exception(__('Cannot activate plugin.'));
        }

        if (@unlink($this->disabled[$id]['root'] . DIRECTORY_SEPARATOR . self::MODULE_FILE_DISABLED) === false) {
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
        if ($lang && isset($this->modules[$id])) {
            $lfile = $this->modules[$id]['root'] . DIRECTORY_SEPARATOR . 'locales' . DIRECTORY_SEPARATOR . '%s' . DIRECTORY_SEPARATOR . '%s';
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
        if ($lang && isset($this->modules[$id])) {
            if ($f = l10n::getFilePath($this->modules[$id]['root'] . DIRECTORY_SEPARATOR . 'locales', 'resources.php', $lang)) {
                $this->loadModuleFile($f);
            }
        }
    }

    /**
     * Returns all modules associative array or only one module if <var>$id</var> is present.
     *
     * @param      string  $id     The optionnal module identifier
     *
     * @return     array  The module(s).
     */
    public function getModules(?string $id = null): array
    {
        if ($this->safe_mode) {
            $stack = $this->soft_disabled;
        } else {
            $stack = $this->modules;
        }

        if ($id && isset($stack[$id])) {
            return $stack[$id];
        }

        return $stack;
    }

    /**
     * Gets all modules (whatever are their statuses) or only one module if <var>$id</var> is present.
     *
     * @param      string  $id     The optionnal module identifier
     *
     * @return     array  The module(s).
     */
    public function getAnyModules(?string $id = null): array
    {
        if ($id && isset($this->all_modules[$id])) {
            return $this->all_modules[$id];
        }

        return $this->all_modules;
    }

    /**
     * Determines if module exists.
     *
     * @param      string  $id     The module identifier
     *
     * @return     bool  True if module exists, False otherwise.
     */
    public function moduleExists(string $id): bool
    {
        return isset($this->modules[$id]);
    }

    /**
     * Gets the disabled modules.
     *
     * @return     array  The disabled modules.
     */
    public function getDisabledModules(): array
    {
        return $this->disabled;
    }

    /**
     * Gets the hard disabled modules.
     *
     * @return     array  The hard disabled modules.
     */
    public function getHardDisabledModules(): array
    {
        return $this->hard_disabled;
    }

    /**
     * Gets the soft disabled modules (safe mode and not hard disabled).
     *
     * @return     array  The soft disabled modules.
     */
    public function getSoftDisabledModules(): array
    {
        return $this->soft_disabled;
    }

    /**
     * Returns root path for module with ID <var>$id</var>.
     *
     * @param      string  $id     The module identifier
     *
     * @return     mixed
     */
    public function moduleRoot(string $id)
    {
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
        return $this->modules[$id][$info] ?? null;
    }

    /**
     * Loads namespace <var>$ns</var> specific files for all modules.
     *
     * @param      string  $ns
     */
    public function loadNsFiles(?string $ns = null): void
    {
        foreach (array_keys($this->modules) as $k) {
            $this->loadNsFile($k, $ns);
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
        if (!isset($this->modules[$id])) {
            return;
        }
        switch ($ns) {
            case 'admin':
                $this->loadModuleFile($this->modules[$id]['root'] . DIRECTORY_SEPARATOR . self::MODULE_FILE_ADMIN);

                break;
            case 'public':
                $this->loadModuleFile($this->modules[$id]['root'] . DIRECTORY_SEPARATOR . self::MODULE_FILE_PUBLIC);

                break;
            case 'xmlrpc':
                $this->loadModuleFile($this->modules[$id]['root'] . DIRECTORY_SEPARATOR . self::MODULE_FILE_XMLRPC);

                break;
        }
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
