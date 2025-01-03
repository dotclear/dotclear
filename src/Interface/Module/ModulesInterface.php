<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

/**
 * @namespace   Dotclear.Interface.Module
 * @brief       Modules interfaces
 */

namespace Dotclear\Interface\Module;

use Dotclear\Module\ModuleDefine;

/**
 * @brief   Modules handler interface.
 *
 * @since   2.28
 */
interface ModulesInterface
{
    /**
     * Return code for package installation.
     *
     * @var     int     PACKAGE_INSTALLED
     */
    public const PACKAGE_INSTALLED = 1;

    /**
     * Return code for package update.
     *
     * @var     int     PACKAGE_UPDATED
     */
    public const PACKAGE_UPDATED = 2;

    /**
     * Name of module old style installation file.
     *
     * @var     string  MODULE_FILE_INSTALL
     */
    public const MODULE_FILE_INSTALL = '_install.php';

    /**
     * Name of module old style initialization file.
     *
     * @deprecated  since 2.28, use My and a namespaced class to declare module for constants instead
     *
     * @var     string  MODULE_FILE_INIT
     */
    public const MODULE_FILE_INIT = '_init.php';

    /**
     * Name of module define file.
     *
     * @var     string  MODULE_FILE_DEFINE
     */
    public const MODULE_FILE_DEFINE = '_define.php';

    /**
     * Name of module old style prepend file.
     *
     * @var     string  MODULE_FILE_PREPEND
     */
    public const MODULE_FILE_PREPEND = '_prepend.php';

    /**
     * Name of module old style backend file.
     *
     * @var     string  MODULE_FILE_ADMIN
     */
    public const MODULE_FILE_ADMIN = '_admin.php';

    /**
     * Name of module old style configuration file.
     *
     * @var     string  MODULE_FILE_CONFIG
     */
    public const MODULE_FILE_CONFIG = '_config.php';

    /**
     * Name of module old style manage file.
     *
     * @var     string  MODULE_FILE_MANAGE
     */
    public const MODULE_FILE_MANAGE = 'index.php';

    /**
     * Name of module old style frontend file.
     *
     * @var     string  MODULE_FILE_PUBLIC
     */
    public const MODULE_FILE_PUBLIC = '_public.php';

    /**
     * Name of module hard deactivation file.
     *
     * @var     string  MODULE_FILE_DISABLED
     */
    public const MODULE_FILE_DISABLED = '_disabled';

    /**
     * The update locked file name.
     *
     * @var     string   MODULE_FILE_LOCKED
     */
    public const MODULE_FILE_LOCKED = '_locked';

    /**
     * Directory for module namespace.
     *
     * @var     string  MODULE_CLASS_DIR
     */
    public const MODULE_CLASS_DIR = 'src';

    /**
     * Name of module prepend class (ex _prepend.php).
     *
     * @var     string  MODULE_CLASS_PREPEND
     */
    public const MODULE_CLASS_PREPEND = 'Prepend';

    /**
     * Name of module installation class (ex _install.php).
     *
     * @var     string  MODULE_CLASS_INSTALL
     */
    public const MODULE_CLASS_INSTALL = 'Install';

    /**
     * Name of module backend class (ex _admin.php).
     *
     * @var     string  MODULE_CLASS_ADMIN
     */
    public const MODULE_CLASS_ADMIN = 'Backend';

    /**
     * Name of module configuration class (ex _config.php).
     *
     * @var     string  MODULE_CLASS_CONFIG
     */
    public const MODULE_CLASS_CONFIG = 'Config';

    /**
     * Name of module manage class (ex index.php).
     *
     * @var     string  MODULE_CLASS_MANAGE
     */
    public const MODULE_CLASS_MANAGE = 'Manage';

    /**
     * Name of module frontend class (ex _public.php).
     *
     * @var     string  MODULE_CLASS_PUPLIC
     */
    public const MODULE_CLASS_PUPLIC = 'Frontend';

    /**
     * Get first ocurrence of a module's defined properties.
     *
     * This method always returns a ModuleDefine class,
     * if module definition does not exist, it is created on the fly
     * with default properties.
     *
     * @param   string                  $id         The module identifier
     * @param   array<string,mixed>     $search     The search parameters
     *
     * @return  ModuleDefine    The first matching module define or properties
     */
    public function getDefine(string $id, array $search = []): ModuleDefine;

    /**
     * Get modules defined properties.
     *
     * More than one module can have same id in this stack.
     *
     * @param   array<string,mixed>     $search     The search parameters
     * @param   bool                    $to_array   Return arrays of modules properties
     *
     * @return  array<int|string, mixed>   The modules defines or properties
     */
    public function getDefines(array $search = [], bool $to_array = false): array;

    /**
     * Checks all modules dependencies.
     *
     * Fills in the following information in module :
     *
     *  - missing : list reasons why module cannot be enabled. Not set if module can be enabled
     *
     *  - using : list reasons why module cannot be disabled. Not set if module can be disabled
     *
     *  - implies : reverse dependencies
     *
     * @param   ModuleDefine    $module     The module to check
     * @param   bool            $to_error   Add dependencies fails to errors
     */
    public function checkDependencies(ModuleDefine $module, bool $to_error = false): void;

    /**
     * Disables the dep modules.
     *
     * If module has missing dep and is not yet in hard disbaled state (_disabled) goes in.
     *
     * @return  array<int, string>   The reasons to disable modules
     */
    public function disableDepModules(): array;

    /**
     * Should run in safe mode?
     *
     * @param   null|bool   $mode   Mode, null to read current mode
     */
    public function safeMode(?bool $mode = null): bool;

    /**
     * Loads modules. <var>$path</var> could be a separated list of paths
     * (path separator depends on your OS).
     *
     * <var>$ns</var> indicates if an additionnal file needs to be loaded on plugin
     * load, value could be:
     * - admin (loads module's Backend.php)
     * - public (loads module's Frontend.php)
     * - upgrade (loads nothing)
     *
     * <var>$lang</var> indicates if we need to load a lang file on plugin
     * loading.
     *
     * @param   string  $path   The path
     * @param   string  $ns     The namespace (context as 'public', 'admin', 'upgrade', ...)
     * @param   string  $lang   The language
     */
    public function loadModules(string $path, ?string $ns = null, ?string $lang = null): void;

    /**
     * Load the _define.php file of the given module.
     *
     * @param   string  $dir    The dir
     * @param   string  $id     The module identifier
     */
    public function requireDefine(string $dir, string $id): void;

    /**
     * This method registers a module in modules list.
     *
     * @param   string  $name           The module name
     * @param   string  $desc           The module description
     * @param   string  $author         The module author
     * @param   string  $version        The module version
     * @param   mixed   $properties     The properties
     */
    public function registerModule(string $name, string $desc, string $author, string $version, $properties = []): void;

    /**
     * Reset modules list.
     */
    public function resetModulesList(): void;

    /**
     * Check if there are no modules loaded.
     *
     * @return  bool    True on no modules
     */
    public function isEmpty(): bool;

    /**
     * Install a Package.
     *
     * @param   string              $zip_file   The zip file
     * @param   ModulesInterface    $modules    The modules
     *
     * @throws  \Exception
     */
    public static function installPackage(string $zip_file, ModulesInterface &$modules): int;

    /**
     * This method installs all modules having a _install file.
     *
     * @see     self::installModule
     *
     * @return  array<string, array<string, bool|string>>
     */
    public function installModules(): array;

    /**
     * Install a module.
     *
     * This method installs module with ID <var>$id</var> and having a _install
     * file. This file should throw exception on failure or true if it installs
     * successfully.
     * <var>$msg</var> is an out parameter that handle installer message.
     *
     * @param   string  $id     The identifier
     * @param   string  $msg    The message
     */
    public function installModule(string $id, string &$msg): ?bool;

    /**
     * Delete a module.
     *
     * @param   string  $id         The module identifier
     * @param   bool    $disabled   Is module disabled
     *
     * @throws  \Exception
     */
    public function deleteModule(string $id, bool $disabled = false): void;

    /**
     * Deactivate a module.
     *
     * @param   string  $id     The identifier
     *
     * @throws  \Exception
     */
    public function deactivateModule(string $id): void;

    /**
     * Activate a module.
     *
     * @param   string  $id     The identifier
     *
     * @throws  \Exception
     */
    public function activateModule(string $id): void;

    /**
     * Clone a module.
     *
     * @throws \Exception
     *
     * @param   string  $id     The module identifier
     */
    public function cloneModule(string $id): void;

    /**
     * Load module l10n file.
     *
     * This method will search for file <var>$file</var> in language
     * <var>$lang</var> for module <var>$id</var>.
     *<var>$file</var> should not have any extension.
     *
     * @param   string  $id     The module identifier
     * @param   string  $lang   The language code
     * @param   string  $file   The filename (without extension)
     */
    public function loadModuleL10N(string $id, ?string $lang, string $file): void;

    /**
     * Loads module l10n resources.
     *
     * @param   string  $id     The module identifier
     * @param   string  $lang   The language code
     */
    public function loadModuleL10Nresources(string $id, ?string $lang): void;

    /**
     * Returns all modules associative array or only one module if <var>$id</var> is present.
     *
     * @deprecated  since 2.26, use self::getDefines() instead
     *
     * @param   string  $id     The optionnal module identifier
     *
     * @return  array<int|string, mixed>   The module(s).
     */
    public function getModules(?string $id = null): array;

    /**
     * Gets all modules (whatever are their statuses) or only one module if <var>$id</var> is present.
     *
     * @deprecated  since 2.26, use self::getDefines() instead
     *
     * @param   string  $id     The optionnal module identifier
     *
     * @return  array<int|string, mixed>  The module(s).
     */
    public function getAnyModules(?string $id = null): array;

    /**
     * Determines if module exists and is enabled.
     *
     * @param   string  $id     The module identifier
     *
     * @return  bool    True if module exists, False otherwise.
     */
    public function moduleExists(string $id): bool;

    /**
     * Gets the disabled modules.
     *
     * @deprecated  since 2.26, use self::getDefines() instead
     *
     * @return  array<int|string, mixed>   The disabled modules.
     */
    public function getDisabledModules(): array;

    /**
     * Gets the hard disabled modules.
     *
     * @deprecated  since 2.26, use self::getDefines() instead
     *
     * @return  array<int|string, mixed>  The hard disabled modules.
     */
    public function getHardDisabledModules(): array;

    /**
     * Gets the soft disabled modules.
     *
     * (safe mode and not hard disabled)
     *
     * @deprecated  since 2.26, use self::getDefines() instead
     *
     * @return  array<int|string, mixed>  The soft disabled modules.
     */
    public function getSoftDisabledModules(): array;

    /**
     * Returns root path for module with ID <var>$id</var>.
     *
     * @deprecated  since 2.26, use self::moduleInfo() instead
     *
     * @param   string  $id     The module identifier
     */
    public function moduleRoot(string $id): ?string;

    /**
     * Get a module information.
     *
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
     * @param   string  $id     The module identifier
     * @param   string  $info   The information
     */
    public function moduleInfo(string $id, string $info): mixed;

    /**
     * Loads namespace <var>$ns</var> specific files for all modules.
     *
     * @deprecated  since 2.27, use nothing instead !
     *
     * @param   string  $ns
     */
    public function loadNsFiles(?string $ns = null): void;

    /**
     * Loads namespace <var>$ns</var> specific file for module with ID
     * <var>$id</var>.
     *
     * @param   string  $id     The module identifier
     * @param   string  $ns     Namespace name
     */
    public function loadNsFile(string $id, ?string $ns = null): void;

    /**
     * Initialise <var>$ns</var> specific namespace for module with ID
     * <var>$id</var>.
     *
     * @param   string  $id         The module identifier
     * @param   string  $ns         Process name
     * @param   bool    $process    Execute process
     *
     * @return  string  The fully qualified class name on success. Empty string on fail.
     */
    public function loadNsClass(string $id, string $ns, bool $process = true): string;

    /**
     * Gets the errors.
     *
     * @return  array<int,string>   The errors.
     */
    public function getErrors(): array;

    /**
     * Compare two versions with option of using only main numbers.
     *
     * @param   string  $current_version    Current version
     * @param   string  $required_version   Required version
     * @param   string  $operator           Comparison operand
     * @param   bool    $strict             Use full version
     */
    public function versionsCompare(string $current_version, string $required_version, string $operator = '>=', bool $strict = true): bool;

    /**
     * Return a HTML CSS resource load (usually in HTML head)
     *
     * @param   string  $src        The source
     * @param   string  $media      The media
     * @param   string  $version    The version
     */
    public function cssLoad(string $src, string $media = 'screen', ?string $version = null): string;

    /**
     * Return a HTML JS resource load (usually in HTML head).
     *
     * @param   string  $src        The source
     * @param   string  $version    The version
     * @param   bool    $module     Load source as JS module
     */
    public function jsLoad(string $src, ?string $version = null, bool $module = false): string;
}
