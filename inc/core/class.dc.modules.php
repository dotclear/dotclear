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

if (!defined('DC_RC_PATH')) {return;}

class dcModules
{
    protected $path;
    protected $ns;
    protected $modules       = array();
    protected $disabled      = array();
    protected $errors        = array();
    protected $modules_names = array();
    protected $all_modules   = array();
    protected $disabled_mode = false;
    protected $disabled_meta = array();
    protected $to_disable    = array();

    protected $id;
    protected $mroot;

    # Inclusion variables
    protected static $superglobals = array('GLOBALS', '_SERVER', '_GET', '_POST', '_COOKIE', '_FILES', '_ENV', '_REQUEST', '_SESSION');
    protected static $_k;
    protected static $_n;

    protected static $type = null;

    public $core; ///< <b>dcCore</b>    dcCore instance

    /**
    Object constructor.

    @param    core        <b>dcCore</b>    dcCore instance
     */
    public function __construct($core)
    {
        $this->core = &$core;
    }

    /**
     * Checks all modules dependencies
     *     Fills in the following information in module :
     *       * cannot_enable : list reasons why module cannot be enabled. Not set if module can be enabled
     *       * cannot_disable : list reasons why module cannot be disabled. Not set if module can be disabled
     *       * implies : reverse dependencies
     * @return array list of enabled modules with unmet dependencies, and that must be disabled.
     */
    public function checkDependencies()
    {
        $dc_version       = preg_replace('/\-dev$/', '', DC_VERSION);
        $this->to_disable = array();
        foreach ($this->all_modules as $k => &$m) {
            if (isset($m['requires'])) {
                $missing = array();
                foreach ($m['requires'] as &$dep) {
                    if (!is_array($dep)) {
                        $dep = array($dep);
                    }
                    // grab missing dependencies
                    if (!isset($this->all_modules[$dep[0]]) && ($dep[0] != 'core')) {
                        // module not present
                        $missing[$dep[0]] = sprintf(__("Requires %s module which is not installed"), $dep[0]);
                    } elseif ((count($dep) > 1) &&
                        version_compare(($dep[0] == 'core' ? $dc_version : $this->all_modules[$dep[0]]['version']), $dep[1]) == -1) {
                        // module present, but version missing
                        if ($dep[0] == 'core') {
                            $missing[$dep[0]] = sprintf(__("Requires Dotclear version %s, but version %s is installed"),
                                $dep[1], $dc_version);
                        } else {
                            $missing[$dep[0]] = sprintf(__("Requires %s module version %s, but version %s is installed"),
                                $dep[0], $dep[1], $this->all_modules[$dep[0]]['version']);
                        }
                    } elseif (($dep[0] != 'core') && !$this->all_modules[$dep[0]]['enabled']) {
                        // module disabled
                        $missing[$dep[0]] = sprintf(__("Requires %s module which is disabled"), $dep[0]);
                    }
                    $this->all_modules[$dep[0]]['implies'][] = $k;
                }
                if (count($missing)) {
                    $m['cannot_enable'] = $missing;
                    if ($m['enabled']) {
                        $this->to_disable[] = array('name' => $k, 'reason' => $missing);
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
     * @param  string $redir_url URL to redirect if modules are to disable
     * @return boolean, true if a redirection has been performed
     */
    public function disableDepModules($redir_url)
    {
        if (isset($_GET['dep'])) {
            // Avoid infinite redirects
            return false;
        }
        $reason = array();
        foreach ($this->to_disable as $module) {
            try {
                $this->deactivateModule($module['name']);
                $reason[] = sprintf("<li>%s : %s</li>", $module['name'], join(',', $module['reason']));
            } catch (Exception $e) {
            }
        }
        if (count($reason)) {
            $message = sprintf("<p>%s</p><ul>%s</ul>",
                __('The following extensions have been disabled :'),
                join('', $reason)
            );
            dcPage::addWarningNotice($message, array('divtag' => true, 'with_ts' => false));
            $url = $redir_url . (strpos($redir_url, "?") ? '&' : '?') . 'dep=1';
            http::redirect($url);
            return true;
        }
        return false;
    }

    /**
    Loads modules. <var>$path</var> could be a separated list of paths
    (path separator depends on your OS).

    <var>$ns</var> indicates if an additionnal file needs to be loaded on plugin
    load, value could be:
    - admin (loads module's _admin.php)
    - public (loads module's _public.php)
    - xmlrpc (loads module's _xmlrpc.php)

    <var>$lang</var> indicates if we need to load a lang file on plugin
    loading.
     */
    public function loadModules($path, $ns = null, $lang = null)
    {
        $this->path = explode(PATH_SEPARATOR, $path);
        $this->ns   = $ns;

        $disabled = isset($_SESSION['sess_safe_mode']) && $_SESSION['sess_safe_mode'];
        $disabled = $disabled && !get_parent_class($this) ? true : false;

        $ignored = array();

        foreach ($this->path as $root) {
            if (!is_dir($root) || !is_readable($root)) {
                continue;
            }

            if (substr($root, -1) != '/') {
                $root .= '/';
            }

            if (($d = @dir($root)) === false) {
                continue;
            }

            while (($entry = $d->read()) !== false) {
                $full_entry = $root . $entry;

                if ($entry != '.' && $entry != '..' && is_dir($full_entry)
                    && file_exists($full_entry . '/_define.php')) {
                    if (!file_exists($full_entry . '/_disabled') && !$disabled) {
                        $this->id    = $entry;
                        $this->mroot = $full_entry;
                        ob_start();
                        require $full_entry . '/_define.php';
                        ob_end_clean();
                        $this->all_modules[$entry] = &$this->modules[$entry];
                        $this->id                  = null;
                        $this->mroot               = null;
                    } else {
                        if (file_exists($full_entry . '/_define.php')) {
                            $this->id            = $entry;
                            $this->mroot         = $full_entry;
                            $this->disabled_mode = true;
                            ob_start();
                            require $full_entry . '/_define.php';
                            ob_end_clean();
                            $this->disabled_mode       = false;
                            $this->disabled[$entry]    = $this->disabled_meta;
                            $this->all_modules[$entry] = &$this->disabled[$entry];
                            $this->id                  = null;
                            $this->mroot               = null;
                        }
                    }
                }
            }
            $d->close();
        }
        $this->checkDependencies();
        # Sort plugins
        uasort($this->modules, array($this, 'sortModules'));

        foreach ($this->modules as $id => $m) {
            # Load translation and _prepend
            if (file_exists($m['root'] . '/_prepend.php')) {
                $r = $this->loadModuleFile($m['root'] . '/_prepend.php');

                # If _prepend.php file returns null (ie. it has a void return statement)
                if (is_null($r)) {
                    $ignored[] = $id;
                    continue;
                }
                unset($r);
            }

            $this->loadModuleL10N($id, $lang, 'main');
            if ($ns == 'admin') {
                $this->loadModuleL10Nresources($id, $lang);
                $this->core->adminurl->register('admin.plugin.' . $id, 'plugin.php', array('p' => $id));
            }
        }

        // Give opportunity to do something before loading context (admin,public,xmlrpc) files
        $this->core->callBehavior('coreBeforeLoadingNsFiles', $this->core, $this, $lang);

        foreach ($this->modules as $id => $m) {
            # If _prepend.php file returns null (ie. it has a void return statement)
            if (in_array($id, $ignored)) {
                continue;
            }
            # Load ns_file
            $this->loadNsFile($id, $ns);
        }
    }

    public function requireDefine($dir, $id)
    {
        if (file_exists($dir . '/_define.php')) {
            $this->id = $id;
            ob_start();
            require $dir . '/_define.php';
            ob_end_clean();
            $this->id = null;
        }
    }

    /**
    This method registers a module in modules list. You should use this to
    register a new module.

    <var>$permissions</var> is a comma separated list of permissions for your
    module. If <var>$permissions</var> is null, only super admin has access to
    this module.

    <var>$priority</var> is an integer. Modules are sorted by priority and name.
    Lowest priority comes first.

    @param    name            <b>string</b>        Module name
    @param    desc            <b>string</b>        Module description
    @param    author        <b>string</b>        Module author name
    @param    version        <b>string</b>        Module version
    @param    properties    <b>array</b>        extra properties
    (currently available keys : permissions, priority, type)
     */
    public function registerModule($name, $desc, $author, $version, $properties = array())
    {
        if ($this->disabled_mode) {
            $this->disabled_meta = array_merge(
                $properties,
                array(
                    'root'          => $this->mroot,
                    'name'          => $name,
                    'desc'          => $desc,
                    'author'        => $author,
                    'version'       => $version,
                    'enabled'       => false,
                    'root_writable' => is_writable($this->mroot)
                )
            );
            return;
        }
        # Fallback to legacy registerModule parameters
        if (!is_array($properties)) {
            $args       = func_get_args();
            $properties = array();
            if (isset($args[4])) {
                $properties['permissions'] = $args[4];
            }
            if (isset($args[5])) {
                $properties['priority'] = (integer) $args[5];
            }
        }

        # Default module properties
        $properties = array_merge(
            array(
                'permissions'       => null,
                'priority'          => 1000,
                'standalone_config' => false,
                'type'              => null,
                'enabled'           => true,
                'requires'          => array(),
                'settings'          => array()
            ), $properties
        );

        # Check module type
        if (self::$type !== null && $properties['type'] !== null && $properties['type'] != self::$type) {
            $this->errors[] = sprintf(
                __('Module "%s" has type "%s" that mismatch required module type "%s".'),
                '<strong>' . html::escapeHTML($name) . '</strong>',
                '<em>' . html::escapeHTML($properties['type']) . '</em>',
                '<em>' . html::escapeHTML(self::$type) . '</em>'
            );
            return;
        }

        # Check module perms on admin side
        $permissions = $properties['permissions'];
        if ($this->ns == 'admin') {
            if ($permissions == '' && !$this->core->auth->isSuperAdmin()) {
                return;
            } elseif (!$this->core->auth->check($permissions, $this->core->blog->id)) {
                return;
            }
        }

        # Check module install on multiple path
        if ($this->id) {
            $module_exists    = array_key_exists($name, $this->modules_names);
            $module_overwrite = $module_exists ? version_compare($this->modules_names[$name], $version, '<') : false;
            if (!$module_exists || ($module_exists && $module_overwrite)) {
                $this->modules_names[$name] = $version;
                $this->modules[$this->id]   = array_merge(
                    $properties,
                    array(
                        'root'          => $this->mroot,
                        'name'          => $name,
                        'desc'          => $desc,
                        'author'        => $author,
                        'version'       => $version,
                        'root_writable' => is_writable($this->mroot)
                    )
                );
            } else {
                $path1          = path::real($this->moduleInfo($name, 'root'));
                $path2          = path::real($this->mroot);
                $this->errors[] = sprintf(
                    __('Module "%s" is installed twice in "%s" and "%s".'),
                    '<strong>' . $name . '</strong>',
                    '<em>' . $path1 . '</em>',
                    '<em>' . $path2 . '</em>'
                );
            }
        }
    }

    public function resetModulesList()
    {
        $this->modules       = array();
        $this->modules_names = array();
        $this->errors        = array();
    }

    public static function installPackage($zip_file, dcModules &$modules)
    {
        $zip = new fileUnzip($zip_file);
        $zip->getList(false, '#(^|/)(__MACOSX|\.svn|\.hg|\.git|\.DS_Store|\.directory|Thumbs\.db)(/|$)#');

        $zip_root_dir = $zip->getRootDir();
        $define       = '';
        if ($zip_root_dir != false) {
            $target      = dirname($zip_file);
            $destination = $target . '/' . $zip_root_dir;
            $define      = $zip_root_dir . '/_define.php';
            $has_define  = $zip->hasFile($define);
        } else {
            $target      = dirname($zip_file) . '/' . preg_replace('/\.([^.]+)$/', '', basename($zip_file));
            $destination = $target;
            $define      = '_define.php';
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

        $ret_code = 1;

        if (!is_dir($destination)) {
            try {
                files::makeDir($destination, true);

                $sandbox = clone $modules;
                $zip->unzip($define, $target . '/_define.php');

                $sandbox->resetModulesList();
                $sandbox->requireDefine($target, basename($destination));
                unlink($target . '/_define.php');

                $new_errors = $sandbox->getErrors();
                if (!empty($new_errors)) {
                    $new_errors = is_array($new_errors) ? implode(" \n", $new_errors) : $new_errors;
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
            # test for update
            $sandbox = clone $modules;
            $zip->unzip($define, $target . '/_define.php');

            $sandbox->resetModulesList();
            $sandbox->requireDefine($target, basename($destination));
            unlink($target . '/_define.php');
            $new_modules = $sandbox->getModules();

            if (!empty($new_modules)) {
                $tmp        = array_keys($new_modules);
                $id         = $tmp[0];
                $cur_module = $modules->getModules($id);
                if (!empty($cur_module) && (defined('DC_DEV') && DC_DEV === true || dcUtils::versionsCompare($new_modules[$id]['version'], $cur_module['version'], '>', true))) {
                    # delete old module
                    if (!files::deltree($destination)) {
                        throw new Exception(__('An error occurred during module deletion.'));
                    }
                    $ret_code = 2;
                } else {
                    $zip->close();
                    unlink($zip_file);
                    throw new Exception(sprintf(__('Unable to upgrade "%s". (older or same version)'), basename($destination)));
                }
            } else {
                $zip->close();
                unlink($zip_file);
                throw new Exception(sprintf(__('Unable to read new _define.php file')));
            }
        }
        $zip->unzipAll($target);
        $zip->close();
        unlink($zip_file);
        return $ret_code;
    }

    /**
    This method installs all modules having a _install file.

    @see dcModules::installModule
     */
    public function installModules()
    {
        $res = array('success' => array(), 'failure' => array());
        foreach ($this->modules as $id => &$m) {
            $i = $this->installModule($id, $msg);
            if ($i === true) {
                $res['success'][$id] = true;
            } elseif ($i === false) {
                $res['failure'][$id] = $msg;
            }
        }

        return $res;
    }

    /**
    This method installs module with ID <var>$id</var> and having a _install
    file. This file should throw exception on failure or true if it installs
    successfully.

    <var>$msg</var> is an out parameter that handle installer message.

    @param    id        <b>string</b>        Module ID
    @param    msg        <b>string</b>        Module installer message
    @return    <b>boolean</b>
     */
    public function installModule($id, &$msg)
    {
        try {
            $i = $this->loadModuleFile($this->modules[$id]['root'] . '/_install.php');
            if ($i === true) {
                return true;
            }
        } catch (Exception $e) {
            $msg = $e->getMessage();
            return false;
        }

        return;
    }

    public function deleteModule($id, $disabled = false)
    {
        if ($disabled) {
            $p = &$this->disabled;
        } else {
            $p = &$this->modules;
        }

        if (!isset($p[$id])) {
            throw new Exception(__('No such module.'));
        }

        if (!files::deltree($p[$id]['root'])) {
            throw new Exception(__('Cannot remove module files'));
        }
    }

    public function deactivateModule($id)
    {
        if (!isset($this->modules[$id])) {
            throw new Exception(__('No such module.'));
        }

        if (!$this->modules[$id]['root_writable']) {
            throw new Exception(__('Cannot deactivate plugin.'));
        }

        if (@file_put_contents($this->modules[$id]['root'] . '/_disabled', '')) {
            throw new Exception(__('Cannot deactivate plugin.'));
        }
    }

    public function activateModule($id)
    {
        if (!isset($this->disabled[$id])) {
            throw new Exception(__('No such module.'));
        }

        if (!$this->disabled[$id]['root_writable']) {
            throw new Exception(__('Cannot activate plugin.'));
        }

        if (@unlink($this->disabled[$id]['root'] . '/_disabled') === false) {
            throw new Exception(__('Cannot activate plugin.'));
        }
    }

    /**
    This method will search for file <var>$file</var> in language
    <var>$lang</var> for module <var>$id</var>.

    <var>$file</var> should not have any extension.

    @param    id        <b>string</b>        Module ID
    @param    lang        <b>string</b>        Language code
    @param    file        <b>string</b>        File name (without extension)
     */
    public function loadModuleL10N($id, $lang, $file)
    {
        if (!$lang || !isset($this->modules[$id])) {
            return;
        }

        $lfile = $this->modules[$id]['root'] . '/locales/%s/%s';
        if (l10n::set(sprintf($lfile, $lang, $file)) === false && $lang != 'en') {
            l10n::set(sprintf($lfile, 'en', $file));
        }
    }

    public function loadModuleL10Nresources($id, $lang)
    {
        if (!$lang || !isset($this->modules[$id])) {
            return;
        }

        $f = l10n::getFilePath($this->modules[$id]['root'] . '/locales', 'resources.php', $lang);
        if ($f) {
            $this->loadModuleFile($f);
        }
    }

    /**
    Returns all modules associative array or only one module if <var>$id</var>
    is present.

    @param    id        <b>string</b>        Optionnal module ID
    @return    <b>array</b>
     */
    public function getModules($id = null)
    {
        if ($id && isset($this->modules[$id])) {
            return $this->modules[$id];
        }
        return $this->modules;
    }

    /**
    Returns true if the module with ID <var>$id</var> exists.

    @param    id        <b>string</b>        Module ID
    @return    <b>boolean</b>
     */
    public function moduleExists($id)
    {
        return isset($this->modules[$id]);
    }

    /**
    Returns all disabled modules in an array

    @return    <b>array</b>
     */
    public function getDisabledModules()
    {
        return $this->disabled;
    }

    /**
    Returns root path for module with ID <var>$id</var>.

    @param    id        <b>string</b>        Module ID
    @return    <b>string</b>
     */
    public function moduleRoot($id)
    {
        return $this->moduleInfo($id, 'root');
    }

    /**
    Returns a module information that could be:
    - root
    - name
    - desc
    - author
    - version
    - permissions
    - priority

    @param    id        <b>string</b>        Module ID
    @param    info        <b>string</b>        Information to retrieve
    @return    <b>string</b>
     */
    public function moduleInfo($id, $info)
    {
        return isset($this->modules[$id][$info]) ? $this->modules[$id][$info] : null;
    }

    /**
    Loads namespace <var>$ns</var> specific files for all modules.

    @param    ns        <b>string</b>        Namespace name
     */
    public function loadNsFiles($ns = null)
    {
        foreach ($this->modules as $k => $v) {
            $this->loadNsFile($k, $ns);
        }
    }

    /**
    Loads namespace <var>$ns</var> specific file for module with ID
    <var>$id</var>

    @param    id        <b>string</b>        Module ID
    @param    ns        <b>string</b>        Namespace name
     */
    public function loadNsFile($id, $ns = null)
    {
        switch ($ns) {
            case 'admin':
                $this->loadModuleFile($this->modules[$id]['root'] . '/_admin.php');
                break;
            case 'public':
                $this->loadModuleFile($this->modules[$id]['root'] . '/_public.php');
                break;
            case 'xmlrpc':
                $this->loadModuleFile($this->modules[$id]['root'] . '/_xmlrpc.php');
                break;
        }
    }

    public function getErrors()
    {
        return $this->errors;
    }

    protected function loadModuleFile($________, $catch = true)
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

    private function sortModules($a, $b)
    {
        if ($a['priority'] == $b['priority']) {
            return strcasecmp($a['name'], $b['name']);
        }

        return ($a['priority'] < $b['priority']) ? -1 : 1;
    }
}
