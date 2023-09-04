<?php
/**
 * Modules handler
 *
 * Provides an object to handle modules (themes or plugins).
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Module;

use Autoloader;
use dcDeprecated;
use dcUtils;
use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\File\Zip\Unzip;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\L10n;
use Dotclear\Interface\Module\ModulesInterface;
use Dotclear\Module\ModuleDefine;
use Exception;

class Modules implements ModulesInterface
{
    public const PACKAGE_INSTALLED = 1;
    public const PACKAGE_UPDATED = 2;

    public const MODULE_FILE_INSTALL = '_install.php';
    public const MODULE_FILE_INIT = '_init.php';
    public const MODULE_FILE_DEFINE = '_define.php';
    public const MODULE_FILE_PREPEND = '_prepend.php';
    public const MODULE_FILE_ADMIN = '_admin.php';
    public const MODULE_FILE_CONFIG = '_config.php';
    public const MODULE_FILE_MANAGE = 'index.php';
    public const MODULE_FILE_PUBLIC = '_public.php';
    public const MODULE_FILE_XMLRPC = '_xmlrpc.php';
    public const MODULE_FILE_DISABLED = '_disabled';
    public const MODULE_FILE_LOCKED = '_locked';

    public const MODULE_CLASS_DIR = 'src';
    public const MODULE_CLASS_PREPEND = 'Prepend';
    public const MODULE_CLASS_INSTALL = 'Install';
    public const MODULE_CLASS_ADMIN = 'Backend';
    public const MODULE_CLASS_CONFIG = 'Config';
    public const MODULE_CLASS_MANAGE = 'Manage';
    public const MODULE_CLASS_PUPLIC = 'Frontend';
    public const MODULE_CLASS_XMLRPC = 'Xmlrpc';

    /** @var    bool    Safe mode execution */
    protected $safe_mode = false;

    /** @var    array<int,string>   Stack of modules paths */
    protected $path = [];

    /** @var    array<int,ModuleDefine>     Stack of modules */
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

    /** @var    ModuleDefine    Current module Define */
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

    public function getDefine(string $id, array $search = []): ModuleDefine
    {
        $found = $this->getDefines(array_merge($search, ['id' => $id]));

        return empty($found) ? new ModuleDefine($id) : $found[0];
    }

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

    public function checkDependencies(ModuleDefine $module, $to_error = false): void
    {
        // Grab current Dotclear and PHP version
        $special = [
            'core' => preg_replace('/\-dev.*$/', '', DC_VERSION),
            'php'  => phpversion(),
        ];

        $optionnals = [];

        // module has required modules
        if (!empty($module->requires)) {
            foreach ($module->requires as $dep) {
                $msg = '';
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
                    // module not present, nor php or core, nor optionnal
                    $msg = sprintf(__('Requires %s module which is not installed'), $dep[0]);
                } elseif (($found->isDefined() || isset($special[$dep[0]])) && (count($dep) > 1) && version_compare(($special[$dep[0]] ?? $found->get('version')), $dep[1]) == -1) {
                    // module present or php or core, but version missing
                    if ($dep[0] == 'php') {
                        $dep[0] = 'PHP';
                        $dep_v  = $special['php'];
                    } elseif ($dep[0] == 'core') {
                        $dep[0] = 'Dotclear';
                        $dep_v  = $special['core'];
                    } else {
                        $dep_v = $found->get('version');
                    }
                    $msg = sprintf(
                        __('Requires %s version %s, but version %s is installed'),
                        $dep[0],
                        $dep[1],
                        $dep_v
                    );
                } elseif ($found->isDefined() && !isset($special[$dep[0]]) && $found->get('state') != ModuleDefine::STATE_ENABLED) {
                    // module disabled
                    $msg = sprintf(__('Requires %s module which is disabled'), $dep[0]);
                }
                if (!empty($msg)) {
                    $module->addMissing($dep[0], $msg);
                    if ($to_error) {
                        $this->errors[] = $msg;
                    }
                }
                $found->addImplies($module->getId());
            }
        }

        // Check modules that cannot be disabled

        // Add dependencies to modules that use current module
        if (!empty($module->getImplies()) && $module->get('state') == ModuleDefine::STATE_ENABLED) {
            foreach ($module->getImplies() as $im) {
                if (isset($optionnals[$im][$module->getId()])) {
                    continue;
                }
                foreach ($this->getDefines(['id' => $im]) as $found) {
                    if ($found->get('state') == ModuleDefine::STATE_ENABLED) {
                        $module->addUsing($im);
                    }
                }
            }
        }
        // Move in soft disabled state module with missing requirements
        if (!$this->safe_mode && !empty($module->getMissing()) && $module->get('state') == ModuleDefine::STATE_ENABLED) {
            $module->set('state', ModuleDefine::STATE_SOFT_DISABLED);
        }
    }

    public function disableDepModules(): array
    {
        if (isset($_GET['dep']) || $this->safe_mode) {
            // Avoid infinite redirects and do not hard disabled modules in safe_mode
            return [];
        }

        $reason = [];
        foreach ($this->defines as $module) {
            if (empty($module->getMissing()) || !in_array($module->get('state'), [ModuleDefine::STATE_ENABLED, ModuleDefine::STATE_SOFT_DISABLED])) {
                continue;
            }

            try {
                $this->deactivateModule($module->getId());
                $reason[] = sprintf('%s : %s', $module->get('name'), join(',', $module->getMissing()));
            } catch (Exception $e) {
                // Ignore exceptions
            }
        }

        return $reason;
    }

    public function safeMode(?bool $mode = null): bool
    {
        if (is_bool($mode)) {
            $this->safe_mode = $mode;
        }

        return $this->safe_mode;
    }

    /**
     * Get list of modules root path by directories.
     *
     * This keep track of previously scanned directories and return all of them.
     *
     * @param   array<int,string>   $paths  The modules directories to parse
     *
     * @return  array<string,array<int,string>>     List of modules by paths, if any
     */
    protected function parsePathModules(array $paths): array
    {
        foreach ($paths as $path) {
            $root = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

            // already scan
            if (isset($this->modules_paths[$root])) {
                continue;
            }

            if (!is_dir($root) || !is_readable($root) || ($d = @dir($root)) === false) {
                continue;
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
        }

        return $this->modules_paths;
    }

    public function loadModules(string $path, ?string $ns = null, ?string $lang = null): void
    {
        $this->path      = explode(PATH_SEPARATOR, $path);
        $this->ns        = $ns;
        $this->safe_mode = isset($_SESSION['sess_safe_mode']) && $_SESSION['sess_safe_mode'];

        $ignored = [];

        // First loop to init
        foreach ($this->parsePathModules($this->path) as $root => $stack) {
            // Init loop
            foreach ($stack as $entry) {
                $this->loadModuleInit($entry, $root . $entry);
            }
        }

        // Second loop to register
        foreach ($this->parsePathModules($this->path) as $root => $stack) {
            // Register loop
            foreach ($stack as $entry) {
                $full_entry  = $root . $entry;
                $this->id    = $entry;
                $this->mroot = $full_entry;

                // Module namespace
                $this->namespace = implode(Autoloader::NS_SEP, ['', 'Dotclear', ucfirst($this->type ?? ModuleDefine::DEFAULT_TYPE), $this->id]);
                Autoloader::me()->addNamespace($this->namespace, $this->mroot . DIRECTORY_SEPARATOR . self::MODULE_CLASS_DIR);

                // Module is marked as disabled (with _disabled file)
                $module_disabled = file_exists($full_entry . DIRECTORY_SEPARATOR . self::MODULE_FILE_DISABLED);
                $module_enabled  = !$module_disabled && !$this->safe_mode;
                if (!$module_enabled) {
                    $this->disabled_mode = true;
                }

                // Load module define
                $this->loadModuleFile($full_entry . DIRECTORY_SEPARATOR . self::MODULE_FILE_DEFINE);

                if (!$module_enabled) {
                    $this->disabled_mode = false;
                    $this->define->set('state', $module_disabled ? ModuleDefine::STATE_HARD_DISABLED : ModuleDefine::STATE_SOFT_DISABLED);
                }
                $this->id        = null;
                $this->mroot     = null;
                $this->namespace = null;
            }
        }

        // Check modules dependencies
        foreach ($this->defines as $module) {
            $this->checkDependencies($module);
        }

        // Sort modules by priority
        uasort($this->defines, fn ($a, $b) => $a->get('priority') <=> $b->get('priority'));

        // Prepend loop
        foreach ($this->defines as $module) {
            // Only on enabled modules
            if ($module->get('state') != ModuleDefine::STATE_ENABLED) {
                continue;
            }
            $ret = true;

            // by class name
            $class = $module->get('namespace') . Autoloader::NS_SEP . self::MODULE_CLASS_PREPEND;
            if (!empty($module->get('namespace')) && class_exists($class)) {
                $ret = $class::init() ? $class::process() : false;
                // by file name
            } elseif (file_exists($module->get('root') . DIRECTORY_SEPARATOR . self::MODULE_FILE_PREPEND)) {
                $ret = $this->loadModuleFile($module->get('root') . DIRECTORY_SEPARATOR . self::MODULE_FILE_PREPEND, true);
            }

            if ($ret !== true) {
                // If _prepend.php file returns null (ie. it has a void return statement)
                $ignored[] = $module->getId();

                continue;
            }
        }

        // Load all modules main translation (new loop as it may required Proxy plugin)
        foreach ($this->defines as $module) {
            $this->loadModuleL10N($module->getId(), $lang, 'main');
        }

        // Load modules context
        if (!empty($ns)) {
            // Give opportunity to do something before loading context (admin,public,xmlrpc) files
            # --BEHAVIOR-- coreBeforeLoadingNsFilesV2 -- Modules, string|null
            App::behavior()->callBehavior('coreBeforeLoadingNsFilesV2', $this, $lang);

            $this->loadModulesContext($ignored, $ns, $lang);
        }
    }

    /**
     * Load modules context.
     *
     * This always creates admin URL as some old plugins _prepend does not return true,
     * this may change in near futur. (2.27)
     *
     * @param   array<int,string>   $ignored    The modules to ignore
     * @param   string              $ns         The namespace (context as 'public', 'admin', ...)
     * @param   null|string         $lang       The language
     */
    protected function loadModulesContext(array $ignored, string $ns, ?string $lang): void
    {
        if ($ns === 'admin') {
            $base   = App::backend()->url->getBase('admin.plugin');
            $params = App::backend()->url->getParams('admin.plugin');
        }

        foreach ($this->defines as $module) {
            // Only enabled modules (in near futur module _prepend must return true too)
            if ($module->get('state') != ModuleDefine::STATE_ENABLED) { //} || in_array($module->getId(), $ignored)) {
                continue;
            }
            if ($ns === 'admin') {
                // This check may be removed in near futur
                if (!in_array($module->getId(), $ignored)) {
                    // Load module resources files
                    $this->loadModuleL10Nresources($module->getId(), $lang);
                }
                // Create module admin URL
                App::backend()->url->register(
                    'admin.plugin.' . $module->getId(),
                    $base,                                  // @phpstan-ignore-line
                    [...$params, 'p' => $module->getId()]   // @phpstan-ignore-line
                );
            }
            // This check may be removed in near futur
            if (!in_array($module->getId(), $ignored)) {
                // Load module ns_file
                $this->loadNsFile($module->getId(), $ns);
            }
        }
    }

    public function requireDefine(string $dir, string $id): void
    {
        $this->id = $id;
        if (file_exists($dir . DIRECTORY_SEPARATOR . self::MODULE_FILE_DEFINE)) {
            $this->loadModuleInit($id, $dir);
            $this->loadModuleFile($dir . DIRECTORY_SEPARATOR . self::MODULE_FILE_DEFINE);
        }
        $this->id = null;
    }

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
                $define->set($k, $v);
            }
        }

        $this->defineModule($define);
    }

    /**
     * Define a module.
     *
     * Create, clean up, check a module Define.
     *
     * @param   ModuleDefine    $define     The module Define
     */
    protected function defineModule(ModuleDefine $define): void
    {
        $this->define = $define;

        $this->define
            ->set('state', $this->disabled_mode ? ModuleDefine::STATE_INIT_DISABLED : ModuleDefine::STATE_ENABLED)
            ->set('root', $this->mroot)
            ->set('namespace', $this->namespace)
            ->set('root_writable', !is_null($this->mroot) && is_writable($this->mroot))
        ;

        // dc < 2.25, module type was optionnal
        if ($this->define->get('type') == ModuleDefine::DEFAULT_TYPE) {
            $this->define->set('type', $this->type);
        }

        // dc < 2.26, priority could be negative
        if ((int) $this->define->get('priority') < 0) {
            $this->define->set('priority', 1);
        }

        $this->define->set('distributed', in_array($this->define->getId(), explode(',', $this->define->get('type') == 'theme' ? DC_DISTRIB_THEMES : DC_DISTRIB_PLUGINS)));

        // try to extract dc_min for easy reading
        if (empty($this->define->get('dc_min')) && !empty($this->define->get('requires'))) {
            foreach ($this->define->get('requires') as $dep) {
                if (is_array($dep) && count($dep) == 2 && $dep[0] == 'core') {
                    $this->define->set('dc_min', $dep[1]);
                }
            }
        }

        if (!$this->disabled_mode) {
            // Check module type
            if ($this->type !== null && $this->define->get('type') !== $this->type) {
                $this->errors[] = sprintf(
                    __('Module "%s" has type "%s" that mismatch required module type "%s".'),
                    '<strong>' . Html::escapeHTML($this->define->get('name')) . '</strong>',
                    '<em>' . Html::escapeHTML($this->define->get('type')) . '</em>',
                    '<em>' . Html::escapeHTML($this->type) . '</em>'
                );

                return;
            }

            // Check module perms on admin side
            $permissions = $this->define->get('permissions');
            if ($this->ns === 'admin') {
                if (($permissions == '' && !App::auth()->isSuperAdmin()) || (!App::auth()->check($permissions, App::blog()->id()))) {
                    return;
                }
            }
        }

        # Check module install on multiple path
        if ($this->id) {
            $module_exists    = array_key_exists($this->id, $this->modules_ids);
            $module_overwrite = $module_exists ? version_compare($this->modules_ids[$this->id], $this->define->get('version'), '<') : false;
            // Module exists => claim that
            if ($module_exists) {
                $path1 = Path::real($this->moduleInfo($this->id, 'root') ?? '');
                $path2 = Path::real($this->mroot ?? '');

                $this->errors[] = sprintf(
                    __('Module "%s" is installed twice in "%s" and "%s".'),
                    '<strong>' . $this->define->get('name') . '</strong>',
                    '<em>' . $path1 . '</em>',
                    '<em>' . $path2 . '</em>'
                );
            }
            // Module is more recent than existing one => delete existing one
            if ($module_overwrite) {
                foreach ($this->defines as $k => $define) {
                    if ($define->getId() == $this->id) {
                        unset($this->defines[$k]);
                    }
                }
            }
            // Module is unique or more recent => add it
            if (!$module_exists || $module_overwrite) {
                $this->modules_ids[$this->id] = $this->define->get('version');
                $this->defines[]              = $this->define;
            }
        }
    }

    public function resetModulesList(): void
    {
        $this->defines     = [];
        $this->modules_ids = [];
        $this->errors      = [];
    }

    public function isEmpty(): bool
    {
        return empty($this->defines);
    }

    public static function installPackage(string $zip_file, ModulesInterface &$modules): int
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
                $modules->checkDependencies($sandbox->getDefine(basename($destination)), true);
                if ($zip->hasFile($init)) {
                    unlink($target . DIRECTORY_SEPARATOR . self::MODULE_FILE_INIT);
                }

                unlink($target . DIRECTORY_SEPARATOR . self::MODULE_FILE_DEFINE);

                $new_errors = $modules->getErrors();
                if (!empty($new_errors)) {
                    $new_errors = implode(" \n", $new_errors);

                    throw new Exception(sprintf(__('Module is not installed: %s'), $new_errors));
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
            $modules->checkDependencies($sandbox->getDefine(basename($destination)), true);
            if ($zip->hasFile($init)) {
                unlink($target . DIRECTORY_SEPARATOR . self::MODULE_FILE_INIT);
            }

            unlink($target . DIRECTORY_SEPARATOR . self::MODULE_FILE_DEFINE);

            $new_errors = $modules->getErrors();
            if (!empty($new_errors)) {
                $new_errors = implode(" \n", $new_errors);

                throw new Exception(sprintf(__('Module is not installed: %s'), $new_errors));
            }

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

    public function installModules(): array
    {
        $res = [
            'success' => [],
            'failure' => [],
        ];
        $msg = '';
        foreach ($this->defines as $module) {
            if ($module->get('state') != ModuleDefine::STATE_ENABLED) {
                continue;
            }
            $ret = $this->installModule($module->getId(), $msg);
            if ($ret === true) {
                $res['success'][$module->getId()] = true;
            } elseif ($ret === false) {
                $res['failure'][$module->getId()] = $msg;
            }
        }

        return $res;
    }

    public function installModule(string $id, string &$msg): ?bool
    {
        $module = $this->getDefine($id);

        if (!$module->isDefined() || $module->get('state') != ModuleDefine::STATE_ENABLED) {
            return null;
        }

        try {
            // by class name
            $install = !empty($this->loadNsClass($id, self::MODULE_CLASS_INSTALL));
            // by file name
            if (!$install) {
                $install = $this->loadModuleFile($module->get('root') . DIRECTORY_SEPARATOR . self::MODULE_FILE_INSTALL, true);
            }

            if ($install === true || $install === null) {
                // Register new version if necessary
                $old_version = App::version()->getVersion($id);
                $new_version = $module->get('version');
                if (version_compare($old_version, $new_version, '<')) {
                    // Register new version
                    App::version()->setVersion($id, $new_version);
                }

                if ($install === true) {
                    return true;
                }
            }
        } catch (Exception $e) {
            $msg = $e->getMessage();

            return false;
        }

        return null;
    }

    public function deleteModule(string $id, bool $disabled = false): void
    {
        $module = $this->getDefine($id, ['state' => ($disabled ? '!' : '') . ModuleDefine::STATE_ENABLED]);

        if (!$module->isDefined()) {
            throw new Exception(__('No such module.'));
        }

        if (!Files::deltree($module->get('root'))) {
            throw new Exception(__('Cannot remove module files'));
        }
    }

    public function deactivateModule(string $id): void
    {
        $module = $this->getDefine($id);

        if (!$module->isDefined()) {
            throw new Exception(__('No such module.'));
        }

        if (!$module->get('root_writable') || !in_array($module->get('state'), [ModuleDefine::STATE_SOFT_DISABLED, ModuleDefine::STATE_ENABLED])) {
            throw new Exception(__('Cannot deactivate plugin.'));
        }

        if (@file_put_contents($module->get('root') . DIRECTORY_SEPARATOR . self::MODULE_FILE_DISABLED, '')) {
            throw new Exception(__('Cannot deactivate plugin.'));
        }
    }

    public function activateModule(string $id): void
    {
        $module = $this->getDefine($id, ['state' => '!' . ModuleDefine::STATE_ENABLED]);

        if (!$module->isDefined()) {
            throw new Exception(__('No such module.'));
        }

        if (!$module->get('root_writable')) {
            throw new Exception(__('Cannot activate plugin.'));
        }

        if (@unlink($module->get('root') . DIRECTORY_SEPARATOR . self::MODULE_FILE_DISABLED) === false) {
            throw new Exception(__('Cannot activate plugin.'));
        }
    }

    public function cloneModule(string $id): void
    {
    }

    public function loadModuleL10N(string $id, ?string $lang, string $file): void
    {
        if ($this->safe_mode) {
            return;
        }

        $module = $this->getDefine($id);//, ['state' => ModuleDefine::STATE_ENABLED]);
        if ($lang && $module->isDefined()) {
            $lfile = $module->get('root') . DIRECTORY_SEPARATOR . 'locales' . DIRECTORY_SEPARATOR . '%s' . DIRECTORY_SEPARATOR . '%s';
            if (L10n::set(sprintf($lfile, $lang, $file)) === false && $lang != 'en') {
                L10n::set(sprintf($lfile, 'en', $file));
            }
        }
    }

    public function loadModuleL10Nresources(string $id, ?string $lang): void
    {
        if ($this->safe_mode) {
            return;
        }

        $module = $this->getDefine($id, ['state' => ModuleDefine::STATE_ENABLED]);
        if ($lang && $module->isDefined()) {
            if ($file = L10n::getFilePath($module->get('root') . DIRECTORY_SEPARATOR . 'locales', 'resources.php', $lang)) {
                $this->loadModuleFile($file, true);
            }
        }
    }

    public function getModules(?string $id = null): array
    {
        dcDeprecated::set(self::class . '::getDefines()', '2.26');

        $modules = $this->getDefines(['state' => $this->safe_mode ? ModuleDefine::STATE_SOFT_DISABLED : ModuleDefine::STATE_ENABLED], true);

        return $id && isset($modules[$id]) ? $modules[$id] : $modules;
    }

    public function getAnyModules(?string $id = null): array
    {
        dcDeprecated::set(self::class . '::getDefines()', '2.26');

        $modules = $this->getDefines([], true);

        return $id && isset($modules[$id]) ? $modules[$id] : $modules;
    }

    public function moduleExists(string $id): bool
    {
        return $this->getDefine($id, ['state' => ModuleDefine::STATE_ENABLED])->isDefined();
    }

    public function getDisabledModules(): array
    {
        dcDeprecated::set(self::class . '::getDefines()', '2.26');

        return $this->getDefines(['state' => '!' . ModuleDefine::STATE_ENABLED], true);
    }

    public function getHardDisabledModules(): array
    {
        dcDeprecated::set(self::class . '::getDefines()', '2.26');

        return $this->getDefines(['state' => ModuleDefine::STATE_HARD_DISABLED], true);
    }

    public function getSoftDisabledModules(): array
    {
        dcDeprecated::set(self::class . '::getDefines()', '2.26');

        return $this->getDefines(['state' => ModuleDefine::STATE_SOFT_DISABLED], true);
    }

    public function moduleRoot(string $id): ?string
    {
        dcDeprecated::set(self::class . '::moduleInfo()', '2.26');

        return $this->moduleInfo($id, 'root');
    }

    public function moduleInfo(string $id, string $info): mixed
    {
        return $this->getDefine($id, ['state' => ModuleDefine::STATE_ENABLED])->get($info);
    }

    public function loadNsFiles(?string $ns = null): void
    {
        dcDeprecated::set('nothing', '2.27');

        foreach ($this->getDefines(['state' => ModuleDefine::STATE_ENABLED]) as $module) {
            $this->loadNsFile($module->getId(), $ns);
        }
    }

    public function loadNsFile(string $id, ?string $ns = null): void
    {
        $module = $this->getDefine($id, ['state' => ModuleDefine::STATE_ENABLED]);
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
            $this->loadModuleFile($module->get('root') . DIRECTORY_SEPARATOR . $file, true);
        }
    }

    public function loadNsClass(string $id, string $ns, bool $process = true): string
    {
        $module = $this->getDefine($id, ['state' => ModuleDefine::STATE_ENABLED]);

        // unknown module
        if (!$module->isDefined()) {
            return '';
        }

        // unknown class
        $class = $module->get('namespace') . Autoloader::NS_SEP . ucfirst($ns);
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
     * @param   string  $________   Module filename
     * @param   bool    $globals    Should include globals variables
     * @param   bool    $catch      Should catch output to prevent hacked/corrupted modules
     *
     * @return  mixed
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

            ob_start();
            $ret = require $________;
            ob_end_clean();

            return $ret;
        }

        // Or just require file
        return require $________;
    }
}
