<?php
/**
 * @brief Dotclear core class
 *
 * True to its name dcCore is the core of Dotclear. It handles everything related
 * to blogs, database connection, plugins...
 *
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

class dcCore
{
    public $con;        ///< <b>connection</b>        Database connection object
    public $prefix;     ///< <b>string</b>            Database tables prefix
    public $blog;       ///< <b>dcBlog</b>            dcBlog object
    public $error;      ///< <b>dcError</b>            dcError object
    public $auth;       ///< <b>dcAuth</b>            dcAuth object
    public $session;    ///< <b>sessionDB</b>        sessionDB object
    public $url;        ///< <b>urlHandler</b>        urlHandler object
    public $wiki2xhtml; ///< <b>wiki2xhtml</b>        wiki2xhtml object
    public $plugins;    ///< <b>dcModules</b>        dcModules object
    public $media;      ///< <b>dcMedia</b>            dcMedia object
    public $postmedia;  ///< <b>dcPostMedia</b>        dcPostMedia object
    public $rest;       ///< <b>dcRestServer</b>    dcRestServer object
    public $log;        ///< <b>dcLog</b>            dcLog object
    public $stime;      ///< <b>float</b>            starting time

    private $versions   = null;
    private $formaters  = array();
    private $behaviors  = array();
    private $post_types = array();

    /**
    dcCore constructor inits everything related to Dotclear. It takes arguments
    to init database connection.

    @param    driver    <b>string</b>    Database driver name
    @param    host        <b>string</b>    Database hostname
    @param    db        <b>string</b>    Database name
    @param    user        <b>string</b>    Database username
    @param    password    <b>string</b>    Database password
    @param    prefix    <b>string</b>    DotClear tables prefix
    @param    persist    <b>boolean</b>    Persistent database connection
     */
    public function __construct($driver, $host, $db, $user, $password, $prefix, $persist)
    {
        if (defined('DC_START_TIME')) {
            $this->stime = DC_START_TIME;
        } else {
            $this->stime = microtime(true);
        }

        $this->con = dbLayer::init($driver, $host, $db, $user, $password, $persist);

        # define weak_locks for mysql
        if ($this->con instanceof mysqlConnection) {
            mysqlConnection::$weak_locks = true;
        } elseif ($this->con instanceof mysqliConnection) {
            mysqliConnection::$weak_locks = true;
        } elseif ($this->con instanceof mysqlimb4Connection) {
            mysqlimb4Connection::$weak_locks = true;
        }

        # define searchpath for postgresql
        if ($this->con instanceof pgsqlConnection) {
            $searchpath = explode('.', $prefix, 2);
            if (count($searchpath) > 1) {
                $prefix = $searchpath[1];
                $sql    = 'SET search_path TO ' . $searchpath[0] . ',public;';
                $this->con->execute($sql);
            }
        }

        $this->prefix = $prefix;

        $ttl = DC_SESSION_TTL;
        if (!is_null($ttl)) {
            if (substr(trim($ttl), 0, 1) != '-') {
                // Clearbricks requires negative session TTL
                $ttl = '-' . trim($ttl);
            }
        }

        $this->error   = new dcError();
        $this->auth    = $this->authInstance();
        $this->session = new sessionDB($this->con, $this->prefix . 'session', DC_SESSION_NAME, '', null, DC_ADMIN_SSL, $ttl);
        $this->url     = new dcUrlHandlers();

        $this->plugins = new dcPlugins($this);

        $this->rest = new dcRestServer($this);

        $this->meta = new dcMeta($this);

        $this->log = new dcLog($this);
    }

    private function authInstance()
    {
        # You can set DC_AUTH_CLASS to whatever you want.
        # Your new class *should* inherits dcAuth.
        if (!defined('DC_AUTH_CLASS')) {
            $c = 'dcAuth';
        } else {
            $c = DC_AUTH_CLASS;
        }

        if (!class_exists($c)) {
            throw new Exception('Authentication class ' . $c . ' does not exist.');
        }

        if ($c != 'dcAuth' && !is_subclass_of($c, 'dcAuth')) {
            throw new Exception('Authentication class ' . $c . ' does not inherit dcAuth.');
        }

        return new $c($this);
    }

    /// @name Blog init methods
    //@{
    /**
    Sets a blog to use in <var>blog</var> property.

    @param    id        <b>string</b>        Blog ID
     */
    public function setBlog($id)
    {
        $this->blog = new dcBlog($this, $id);
    }

    /**
    Unsets <var>blog</var> property.
     */
    public function unsetBlog()
    {
        $this->blog = null;
    }
    //@}

    /// @name Blog status methods
    //@{
    /**
    Returns an array of available blog status codes and names.

    @return    <b>array</b> Simple array with codes in keys and names in value
     */
    public function getAllBlogStatus()
    {
        return array(
            1  => __('online'),
            0  => __('offline'),
            -1 => __('removed')
        );
    }

    /**
    Returns a blog status name given to a code. This is intended to be
    human-readable and will be translated, so never use it for tests.
    If status code does not exist, returns <i>offline</i>.

    @param    s    <b>integer</b> Status code
    @return    <b>string</b> Blog status name
     */
    public function getBlogStatus($s)
    {
        $r = $this->getAllBlogStatus();
        if (isset($r[$s])) {
            return $r[$s];
        }
        return $r[0];
    }
    //@}

    /// @name Admin nonce secret methods
    //@{

    public function getNonce()
    {
        return $this->auth->cryptLegacy(session_id());
    }

    public function checkNonce($secret)
    {
        // 40 alphanumeric characters min
        if (!preg_match('/^([0-9a-f]{40,})$/i', $secret)) {
            return false;
        }

        return $secret == $this->auth->cryptLegacy(session_id());
    }

    public function formNonce()
    {
        if (!session_id()) {
            return;
        }

        return form::hidden(array('xd_check'), $this->getNonce());
    }
    //@}

    /// @name Text Formatters methods
    //@{
    /**
    Adds a new text formater which will call the function <var>$func</var> to
    transform text. The function must be a valid callback and takes one
    argument: the string to transform. It returns the transformed string.

    @param    editor_id    <b>string</b>    Editor id (dcLegacyEditor, dcCKEditor, ...)
    @param    name        <b>string</b>    Formater name
    @param    func        <b>callback</b>    Function to use, must be a valid and callable callback
     */
    public function addEditorFormater($editor_id, $name, $func)
    {
        if (is_callable($func)) {
            $this->formaters[$editor_id][$name] = $func;
        }
    }

    /// @name Text Formatters methods
    //@{
    /**
    Adds a new text formater which will call the function <var>$func</var> to
    transform text. The function must be a valid callback and takes one
    argument: the string to transform. It returns the transformed string.

    @param    name        <b>string</b>        Formater name
    @param    func        <b>callback</b>    Function to use, must be a valid and callable callback
     */
    public function addFormater($name, $func)
    {
        $this->addEditorFormater('dcLegacyEditor', $name, $func);
    }

    /**
    Returns editors list

    @return    <b>array</b> An array of editors values.
     */
    public function getEditors()
    {
        $editors = array();

        foreach (array_keys($this->formaters) as $editor_id) {
            $editors[$editor_id] = $this->plugins->moduleInfo($editor_id, 'name');
        }

        return $editors;
    }

    /**
    Returns formaters list by editor

    @param    editor_id    <b>string</b>    Editor id (dcLegacyEditor, dcCKEditor, ...)
    @return    <b>array</b> An array of formaters names in values.

    /**
    if @param editor_id is empty:
    return all formaters sorted by actives editors

    if @param editor_id is not empty
    return formaters for an editor if editor is active
    return empty() array if editor is not active.
    It can happens when a user choose an editor and admin deactivate that editor later
     */
    public function getFormaters($editor_id = '')
    {
        $formaters_list = array();

        if (!empty($editor_id)) {
            if (isset($this->formaters[$editor_id])) {
                $formaters_list = array_keys($this->formaters[$editor_id]);
            }
        } else {
            foreach ($this->formaters as $editor => $formaters) {
                $formaters_list[$editor] = array_keys($formaters);
            }
        }

        return $formaters_list;
    }

    /**
    If <var>$name</var> is a valid formater, it returns <var>$str</var>
    transformed using that formater.

    @param    editor_id    <b>string</b>    Editor id (dcLegacyEditor, dcCKEditor, ...)
    @param    name        <b>string</b>    Formater name
    @param    str            <b>string</b>    String to transform
    @return    <b>string</b>    String transformed
     */
    public function callEditorFormater($editor_id, $name, $str)
    {
        if (isset($this->formaters[$editor_id]) && isset($this->formaters[$editor_id][$name])) {
            return call_user_func($this->formaters[$editor_id][$name], $str);
        }

        return $str;
    }
    //@}

    /**
    If <var>$name</var> is a valid formater, it returns <var>$str</var>
    transformed using that formater.

    @param    name        <b>string</b>        Formater name
    @param    str            <b>string</b>        String to transform
    @return    <b>string</b>    String transformed
     */
    public function callFormater($name, $str)
    {
        return $this->callEditorFormater('dcLegacyEditor', $name, $str);
    }
    //@}

    /// @name Behaviors methods
    //@{
    /**
    Adds a new behavior to behaviors stack. <var>$func</var> must be a valid
    and callable callback.

    @param    behavior    <b>string</b>        Behavior name
    @param    func        <b>callback</b>    Function to call
     */
    public function addBehavior($behavior, $func)
    {
        if (is_callable($func)) {
            $this->behaviors[$behavior][] = $func;
        }
    }

    /**
    Tests if a particular behavior exists in behaviors stack.

    @param    behavior    <b>string</b>    Behavior name
    @return    <b>boolean</b>
     */
    public function hasBehavior($behavior)
    {
        return isset($this->behaviors[$behavior]);
    }

    /**
    Get behaviors stack (or part of).

    @param    behavior    <b>string</b>        Behavior name
    @return    <b>array</b>
     */
    public function getBehaviors($behavior = '')
    {
        if (empty($this->behaviors)) {
            return;
        }

        if ($behavior == '') {
            return $this->behaviors;
        } elseif (isset($this->behaviors[$behavior])) {
            return $this->behaviors[$behavior];
        }

        return array();
    }

    /**
    Calls every function in behaviors stack for a given behavior and returns
    concatened result of each function.

    Every parameters added after <var>$behavior</var> will be pass to
    behavior calls.

    @param    behavior    <b>string</b>    Behavior name
    @return    <b>string</b> Behavior concatened result
     */
    public function callBehavior($behavior)
    {
        if (isset($this->behaviors[$behavior])) {
            $args = func_get_args();
            array_shift($args);

            $res = '';

            foreach ($this->behaviors[$behavior] as $f) {
                $res .= call_user_func_array($f, $args);
            }

            return $res;
        }
    }
    //@}

    /// @name Post types URLs management
    //@{
    public function getPostAdminURL($type, $post_id, $escaped = true)
    {
        if (!isset($this->post_types[$type])) {
            $type = 'post';
        }

        $url = sprintf($this->post_types[$type]['admin_url'], $post_id);
        return $escaped ? html::escapeURL($url) : $url;
    }

    public function getPostPublicURL($type, $post_url, $escaped = true)
    {
        if (!isset($this->post_types[$type])) {
            $type = 'post';
        }

        $url = sprintf($this->post_types[$type]['public_url'], $post_url);
        return $escaped ? html::escapeURL($url) : $url;
    }

    public function setPostType($type, $admin_url, $public_url, $label = '')
    {
        $this->post_types[$type] = array(
            'admin_url'  => $admin_url,
            'public_url' => $public_url,
            'label'      => ($label != '' ? $label : $type)
        );
    }

    public function getPostTypes()
    {
        return $this->post_types;
    }
    //@}

    /// @name Versions management methods
    //@{
    /**
    Returns a given $module version.

    @param    module    <b>string</b>    Module name
    @return    <b>string</b>    Module version
     */
    public function getVersion($module = 'core')
    {
        # Fetch versions if needed
        if (!is_array($this->versions)) {
            $sql = new dcSelectStatement($this, 'coreGetVersion');
            $sql
                ->columns(array('module', 'version'))
                ->from($this->prefix . 'version');

            $rs     = $this->con->select($sql->statement());

            while ($rs->fetch()) {
                $this->versions[$rs->module] = $rs->version;
            }
        }

        if (isset($this->versions[$module])) {
            return $this->versions[$module];
        } else {
            return;
        }
    }

    /**
    Sets $version to given $module.

    @param    module    <b>string</b>    Module name
    @param    version    <b>string</b>    Module version
     */
    public function setVersion($module, $version)
    {
        $cur_version = $this->getVersion($module);

        $cur          = $this->con->openCursor($this->prefix . 'version');
        $cur->module  = (string) $module;
        $cur->version = (string) $version;

        if ($cur_version === null) {
            $cur->insert();
        } else {
            $sql = new dcUpdateStatement($this, 'coreSetVersion');
            $sql->where('module=' . $sql->quote($module));

            $cur->update($sql->whereStatement());
        }

        $this->versions[$module] = $version;
    }

    /**
    Removes given $module version entry.

    @param    module    <b>string</b>    Module name
     */
    public function delVersion($module)
    {
        $sql = new dcDeleteStatement($this, 'coreDelVersion');
        $sql
            ->from($this->prefix . 'version')
            ->where('module = ' . $sql->quote($module));

        $this->con->execute($sql->statement());

        if (is_array($this->versions)) {
            unset($this->versions[$module]);
        }
    }

    //@}

    /// @name Users management methods
    //@{
    /**
    Returns a user by its ID.

    @param    id        <b>string</b>        User ID
    @return    <b>record</b>
     */
    public function getUser($id)
    {
        $params['user_id'] = $id;

        return $this->getUsers($params);
    }

    /**
    Returns a users list. <b>$params</b> is an array with the following
    optionnal parameters:

    - <var>q</var>: search string (on user_id, user_name, user_firstname)
    - <var>user_id</var>: user ID
    - <var>order</var>: ORDER BY clause (default: user_id ASC)
    - <var>limit</var>: LIMIT clause (should be an array ![limit,offset])

    @param    params        <b>array</b>        Parameters
    @param    count_only    <b>boolean</b>        Only counts results
    @return    <b>record</b>
     */
    public function getUsers($params = array(), $count_only = false)
    {
        $sql = new dcSelectStatement($this, 'coreGetUsers');
        $sql
            ->from($this->prefix . 'user U')
            ->where('NULL IS NULL');

        if ($count_only) {
            $sql->columns('COUNT(U.user_id)');
        } else {
            $sql
                ->columns(array('U.user_id', 'user_super', 'user_status', 'user_pwd', 'user_change_pwd', 'user_name',
                    'user_firstname', 'user_displayname', 'user_email', 'user_url', 'user_desc', 'user_lang', 'user_tz',
                    'user_post_status', 'user_options', 'COUNT(P.post_id) AS nb_post'))
                ->join('LEFT JOIN ' . $this->prefix . 'post P ON U.user_id = P.user_id');
        }

        if (!empty($params['q'])) {
            $q = $sql->escape(str_replace('*', '%', strtolower($params['q'])));
            $sql->where(
                '(' .
                "LOWER(U.user_id) LIKE '" . $q . "' " .
                "OR LOWER(user_name) LIKE '" . $q . "' " .
                "OR LOWER(user_firstname) LIKE '" . $q . "' " .
                ')'
            );
        }

        if (!empty($params['user_id'])) {
            $sql->where('U.user_id = ' . $sql->quote($params['user_id']));
        }

        if (!$count_only) {
            $sql->group(array('U.user_id', 'user_super', 'user_status', 'user_pwd', 'user_change_pwd', 'user_name', 'user_firstname', 'user_displayname', 'user_email', 'user_url', 'user_desc', 'user_lang', 'user_tz', 'user_post_status', 'user_options'));

            if (!empty($params['order']) && !$count_only) {
                if (preg_match('`^([^. ]+) (?:asc|desc)`i', $params['order'], $matches)) {
                    if (in_array($matches[1], array('user_id', 'user_name', 'user_firstname', 'user_displayname'))) {
                        $table_prefix = 'U.';
                    } else {
                        $table_prefix = ''; // order = nb_post (asc|desc)
                    }
                    $sql->order($table_prefix . $sql->escape($params['order']));
                } else {
                    $sql->order($sql->escape($params['order']));
                }
            } else {
                $sql->order('U.user_id ASC');
            }
        }

        if (!$count_only && !empty($params['limit'])) {
            $sql->limit($params['limit']);
        }

        $rs = $this->con->select($sql->statement());
        $rs->extend('rsExtUser');
        return $rs;
    }

    /**
    Create a new user. Takes a cursor as input and returns the new user ID.

    @param    cur        <b>cursor</b>        User cursor
    @return    <b>string</b>
     */
    public function addUser($cur)
    {
        if (!$this->auth->isSuperAdmin()) {
            throw new Exception(__('You are not an administrator'));
        }

        if ($cur->user_id == '') {
            throw new Exception(__('No user ID given'));
        }

        if ($cur->user_pwd == '') {
            throw new Exception(__('No password given'));
        }

        $this->getUserCursor($cur);

        if ($cur->user_creadt === null) {
            $cur->user_creadt = date('Y-m-d H:i:s');
        }

        $cur->insert();

        $this->auth->afterAddUser($cur);

        return $cur->user_id;
    }

    /**
    Updates an existing user. Returns the user ID.

    @param    id        <b>string</b>        User ID
    @param    cur        <b>cursor</b>        User cursor
    @return    <b>string</b>
     */
    public function updUser($id, $cur)
    {
        $this->getUserCursor($cur);

        if (($cur->user_id !== null || $id != $this->auth->userID()) &&
            !$this->auth->isSuperAdmin()) {
            throw new Exception(__('You are not an administrator'));
        }

        $sql = new dcUpdateStatement($this, 'coreUpdUser');
        $sql
            ->where('user_id = ' . $sql->quote($id));

        $cur->update($sql->whereStatement());

        $this->auth->afterUpdUser($id, $cur);

        if ($cur->user_id !== null) {
            $id = $cur->user_id;
        }

        # Updating all user's blogs
        $sql = new dcSelectStatement($this, 'coreUpdUser');
        $sql
            ->columns('DISTINCT(blog_id)')
            ->from($this->prefix . 'post')
            ->where('user_id = ' . $sql->quote($id));

        $rs = $this->con->select($sql->statement());

        while ($rs->fetch()) {
            $b = new dcBlog($this, $rs->blog_id);
            $b->triggerBlog();
            unset($b);
        }

        return $id;
    }

    /**
    Deletes a user.

    @param    id        <b>string</b>        User ID
     */
    public function delUser($id)
    {
        if (!$this->auth->isSuperAdmin()) {
            throw new Exception(__('You are not an administrator'));
        }

        if ($id == $this->auth->userID()) {
            return;
        }

        $rs = $this->getUser($id);

        if ($rs->nb_post > 0) {
            return;
        }

        $sql = new dcDeleteStatement($this, 'coreDelUser');
        $sql
            ->from($this->prefix . 'user')
            ->where('user_id = ' . $sql->quote($id));

        $this->con->execute($sql->statement());

        $this->auth->afterDelUser($id);
    }

    /**
    Checks whether a user exists.

    @param    id        <b>string</b>        User ID
    @return    <b>boolean</b>
     */
    public function userExists($id)
    {
        $sql = new dcSelectStatement($this, 'coreUserExists');
        $sql
            ->columns('user_id')
            ->from($this->prefix . 'user')
            ->where('user_id = ' . $sql->quote($id));

        $rs = $this->con->select($sql->statement());

        return !$rs->isEmpty();
    }

    /**
    Returns all user permissions as an array which looks like:

    - [blog_id]
    - [name] => Blog name
    - [url] => Blog URL
    - [p]
    - [permission] => true
    - ...

    @param    id        <b>string</b>        User ID
    @return    <b>array</b>
     */
    public function getUserPermissions($id)
    {
        $sql = new dcSelectStatement($this, 'coreGetUserPermissions');
        $sql
            ->columns(array('B.blog_id', 'blog_name', 'blog_url', 'permissions'))
            ->from($this->prefix . 'permissions P')
            ->join('INNER JOIN ' . $this->prefix . 'blog B ON P.blog_id = B.blog_id')
            ->where('user_id = ' . $sql->quote($id));

        $rs = $this->con->select($sql->statement());

        $res = array();

        while ($rs->fetch()) {
            $res[$rs->blog_id] = array(
                'name' => $rs->blog_name,
                'url'  => $rs->blog_url,
                'p'    => $this->auth->parsePermissions($rs->permissions)
            );
        }

        return $res;
    }

    /**
    Sets user permissions. The <var>$perms</var> array looks like:

    - [blog_id] => '|perm1|perm2|'
    - ...

    @param    id        <b>string</b>        User ID
    @param    perms    <b>array</b>        Permissions array
     */
    public function setUserPermissions($id, $perms)
    {
        if (!$this->auth->isSuperAdmin()) {
            throw new Exception(__('You are not an administrator'));
        }

        $sql = new dcDeleteStatement($this, 'coreSetUserPermissions');
        $sql
            ->from($this->prefix . 'permissions')
            ->where('user_id = ' . $sql->quote($id));

        $this->con->execute($sql->statement());

        foreach ($perms as $blog_id => $p) {
            $this->setUserBlogPermissions($id, $blog_id, $p, false);
        }
    }

    /**
    Sets user permissions for a given blog. <var>$perms</var> is an array with
    permissions in values

    @param    id            <b>string</b>        User ID
    @param    blog_id        <b>string</b>        Blog ID
    @param    perms        <b>array</b>        Permissions
    @param    delete_first    <b>boolean</b>        Delete permissions before
     */
    public function setUserBlogPermissions($id, $blog_id, $perms, $delete_first = true)
    {
        if (!$this->auth->isSuperAdmin()) {
            throw new Exception(__('You are not an administrator'));
        }

        $no_perm = empty($perms);

        $perms = '|' . implode('|', array_keys($perms)) . '|';

        $cur = $this->con->openCursor($this->prefix . 'permissions');

        $cur->user_id     = (string) $id;
        $cur->blog_id     = (string) $blog_id;
        $cur->permissions = $perms;

        if ($delete_first || $no_perm) {
            $sql = new dcDeleteStatement($this, 'coreSetUserBlogPermissions');
            $sql
                ->from($this->prefix . 'permissions')
                ->where(array(
                    'blog_id = ' . $sql->quote($blog_id),
                    'user_id = ' . $sql->quote($id)
                ));
            $this->con->execute($sql->statement());
        }

        if (!$no_perm) {
            $cur->insert();
        }
    }

    /**
    Sets a user default blog. This blog will be selected when user log in.

    @param    id            <b>string</b>        User ID
    @param    blog_id        <b>string</b>        Blog ID
     */
    public function setUserDefaultBlog($id, $blog_id)
    {
        $cur = $this->con->openCursor($this->prefix . 'user');

        $sql = new dcUpdateStatement($this, 'coreSetUserDefaultBlog');
        $sql->where('user_id = ' . $sql->quote($id));

        $cur->user_default_blog = (string) $blog_id;

        $cur->update($sql->whereStatement());
    }

    private function getUserCursor($cur)
    {
        if ($cur->isField('user_id')
            && !preg_match('/^[A-Za-z0-9@._-]{2,}$/', $cur->user_id)) {
            throw new Exception(__('User ID must contain at least 2 characters using letters, numbers or symbols.'));
        }

        if ($cur->user_url !== null && $cur->user_url != '') {
            if (!preg_match('|^http(s?)://|', $cur->user_url)) {
                $cur->user_url = 'http://' . $cur->user_url;
            }
        }

        if ($cur->isField('user_pwd')) {
            if (strlen($cur->user_pwd) < 6) {
                throw new Exception(__('Password must contain at least 6 characters.'));
            }
            $cur->user_pwd = $this->auth->crypt($cur->user_pwd);
        }

        if ($cur->user_lang !== null && !preg_match('/^[a-z]{2}(-[a-z]{2})?$/', $cur->user_lang)) {
            throw new Exception(__('Invalid user language code'));
        }

        if ($cur->user_upddt === null) {
            $cur->user_upddt = date('Y-m-d H:i:s');
        }

        if ($cur->user_options !== null) {
            $cur->user_options = serialize((array) $cur->user_options);
        }
    }

    /**
    Returns user default settings in an associative array with setting names in
    keys.

    @return    <b>array</b>
     */
    public function userDefaults()
    {
        return array(
            'edit_size'      => 24,
            'enable_wysiwyg' => true,
            'toolbar_bottom' => false,
            'editor'         => array('xhtml' => 'dcCKEditor', 'wiki' => 'dcLegacyEditor'),
            'post_format'    => 'wiki'
        );
    }
    //@}

    /// @name Blog management methods
    //@{
    /**
    Returns all blog permissions (users) as an array which looks like:

    - [user_id]
    - [name] => User name
    - [firstname] => User firstname
    - [displayname] => User displayname
    - [super] => (true|false) super admin
    - [p]
    - [permission] => true
    - ...

    @param    id            <b>string</b>        Blog ID
    @param    with_super    <b>boolean</b>        Includes super admins in result
    @return    <b>array</b>
     */
    public function getBlogPermissions($id, $with_super = true)
    {
        $sql = new dcSelectStatement($this, 'coreGetBlogPermissions');
        $sql
            ->columns(array('U.user_id AS user_id', 'user_super', 'user_name', 'user_firstname', 'user_displayname',
                'user_email', 'permissions'))
            ->from($this->prefix . 'user U')
            ->join('JOIN ' . $this->prefix . 'permissions P ON U.user_id = P.user_id')
            ->where('blog_id = ' . $sql->quote($id));

        if ($with_super) {
            $sqlSuper = new dcSelectStatement($this, 'coreGetBlogPermissionsSuper');
            $sqlSuper
                ->columns(array('U.user_id AS user_id', 'user_super', 'user_name', 'user_firstname', 'user_displayname',
                    'user_email', 'NULL AS permissions'))
                ->from($this->prefix . 'user U')
                ->where('user_super = 1');
            $sql->sql('UNION ' . $sqlSuper->statement());
        }

        $rs = $this->con->select($sql->statement());

        $res = array();

        while ($rs->fetch()) {
            $res[$rs->user_id] = array(
                'name'        => $rs->user_name,
                'firstname'   => $rs->user_firstname,
                'displayname' => $rs->user_displayname,
                'email'       => $rs->user_email,
                'super'       => (boolean) $rs->user_super,
                'p'           => $this->auth->parsePermissions($rs->permissions)
            );
        }

        return $res;
    }

    /**
    Returns a blog of given ID.

    @param    id        <b>string</b>        Blog ID
    @return    <b>record</b>
     */
    public function getBlog($id)
    {
        $blog = $this->getBlogs(array('blog_id' => $id));

        if ($blog->isEmpty()) {
            return false;
        }

        return $blog;
    }

    /**
    Returns a record of blogs. <b>$params</b> is an array with the following
    optionnal parameters:

    - <var>blog_id</var>: Blog ID
    - <var>q</var>: Search string on blog_id, blog_name and blog_url
    - <var>limit</var>: limit results

    @param    params        <b>array</b>        Parameters
    @param    count_only    <b>boolean</b>        Count only results
    @return    <b>record</b>
     */
    public function getBlogs($params = array(), $count_only = false)
    {
        $sql = new dcSelectStatement($this, 'coreGetBlogs');
        $sql->from($this->prefix . 'blog B');

        if ($count_only) {
            $sql->columns('COUNT(B.blog_id)');
        } else {
            $sql->columns(array('B.blog_id', 'blog_uid', 'blog_url', 'blog_name', 'blog_desc', 'blog_creadt', 'blog_upddt', 'blog_status'));

            if (!empty($params['order'])) {
                $sql->order($sql->escape($params['order']));
            } else {
                $sql->order('B.blog_id ASC');
            }

            if (!empty($params['limit'])) {
                $sql->limit($params['limit']);
            }
        }

        if ($this->auth->userID() && !$this->auth->isSuperAdmin()) {
            $sql->join('INNER JOIN ' . $this->prefix . 'permissions PE ON B.blog_id = PE.blog_id');
            $sql->where("AND PE.user_id = " . $sql->quote($this->auth->userID()) .
                " AND (permissions LIKE '%|usage|%' OR permissions LIKE '%|admin|%' OR permissions LIKE '%|contentadmin|%')" .
                " AND blog_status IN (1,0)");
        } elseif (!$this->auth->userID()) {
            $sql->where('blog_status IN (1,0)');
        }

        if (isset($params['blog_status']) && $params['blog_status'] !== '' && $this->auth->isSuperAdmin()) {
            $sql->where('blog_status = ' . (integer) $params['blog_status']);
        }

        if (isset($params['blog_id']) && $params['blog_id'] !== '') {
            if (!is_array($params['blog_id'])) {
                $params['blog_id'] = array($params['blog_id']);
            }
            $sql->where('B.blog_id ' . $sql->in($params['blog_id']));
        }

        if (!empty($params['q'])) {
            $params['q'] = strtolower(str_replace('*', '%', $params['q']));
            $sql->where('(' .
                "LOWER(B.blog_id) LIKE " . $sql->quote($params['q']) .
                " OR LOWER(B.blog_name) LIKE " . $sql->quote($params['q']) .
                " OR LOWER(B.blog_url) LIKE " . $sql->quote($params['q']) .
                ')');
        }

        return $this->con->select($sql->statement());
    }

    /**
    Creates a new blog.

    @param    cur            <b>cursor</b>        Blog cursor
     */
    public function addBlog($cur)
    {
        if (!$this->auth->isSuperAdmin()) {
            throw new Exception(__('You are not an administrator'));
        }

        $this->getBlogCursor($cur);

        $cur->blog_creadt = date('Y-m-d H:i:s');
        $cur->blog_upddt  = date('Y-m-d H:i:s');
        $cur->blog_uid    = md5(uniqid());

        $cur->insert();
    }

    /**
    Updates a given blog.

    @param    id        <b>string</b>        Blog ID
    @param    cur        <b>cursor</b>        Blog cursor
     */
    public function updBlog($id, $cur)
    {
        $this->getBlogCursor($cur);

        $cur->blog_upddt = date('Y-m-d H:i:s');

        $sql = new dcUpdateStatement($this, 'coreUpdBlog');
        $sql->where('blog_id = ' . $sql->quote($id));
        $cur->update($sql->whereStatement());
    }

    private function getBlogCursor($cur)
    {
        if (($cur->blog_id !== null
            && !preg_match('/^[A-Za-z0-9._-]{2,}$/', $cur->blog_id)) ||
            (!$cur->blog_id)) {
            throw new Exception(__('Blog ID must contain at least 2 characters using letters, numbers or symbols.'));
        }

        if (($cur->blog_name !== null && $cur->blog_name == '') ||
            (!$cur->blog_name)) {
            throw new Exception(__('No blog name'));
        }

        if (($cur->blog_url !== null && $cur->blog_url == '') ||
            (!$cur->blog_url)) {
            throw new Exception(__('No blog URL'));
        }

        if ($cur->blog_desc !== null) {
            $cur->blog_desc = html::clean($cur->blog_desc);
        }
    }

    /**
    Removes a given blog.
    @warning This will remove everything related to the blog (posts,
    categories, comments, links...)

    @param    id        <b>string</b>        Blog ID
     */
    public function delBlog($id)
    {
        if (!$this->auth->isSuperAdmin()) {
            throw new Exception(__('You are not an administrator'));
        }

        $sql = new dcDeleteStatement($this, 'coreDelBlog');
        $sql
            ->from($this->prefix . 'blog')
            ->where('blog_id = ' . $sql->quote($id));

        $this->con->execute($sql->statement());
    }

    /**
    Checks if a blog exist.

    @param    id        <b>string</b>        Blog ID
    @return    <b>boolean</b>
     */
    public function blogExists($id)
    {
        $sql = new dcSelectStatement($this, 'coreBlogExists');
        $sql
            ->columns('blog_id')
            ->from($this->prefix . 'blog')
            ->where('blog_id = ' . $sql->quote($id));

        $rs = $this->con->select($sql->statement());

        return !$rs->isEmpty();
    }

    /**
    Count posts on a blog

    @param    id        <b>string</b>        Blog ID
    @param    type        <b>string</b>        Post type
    @return    <b>boolean</b>
     */
    public function countBlogPosts($id, $type = null)
    {
        $sql = new dcSelectStatement($this, 'coreCountBlogPosts');
        $sql
            ->columns('COUNT(post_id)')
            ->from($this->prefix . 'post')
            ->where('blog_id = ' . $sql->quote($id));

        if ($type) {
            $sql->where('post_type = ' . $sql->quote($type));
        }

        return $this->con->select($sql->statement())->f(0);
    }
    //@}

    /// @name HTML Filter methods
    //@{
    /**
    Calls HTML filter to drop bad tags and produce valid XHTML output (if
    tidy extension is present). If <b>enable_html_filter</b> blog setting is
    false, returns not filtered string.

    @param    str    <b>string</b>        String to filter
    @return    <b>string</b> Filtered string.
     */
    public function HTMLfilter($str)
    {
        if ($this->blog instanceof dcBlog && !$this->blog->settings->system->enable_html_filter) {
            return $str;
        }

        $filter = new htmlFilter;
        $str    = trim($filter->apply($str));
        return $str;
    }
    //@}

    /// @name wiki2xhtml methods
    //@{
    private function initWiki()
    {
        $this->wiki2xhtml = new wiki2xhtml;
    }

    /**
    Returns a transformed string with wiki2xhtml.

    @param    str        <b>string</b>        String to transform
    @return    <b>string</b>    Transformed string
     */
    public function wikiTransform($str)
    {
        if (!($this->wiki2xhtml instanceof wiki2xhtml)) {
            $this->initWiki();
        }
        return $this->wiki2xhtml->transform($str);
    }

    /**
    Inits <var>wiki2xhtml</var> property for blog post.
     */
    public function initWikiPost()
    {
        $this->initWiki();

        $this->wiki2xhtml->setOpts(array(
            'active_title'        => 1,
            'active_setext_title' => 0,
            'active_hr'           => 1,
            'active_lists'        => 1,
            'active_quote'        => 1,
            'active_pre'          => 1,
            'active_aside'        => 1,
            'active_empty'        => 1,
            'active_auto_br'      => 0,
            'active_auto_urls'    => 0,
            'active_urls'         => 1,
            'active_auto_img'     => 0,
            'active_img'          => 1,
            'active_anchor'       => 1,
            'active_em'           => 1,
            'active_strong'       => 1,
            'active_br'           => 1,
            'active_q'            => 1,
            'active_code'         => 1,
            'active_acronym'      => 1,
            'active_ins'          => 1,
            'active_del'          => 1,
            'active_footnotes'    => 1,
            'active_wikiwords'    => 0,
            'active_macros'       => 1,
            'active_mark'         => 1,
            'parse_pre'           => 1,
            'active_fr_syntax'    => 0,
            'first_title_level'   => 3,
            'note_prefix'         => 'wiki-footnote',
            'note_str'            => '<div class="footnotes"><h4>Notes</h4>%s</div>',
            'img_style_center'    => 'display:table; margin:0 auto;'
        ));

        $this->wiki2xhtml->registerFunction('url:post', array($this, 'wikiPostLink'));

        # --BEHAVIOR-- coreWikiPostInit
        $this->callBehavior('coreInitWikiPost', $this->wiki2xhtml);
    }

    /**
    Inits <var>wiki2xhtml</var> property for simple blog comment (basic syntax).
     */
    public function initWikiSimpleComment()
    {
        $this->initWiki();

        $this->wiki2xhtml->setOpts(array(
            'active_title'        => 0,
            'active_setext_title' => 0,
            'active_hr'           => 0,
            'active_lists'        => 0,
            'active_quote'        => 0,
            'active_pre'          => 0,
            'active_aside'        => 0,
            'active_empty'        => 0,
            'active_auto_br'      => 1,
            'active_auto_urls'    => 1,
            'active_urls'         => 0,
            'active_auto_img'     => 0,
            'active_img'          => 0,
            'active_anchor'       => 0,
            'active_em'           => 0,
            'active_strong'       => 0,
            'active_br'           => 0,
            'active_q'            => 0,
            'active_code'         => 0,
            'active_acronym'      => 0,
            'active_ins'          => 0,
            'active_del'          => 0,
            'active_footnotes'    => 0,
            'active_wikiwords'    => 0,
            'active_macros'       => 0,
            'active_mark'         => 0,
            'parse_pre'           => 0,
            'active_fr_syntax'    => 0
        ));

        # --BEHAVIOR-- coreInitWikiSimpleComment
        $this->callBehavior('coreInitWikiSimpleComment', $this->wiki2xhtml);
    }

    /**
    Inits <var>wiki2xhtml</var> property for blog comment.
     */
    public function initWikiComment()
    {
        $this->initWiki();

        $this->wiki2xhtml->setOpts(array(
            'active_title'        => 0,
            'active_setext_title' => 0,
            'active_hr'           => 0,
            'active_lists'        => 1,
            'active_quote'        => 0,
            'active_pre'          => 1,
            'active_aside'        => 0,
            'active_empty'        => 0,
            'active_auto_br'      => 1,
            'active_auto_urls'    => 1,
            'active_urls'         => 1,
            'active_auto_img'     => 0,
            'active_img'          => 0,
            'active_anchor'       => 0,
            'active_em'           => 1,
            'active_strong'       => 1,
            'active_br'           => 1,
            'active_q'            => 1,
            'active_code'         => 1,
            'active_acronym'      => 1,
            'active_ins'          => 1,
            'active_del'          => 1,
            'active_footnotes'    => 0,
            'active_wikiwords'    => 0,
            'active_macros'       => 0,
            'active_mark'         => 1,
            'parse_pre'           => 0,
            'active_fr_syntax'    => 0
        ));

        # --BEHAVIOR-- coreInitWikiComment
        $this->callBehavior('coreInitWikiComment', $this->wiki2xhtml);
    }

    public function wikiPostLink($url, $content)
    {
        if (!($this->blog instanceof dcBlog)) {
            return array();
        }

        $post_id = abs((integer) substr($url, 5));
        if (!$post_id) {
            return array();
        }

        $post = $this->blog->getPosts(array('post_id' => $post_id));
        if ($post->isEmpty()) {
            return array();
        }

        $res        = array('url' => $post->getURL());
        $post_title = $post->post_title;

        if ($content != $url) {
            $res['title'] = html::escapeHTML($post->post_title);
        }

        if ($content == '' || $content == $url) {
            $res['content'] = html::escapeHTML($post->post_title);
        }

        if ($post->post_lang) {
            $res['lang'] = $post->post_lang;
        }

        return $res;
    }
    //@}

    /// @name Maintenance methods
    //@{
    /**
    Creates default settings for active blog. Optionnal parameter
    <var>defaults</var> replaces default params while needed.

    @param    defaults        <b>array</b>    Default parameters
     */
    public function blogDefaults($defaults = null)
    {
        if (!is_array($defaults)) {
            $defaults = array(
                array('allow_comments', 'boolean', true,
                    'Allow comments on blog'),
                array('allow_trackbacks', 'boolean', true,
                    'Allow trackbacks on blog'),
                array('blog_timezone', 'string', 'Europe/London',
                    'Blog timezone'),
                array('comments_nofollow', 'boolean', true,
                    'Add rel="nofollow" to comments URLs'),
                array('comments_pub', 'boolean', true,
                    'Publish comments immediately'),
                array('comments_ttl', 'integer', 0,
                    'Number of days to keep comments open (0 means no ttl)'),
                array('copyright_notice', 'string', '', 'Copyright notice (simple text)'),
                array('date_format', 'string', '%A, %B %e %Y',
                    'Date format. See PHP strftime function for patterns'),
                array('editor', 'string', '',
                    'Person responsible of the content'),
                array('enable_html_filter', 'boolean', 0,
                    'Enable HTML filter'),
                array('enable_xmlrpc', 'boolean', 0,
                    'Enable XML/RPC interface'),
                array('lang', 'string', 'en',
                    'Default blog language'),
                array('media_exclusion', 'string', '/\.(phps?|pht(ml)?|phl|s?html?|js|htaccess)[0-9]*$/i',
                    'File name exclusion pattern in media manager. (PCRE value)'),
                array('media_img_m_size', 'integer', 448,
                    'Image medium size in media manager'),
                array('media_img_s_size', 'integer', 240,
                    'Image small size in media manager'),
                array('media_img_t_size', 'integer', 100,
                    'Image thumbnail size in media manager'),
                array('media_img_title_pattern', 'string', 'Title ;; Date(%b %Y) ;; separator(, )',
                    'Pattern to set image title when you insert it in a post'),
                array('media_video_width', 'integer', 400,
                    'Video width in media manager'),
                array('media_video_height', 'integer', 300,
                    'Video height in media manager'),
                array('media_flash_fallback', 'boolean', true,
                    'Flash player fallback for audio and video media'),
                array('nb_post_for_home', 'integer', 20,
                    'Number of entries on first home page'),
                array('nb_post_per_page', 'integer', 20,
                    'Number of entries on home pages and category pages'),
                array('nb_post_per_feed', 'integer', 20,
                    'Number of entries on feeds'),
                array('nb_comment_per_feed', 'integer', 20,
                    'Number of comments on feeds'),
                array('post_url_format', 'string', '{y}/{m}/{d}/{t}',
                    'Post URL format. {y}: year, {m}: month, {d}: day, {id}: post id, {t}: entry title'),
                array('public_path', 'string', 'public',
                    'Path to public directory, begins with a / for a full system path'),
                array('public_url', 'string', '/public',
                    'URL to public directory'),
                array('robots_policy', 'string', 'INDEX,FOLLOW',
                    'Search engines robots policy'),
                array('short_feed_items', 'boolean', false,
                    'Display short feed items'),
                array('theme', 'string', 'berlin',
                    'Blog theme'),
                array('themes_path', 'string', 'themes',
                    'Themes root path'),
                array('themes_url', 'string', '/themes',
                    'Themes root URL'),
                array('time_format', 'string', '%H:%M',
                    'Time format. See PHP strftime function for patterns'),
                array('tpl_allow_php', 'boolean', false,
                    'Allow PHP code in templates'),
                array('tpl_use_cache', 'boolean', true,
                    'Use template caching'),
                array('trackbacks_pub', 'boolean', true,
                    'Publish trackbacks immediately'),
                array('trackbacks_ttl', 'integer', 0,
                    'Number of days to keep trackbacks open (0 means no ttl)'),
                array('url_scan', 'string', 'query_string',
                    'URL handle mode (path_info or query_string)'),
                array('use_smilies', 'boolean', false,
                    'Show smilies on entries and comments'),
                array('no_search', 'boolean', false,
                    'Disable search'),
                array('inc_subcats', 'boolean', false,
                    'Include sub-categories in category page and category posts feed'),
                array('wiki_comments', 'boolean', false,
                    'Allow commenters to use a subset of wiki syntax'),
                array('import_feed_url_control', 'boolean', true,
                    'Control feed URL before import'),
                array('import_feed_no_private_ip', 'boolean', true,
                    'Prevent import feed from private IP'),
                array('import_feed_ip_regexp', 'string', '',
                    'Authorize import feed only from this IP regexp'),
                array('import_feed_port_regexp', 'string', '/^(80|443)$/',
                    'Authorize import feed only from this port regexp')
            );
        }

        $settings = new dcSettings($this, null);
        $settings->addNamespace('system');

        foreach ($defaults as $v) {
            $settings->system->put($v[0], $v[2], $v[1], $v[3], false, true);
        }
    }

    /**
    Recreates entries search engine index.

    @param    start    <b>integer</b>        Start entry index
    @param    limit    <b>integer</b>        Number of entry to index

    @return    <b>integer</b>        <var>$start</var> and <var>$limit</var> sum
     */
    public function indexAllPosts($start = null, $limit = null)
    {
        $sql = new dcSelectStatement($this, 'coreIndexAllPostsCount');
        $sql
            ->columns('COUNT(post_id)')
            ->from($this->prefix . 'post');

        $rs    = $this->con->select($sql->statement());
        $count = $rs->f(0);
        unset($sql);

        $sql = new dcSelectStatement($this, 'coreIndexAllPosts');
        $sql
            ->columns(array('post_id', 'post_title', 'post_excerpt_xhtml', 'post_content_xhtml'))
            ->from($this->prefix . 'post');

        if ($start !== null && $limit !== null) {
            $sql->limit($start, $limit);
        }

        $rs = $this->con->select($sql->statement());
        unset($sql);

        $cur = $this->con->openCursor($this->prefix . 'post');

        $sql = new dcUpdateStatement($this, 'coreIndexAllPosts');
        while ($rs->fetch()) {
            $words = $rs->post_title . ' ' . $rs->post_excerpt_xhtml . ' ' .
            $rs->post_content_xhtml;

            $cur->post_words = implode(' ', text::splitWords($words));
            $sql->where('post_id = ' . (integer) $rs->post_id, true);
            $cur->update($sql->whereStatement());
            $cur->clean();
        }

        if ($start + $limit > $count) {
            return;
        }
        return $start + $limit;
    }

    /**
    Recreates comments search engine index.

    @param    start    <b>integer</b>        Start comment index
    @param    limit    <b>integer</b>        Number of comments to index

    @return    <b>integer</b>        <var>$start</var> and <var>$limit</var> sum
     */
    public function indexAllComments($start = null, $limit = null)
    {
        $sql = new dcSelectStatement($this, 'coreIndexAllCommentsCount');
        $sql
            ->columns('COUNT(comment_id)')
            ->from($this->prefix . 'comment');

        $rs    = $this->con->select($sql->statement());
        $count = $rs->f(0);
        unset($sql);

        $sql = new dcSelectStatement($this, 'coreIndexAllComments');
        $sql
            ->columns(array('comment_id', 'comment_content'))
            ->from($this->prefix . 'comment');

        if ($start !== null && $limit !== null) {
            $sql->limit($start, $limit);
        }

        $rs = $this->con->select($sql->statement());
        unset($sql);

        $cur = $this->con->openCursor($this->prefix . 'comment');

        $sql = new dcUpdateStatement($this, 'coreIndexAllComments');
        while ($rs->fetch()) {
            $cur->comment_words = implode(' ', text::splitWords($rs->comment_content));
            $sql->where('comment_id = ' . (integer) $rs->comment_id, true);
            $cur->update($sql->whereStatement());
            $cur->clean();
        }

        if ($start + $limit > $count) {
            return;
        }
        return $start + $limit;
    }

    /**
    Reinits nb_comment and nb_trackback in post table.
     */
    public function countAllComments()
    {
        $sqlCount = new dcSelectStatement($this, 'coreCountAllComments');
        $sqlCount
            ->columns('COUNT(C.comment_id)')
            ->from($this->prefix . 'comment C')
            ->where(array(
                'C.post_id = P.post_id',
                'C.comment_status = 1'
            ));

        $sql = new dcUpdateStatement($this, 'coreCountAllComments');
        $sql
            ->ref($this->prefix . 'post P');

        $sqlCount->cond('AND C.comment_trackback <> 1', true);
        $sql->set('nb_comment = (' . $sqlCount->statement() . ')', true);
        $this->con->execute($sql->statement());

        $sqlCount->cond('AND C.comment_trackback = 1', true);
        $sql->set('nb_trackback = (' . $sqlCount->statement() . ')', true);
        $this->con->execute($sql->statement());
    }

    /**
    Empty templates cache directory
     */
    public function emptyTemplatesCache()
    {
        if (is_dir(DC_TPL_CACHE . '/cbtpl')) {
            files::deltree(DC_TPL_CACHE . '/cbtpl');
        }
    }

    /**
    Return elapsed time since script has been started
    @param      $mtime <b>float</b> timestamp (microtime format) to evaluate delta from
    current time is taken if null
    @return <b>float</b>         elapsed time
     */
    public function getElapsedTime($mtime = null)
    {
        if ($mtime !== null) {
            return $mtime - $this->stime;
        } else {
            return microtime(true) - $this->stime;
        }
    }
    //@}

}
