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
    public $con;        ///< <b>connection</b>          Database connection object
    public $prefix;     ///< <b>string</b>              Database tables prefix
    public $blog;       ///< <b>dcBlog</b>              dcBlog object
    public $error;      ///< <b>dcError</b>             dcError object
    public $auth;       ///< <b>dcAuth</b>              dcAuth object
    public $session;    ///< <b>sessionDB</b>           sessionDB object
    public $url;        ///< <b>urlHandler</b>          urlHandler object
    public $wiki2xhtml; ///< <b>wiki2xhtml</b>          wiki2xhtml object
    public $plugins;    ///< <b>dcModules</b>           dcModules object
    public $themes;     ///< <b>dcThemes</b>            dcThemes object
    public $media;      ///< <b>dcMedia</b>             dcMedia object
    public $postmedia;  ///< <b>dcPostMedia</b>         dcPostMedia object
    public $rest;       ///< <b>dcRestServer</b>        dcRestServer object
    public $log;        ///< <b>dcLog</b>               dcLog object
    public $stime;      ///< <b>float</b>               starting time
    public $meta;       ///< <b>dcMeta</b>              dcMeta object

    public $adminurl;   ///< <b>dcAdminURL</b>          dcAdminURL object
    public $notices;    ///< <b>dcAdminNotices</b>      dcNotices object
    public $favs;       ///< <b>dcFavorites</b>         dcFavorites object

    private $versions   = null;
    private $formaters  = [];
    private $behaviors  = [];
    private $post_types = [];

    /**
     * dcCore constructor inits everything related to Dotclear. It takes arguments
     * to init database connection.
     *
     * @param      string  $driver    The db driver
     * @param      string  $host      The db host
     * @param      string  $db        The db name
     * @param      string  $user      The db user
     * @param      string  $password  The db password
     * @param      string  $prefix    The tables prefix
     * @param      bool    $persist   Persistent database connection
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
        if ($this->con instanceof mysqliConnection) {
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
        if (!is_null($ttl)) {   // @phpstan-ignore-line
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
     * Sets the blog to use.
     *
     * @param      string  $id     The blog ID
     */
    public function setBlog($id)
    {
        $this->blog = new dcBlog($this, $id);
    }

    /**
     * Unsets blog property
     */
    public function unsetBlog()
    {
        $this->blog = null;
    }
    //@}

    /// @name Blog status methods
    //@{
    /**
     * Gets all blog status.
     *
     * @return     array  An array of available blog status codes and names.
     */
    public function getAllBlogStatus()
    {
        return [
            1  => __('online'),
            0  => __('offline'),
            -1 => __('removed')
        ];
    }

    /**
     * Returns a blog status name given to a code. This is intended to be
     * human-readable and will be translated, so never use it for tests.
     * If status code does not exist, returns <i>offline</i>.
     *
     * @param      integer  $s      Status code
     *
     * @return     string   The blog status name.
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

    /**
     * Gets the nonce.
     *
     * @return     string  The nonce.
     */
    public function getNonce()
    {
        return $this->auth->cryptLegacy(session_id());
    }

    /**
     * Check the nonce
     *
     * @param      string  $secret  The nonce
     *
     * @return     bool
     */
    public function checkNonce($secret)
    {
        // 40 alphanumeric characters min
        if (!preg_match('/^([0-9a-f]{40,})$/i', $secret)) {
            return false;
        }

        return $secret == $this->auth->cryptLegacy(session_id());
    }

    /**
     * Get the nonce HTML code
     *
     * @return     mixed
     */
    public function formNonce()
    {
        if (!session_id()) {
            return;
        }

        return form::hidden(['xd_check'], $this->getNonce());
    }
    //@}

    /// @name Text Formatters methods
    //@{
    /**
     * Adds a new text formater which will call the function <var>$func</var> to
     * transform text. The function must be a valid callback and takes one
     * argument: the string to transform. It returns the transformed string.
     *
     * @param      string    $editor_id  The editor identifier (dcLegacyEditor, dcCKEditor, ...)
     * @param      string    $name       The formater name
     * @param      callable  $func       The function to use, must be a valid and callable callback
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

    /**
     * Adds a new dcLegacyEditor text formater which will call the function
     * <var>$func</var> to transform text. The function must be a valid callback
     * and takes one argument: the string to transform. It returns the transformed string.
     *
     * @param      string    $name       The formater name
     * @param      callable  $func       The function to use, must be a valid and callable callback
     */
    public function addFormater($name, $func)
    {
        $this->addEditorFormater('dcLegacyEditor', $name, $func);
    }

    /**
     * Gets the editors list.
     *
     * @return     array  The editors.
     */
    public function getEditors()
    {
        $editors = [];

        foreach (array_keys($this->formaters) as $editor_id) {
            $editors[$editor_id] = $this->plugins->moduleInfo($editor_id, 'name');
        }

        return $editors;
    }

    /**
    Returns formaters list by editor

    @param    editor_id    <b>string</b>    Editor id
    @return    <b>array</b> An array of formaters names in values.

    /**
     */
    /**
     * Gets the formaters.
     *
     * if @param editor_id is empty:
     * return all formaters sorted by actives editors
     *
     * if @param editor_id is not empty
     * return formaters for an editor if editor is active
     * return empty() array if editor is not active.
     * It can happens when a user choose an editor and admin deactivate that editor later
     *
     * @param      string  $editor_id  The editor identifier (dcLegacyEditor, dcCKEditor, ...)
     *
     * @return     array   The formaters.
     */
    public function getFormaters($editor_id = '')
    {
        $formaters_list = [];

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
     * If <var>$name</var> is a valid formater, it returns <var>$str</var>
     * transformed using that formater.
     *
     * @param      string  $editor_id  The editor identifier (dcLegacyEditor, dcCKEditor, ...)
     * @param      string  $name       The formater name
     * @param      string  $str        The string to transform
     *
     * @return     string
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
     * If <var>$name</var> is a valid dcLegacyEditor formater, it returns
     * <var>$str</var> transformed using that formater.
     *
     * @param      string  $name   The name
     * @param      string  $str    The string
     *
     * @return     string
     */
    public function callFormater($name, $str)
    {
        return $this->callEditorFormater('dcLegacyEditor', $name, $str);
    }
    //@}

    /// @name Behaviors methods
    //@{
    /**
     * Adds a new behavior to behaviors stack. <var>$func</var> must be a valid
     * and callable callback.
     *
     * @param      string    $behavior  The behavior
     * @param      callable  $func      The function
     */
    public function addBehavior($behavior, $func)
    {
        if (is_callable($func)) {
            $this->behaviors[$behavior][] = $func;
        }
    }

    /**
     * Determines if behavior exists in behaviors stack.
     *
     * @param      string  $behavior  The behavior
     *
     * @return     bool    True if behavior exists, False otherwise.
     */
    public function hasBehavior($behavior)
    {
        return isset($this->behaviors[$behavior]);
    }

    /**
     * Gets the behaviors stack (or part of).
     *
     * @param      string  $behavior  The behavior
     *
     * @return     mixed   The behaviors.
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

        return [];
    }

    /**
     * Calls every function in behaviors stack for a given behavior and returns
     * concatened result of each function.
     *
     * Every parameters added after <var>$behavior</var> will be pass to
     * behavior calls.
     *
     * @param      string  $behavior  The behavior
     * @param      mixed   ...$args   The arguments
     *
     * @return     mixed   Behavior concatened result
     */
    public function callBehavior($behavior, ...$args)
    {
        if (isset($this->behaviors[$behavior])) {
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

    /**
     * Gets the post admin url.
     *
     * @param      string  $type     The type
     * @param      mixed   $post_id  The post identifier
     * @param      bool    $escaped  Escape the URL
     *
     * @return     string    The post admin url.
     */
    public function getPostAdminURL($type, $post_id, $escaped = true)
    {
        if (!isset($this->post_types[$type])) {
            $type = 'post';
        }

        $url = sprintf($this->post_types[$type]['admin_url'], $post_id);

        return $escaped ? html::escapeURL($url) : $url;
    }

    /**
     * Gets the post public url.
     *
     * @param      string  $type      The type
     * @param      string  $post_url  The post url
     * @param      bool    $escaped   Escape the URL
     *
     * @return     string    The post public url.
     */
    public function getPostPublicURL($type, $post_url, $escaped = true)
    {
        if (!isset($this->post_types[$type])) {
            $type = 'post';
        }

        $url = sprintf($this->post_types[$type]['public_url'], $post_url);

        return $escaped ? html::escapeURL($url) : $url;
    }

    /**
     * Sets the post type.
     *
     * @param      string  $type        The type
     * @param      string  $admin_url   The admin url
     * @param      string  $public_url  The public url
     * @param      string  $label       The label
     */
    public function setPostType($type, $admin_url, $public_url, $label = '')
    {
        $this->post_types[$type] = [
            'admin_url'  => $admin_url,
            'public_url' => $public_url,
            'label'      => ($label != '' ? $label : $type)
        ];
    }

    /**
     * Gets the post types.
     *
     * @return     array  The post types.
     */
    public function getPostTypes()
    {
        return $this->post_types;
    }
    //@}

    /// @name Versions management methods
    //@{
    /**
     * Gets the version of a module.
     *
     * @param      string  $module  The module
     *
     * @return     mixed  The version.
     */
    public function getVersion($module = 'core')
    {
        # Fetch versions if needed
        if (!is_array($this->versions)) {
            $strReq = 'SELECT module, version FROM ' . $this->prefix . 'version';
            $rs     = $this->con->select($strReq);

            while ($rs->fetch()) {
                $this->versions[$rs->module] = $rs->version;
            }
        }

        if (isset($this->versions[$module])) {
            return $this->versions[$module];
        }
    }

    /**
     * Sets the version of a module.
     *
     * @param      string  $module   The module
     * @param      string  $version  The version
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
            $cur->update("WHERE module='" . $this->con->escape($module) . "'");
        }

        $this->versions[$module] = $version;
    }

    /**
     * Remove a module version entry
     *
     * @param      string  $module  The module
     */
    public function delVersion($module)
    {
        $strReq = 'DELETE FROM ' . $this->prefix . 'version ' .
        "WHERE module = '" . $this->con->escape($module) . "' ";

        $this->con->execute($strReq);

        if (is_array($this->versions)) {
            unset($this->versions[$module]);
        }
    }

    //@}

    /// @name Users management methods
    //@{
    /**
     * Gets the user by its ID.
     *
     * @param      string  $id     The identifier
     *
     * @return     record  The user.
     */
    public function getUser($id)
    {
        $params['user_id'] = $id;

        return $this->getUsers($params);
    }

    /**
     * Returns a users list. <b>$params</b> is an array with the following
     * optionnal parameters:
     *
     * - <var>q</var>: search string (on user_id, user_name, user_firstname)
     * - <var>user_id</var>: user ID
     * - <var>order</var>: ORDER BY clause (default: user_id ASC)
     * - <var>limit</var>: LIMIT clause (should be an array ![limit,offset])
     *
     * @param      array|ArrayObject    $params      The parameters
     * @param      bool                 $count_only  Count only results
     *
     * @return     record  The users.
     */
    public function getUsers($params = [], $count_only = false)
    {
        if ($count_only) {
            $strReq = 'SELECT count(U.user_id) ' .
            'FROM ' . $this->prefix . 'user U ' .
                'WHERE NULL IS NULL ';
        } else {
            $strReq = 'SELECT U.user_id,user_super,user_status,user_pwd,user_change_pwd,' .
                'user_name,user_firstname,user_displayname,user_email,user_url,' .
                'user_desc, user_lang,user_tz, user_post_status,user_options, ' .
                'count(P.post_id) AS nb_post ';
            if (!empty($params['columns'])) {
                $strReq .= ',';
                if (is_array($params['columns'])) {
                    $strReq .= implode(',', $params['columns']);
                } else {
                    $strReq .= $params['columns'];
                }
                $strReq .= ' ';
            }
            $strReq .= 'FROM ' . $this->prefix . 'user U ' .
            'LEFT JOIN ' . $this->prefix . 'post P ON U.user_id = P.user_id ' .
                'WHERE NULL IS NULL ';
        }

        if (!empty($params['q'])) {
            $q = $this->con->escape(str_replace('*', '%', strtolower($params['q'])));
            $strReq .= 'AND (' .
                "LOWER(U.user_id) LIKE '" . $q . "' " .
                "OR LOWER(user_name) LIKE '" . $q . "' " .
                "OR LOWER(user_firstname) LIKE '" . $q . "' " .
                ') ';
        }

        if (!empty($params['user_id'])) {
            $strReq .= "AND U.user_id = '" . $this->con->escape($params['user_id']) . "' ";
        }

        if (!$count_only) {
            $strReq .= 'GROUP BY U.user_id,user_super,user_status,user_pwd,user_change_pwd,' .
                'user_name,user_firstname,user_displayname,user_email,user_url,' .
                'user_desc, user_lang,user_tz,user_post_status,user_options ';

            if (!empty($params['order'])) {
                if (preg_match('`^([^. ]+) (?:asc|desc)`i', $params['order'], $matches)) {
                    if (in_array($matches[1], ['user_id', 'user_name', 'user_firstname', 'user_displayname'])) {
                        $table_prefix = 'U.';
                    } else {
                        $table_prefix = ''; // order = nb_post (asc|desc)
                    }
                    $strReq .= 'ORDER BY ' . $table_prefix . $this->con->escape($params['order']) . ' ';
                } else {
                    $strReq .= 'ORDER BY ' . $this->con->escape($params['order']) . ' ';
                }
            } else {
                $strReq .= 'ORDER BY U.user_id ASC ';
            }
        }

        if (!$count_only && !empty($params['limit'])) {
            $strReq .= $this->con->limit($params['limit']);
        }
        $rs = $this->con->select($strReq);
        $rs->extend('rsExtUser');

        return $rs;
    }

    /**
     * Adds a new user. Takes a cursor as input and returns the new user ID.
     *
     * @param      cursor     $cur    The user cursor
     *
     * @throws     Exception
     *
     * @return     string
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
     * Updates an existing user. Returns the user ID.
     *
     * @param      string     $id     The user identifier
     * @param      cursor     $cur    The cursor
     *
     * @throws     Exception
     *
     * @return     string
     */
    public function updUser($id, $cur)
    {
        $this->getUserCursor($cur);

        if (($cur->user_id !== null || $id != $this->auth->userID()) && !$this->auth->isSuperAdmin()) {
            throw new Exception(__('You are not an administrator'));
        }

        $cur->update("WHERE user_id = '" . $this->con->escape($id) . "' ");

        $this->auth->afterUpdUser($id, $cur);

        if ($cur->user_id !== null) {
            $id = $cur->user_id;
        }

        # Updating all user's blogs
        $rs = $this->con->select(
            'SELECT DISTINCT(blog_id) FROM ' . $this->prefix . 'post ' .
            "WHERE user_id = '" . $this->con->escape($id) . "' "
        );

        while ($rs->fetch()) {
            $b = new dcBlog($this, $rs->blog_id);
            $b->triggerBlog();
            unset($b);
        }

        return $id;
    }

    /**
     * Deletes a user.
     *
     * @param      string     $id     The user identifier
     *
     * @throws     Exception
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

        $strReq = 'DELETE FROM ' . $this->prefix . 'user ' .
        "WHERE user_id = '" . $this->con->escape($id) . "' ";

        $this->con->execute($strReq);

        $this->auth->afterDelUser($id);
    }

    /**
     * Determines if user exists.
     *
     * @param      string  $id     The identifier
     *
     * @return      bool  True if user exists, False otherwise.
     */
    public function userExists($id)
    {
        $strReq = 'SELECT user_id ' .
        'FROM ' . $this->prefix . 'user ' .
        "WHERE user_id = '" . $this->con->escape($id) . "' ";

        $rs = $this->con->select($strReq);

        return !$rs->isEmpty();
    }

    /**
     * Returns all user permissions as an array which looks like:
     *
     * - [blog_id]
     * - [name] => Blog name
     * - [url] => Blog URL
     * - [p]
     * - [permission] => true
     * - ...
     *
     * @param      string  $id     The user identifier
     *
     * @return     array   The user permissions.
     */
    public function getUserPermissions($id)
    {
        $strReq = 'SELECT B.blog_id, blog_name, blog_url, permissions ' .
        'FROM ' . $this->prefix . 'permissions P ' .
        'INNER JOIN ' . $this->prefix . 'blog B ON P.blog_id = B.blog_id ' .
        "WHERE user_id = '" . $this->con->escape($id) . "' ";

        $rs = $this->con->select($strReq);

        $res = [];

        while ($rs->fetch()) {
            $res[$rs->blog_id] = [
                'name' => $rs->blog_name,
                'url'  => $rs->blog_url,
                'p'    => $this->auth->parsePermissions($rs->permissions)
            ];
        }

        return $res;
    }

    /**
     * Sets user permissions. The <var>$perms</var> array looks like:
     *
     * - [blog_id] => '|perm1|perm2|'
     * - ...
     *
     * @param      string     $id     The user identifier
     * @param      array      $perms  The permissions
     *
     * @throws     Exception
     */
    public function setUserPermissions($id, $perms)
    {
        if (!$this->auth->isSuperAdmin()) {
            throw new Exception(__('You are not an administrator'));
        }

        $strReq = 'DELETE FROM ' . $this->prefix . 'permissions ' .
        "WHERE user_id = '" . $this->con->escape($id) . "' ";

        $this->con->execute($strReq);

        foreach ($perms as $blog_id => $p) {
            $this->setUserBlogPermissions($id, $blog_id, $p, false);
        }
    }

    /**
     * Sets the user blog permissions.
     *
     * @param      string     $id            The user identifier
     * @param      string     $blog_id       The blog identifier
     * @param      array      $perms         The permissions
     * @param      bool       $delete_first  Delete permissions first
     *
     * @throws     Exception  (description)
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
            $strReq = 'DELETE FROM ' . $this->prefix . 'permissions ' .
            "WHERE blog_id = '" . $this->con->escape($blog_id) . "' " .
            "AND user_id = '" . $this->con->escape($id) . "' ";

            $this->con->execute($strReq);
        }

        if (!$no_perm) {
            $cur->insert();
        }
    }

    /**
     * Sets the user default blog. This blog will be selected when user log in.
     *
     * @param      string  $id       The user identifier
     * @param      string  $blog_id  The blog identifier
     */
    public function setUserDefaultBlog($id, $blog_id)
    {
        $cur = $this->con->openCursor($this->prefix . 'user');

        $cur->user_default_blog = (string) $blog_id;

        $cur->update("WHERE user_id = '" . $this->con->escape($id) . "'");
    }

    /**
     * Gets the user cursor.
     *
     * @param      cursor     $cur    The user cursor
     *
     * @throws     Exception
     */
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
     * Returns user default settings in an associative array with setting names in keys.
     *
     * @return     array
     */
    public function userDefaults()
    {
        return [
            'edit_size'      => 24,
            'enable_wysiwyg' => true,
            'toolbar_bottom' => false,
            'editor'         => ['xhtml' => 'dcCKEditor', 'wiki' => 'dcLegacyEditor'],
            'post_format'    => 'xhtml'
        ];
    }
    //@}

    /// @name Blog management methods
    //@{
    /**
     * Returns all blog permissions (users) as an array which looks like:
     *
     * - [user_id]
     * - [name] => User name
     * - [firstname] => User firstname
     * - [displayname] => User displayname
     * - [super] => (true|false) super admin
     * - [p]
     * - [permission] => true
     * - ...
     *
     * @param      string  $id          The blog identifier
     * @param      bool    $with_super  Includes super admins in result
     *
     * @return     array   The blog permissions.
     */
    public function getBlogPermissions($id, $with_super = true)
    {
        $strReq = 'SELECT U.user_id AS user_id, user_super, user_name, user_firstname, ' .
        'user_displayname, user_email, permissions ' .
        'FROM ' . $this->prefix . 'user U ' .
        'JOIN ' . $this->prefix . 'permissions P ON U.user_id = P.user_id ' .
        "WHERE blog_id = '" . $this->con->escape($id) . "' ";

        if ($with_super) {
            $strReq .= 'UNION ' .
            'SELECT U.user_id AS user_id, user_super, user_name, user_firstname, ' .
            'user_displayname, user_email, NULL AS permissions ' .
            'FROM ' . $this->prefix . 'user U ' .
                'WHERE user_super = 1 ';
        }

        $rs = $this->con->select($strReq);

        $res = [];

        while ($rs->fetch()) {
            $res[$rs->user_id] = [
                'name'        => $rs->user_name,
                'firstname'   => $rs->user_firstname,
                'displayname' => $rs->user_displayname,
                'email'       => $rs->user_email,
                'super'       => (boolean) $rs->user_super,
                'p'           => $this->auth->parsePermissions($rs->permissions)
            ];
        }

        return $res;
    }

    /**
     * Gets the blog.
     *
     * @param      string  $id     The blog identifier
     *
     * @return     mixed    The blog.
     */
    public function getBlog($id)
    {
        $blog = $this->getBlogs(['blog_id' => $id]);

        if ($blog->isEmpty()) {
            return false;
        }

        return $blog;
    }

    /**
     * Returns a record of blogs. <b>$params</b> is an array with the following
     * optionnal parameters:
     *
     * - <var>blog_id</var>: Blog ID
     * - <var>q</var>: Search string on blog_id, blog_name and blog_url
     * - <var>limit</var>: limit results
     *
     * @param      array|ArrayObject    $params      The parameters
     * @param      bool                 $count_only  Count only results
     *
     * @return     record  The blogs.
     */
    public function getBlogs($params = [], $count_only = false)
    {
        $join  = ''; // %1$s
        $where = ''; // %2$s

        if ($count_only) {
            $strReq = 'SELECT count(B.blog_id) ' .
            'FROM ' . $this->prefix . 'blog B ' .
                '%1$s ' .
                'WHERE NULL IS NULL ' .
                '%2$s ';
        } else {
            $strReq = 'SELECT B.blog_id, blog_uid, blog_url, blog_name, blog_desc, blog_creadt, ' .
                'blog_upddt, blog_status ';
            if (!empty($params['columns'])) {
                $strReq .= ',';
                if (is_array($params['columns'])) {
                    $strReq .= implode(',', $params['columns']);
                } else {
                    $strReq .= $params['columns'];
                }
                $strReq .= ' ';
            }
            $strReq .= 'FROM ' . $this->prefix . 'blog B ' .
                '%1$s ' .
                'WHERE NULL IS NULL ' .
                '%2$s ';

            if (!empty($params['order'])) {
                $strReq .= 'ORDER BY ' . $this->con->escape($params['order']) . ' ';
            } else {
                $strReq .= 'ORDER BY B.blog_id ASC ';
            }

            if (!empty($params['limit'])) {
                $strReq .= $this->con->limit($params['limit']);
            }
        }

        if ($this->auth->userID() && !$this->auth->isSuperAdmin()) {
            $join  = 'INNER JOIN ' . $this->prefix . 'permissions PE ON B.blog_id = PE.blog_id ';
            $where = "AND PE.user_id = '" . $this->con->escape($this->auth->userID()) . "' " .
                "AND (permissions LIKE '%|usage|%' OR permissions LIKE '%|admin|%' OR permissions LIKE '%|contentadmin|%') " .
                'AND blog_status IN (1,0) ';
        } elseif (!$this->auth->userID()) {
            $where = 'AND blog_status IN (1,0) ';
        }

        if (isset($params['blog_status']) && $params['blog_status'] !== '' && $this->auth->isSuperAdmin()) {
            $where .= 'AND blog_status = ' . (integer) $params['blog_status'] . ' ';
        }

        if (isset($params['blog_id']) && $params['blog_id'] !== '') {
            if (!is_array($params['blog_id'])) {
                $params['blog_id'] = [$params['blog_id']];
            }
            $where .= 'AND B.blog_id ' . $this->con->in($params['blog_id']);
        }

        if (!empty($params['q'])) {
            $params['q'] = strtolower(str_replace('*', '%', $params['q']));
            $where .= 'AND (' .
            "LOWER(B.blog_id) LIKE '" . $this->con->escape($params['q']) . "' " .
            "OR LOWER(B.blog_name) LIKE '" . $this->con->escape($params['q']) . "' " .
            "OR LOWER(B.blog_url) LIKE '" . $this->con->escape($params['q']) . "' " .
                ') ';
        }

        $strReq = sprintf($strReq, $join, $where);

        return $this->con->select($strReq);
    }

    /**
     * Adds a new blog.
     *
     * @param      cursor     $cur    The blog cursor
     *
     * @throws     Exception
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
     * Updates a given blog.
     *
     * @param      string  $id     The blog identifier
     * @param      cursor  $cur    The cursor
     */
    public function updBlog($id, $cur)
    {
        $this->getBlogCursor($cur);

        $cur->blog_upddt = date('Y-m-d H:i:s');

        $cur->update("WHERE blog_id = '" . $this->con->escape($id) . "'");
    }

    /**
     * Gets the blog cursor.
     *
     * @param      cursor  $cur    The cursor
     *
     * @throws     Exception
     */
    private function getBlogCursor($cur)
    {
        if (($cur->blog_id !== null
            && !preg_match('/^[A-Za-z0-9._-]{2,}$/', $cur->blog_id)) || (!$cur->blog_id)) {
            throw new Exception(__('Blog ID must contain at least 2 characters using letters, numbers or symbols.'));
        }

        if (($cur->blog_name !== null && $cur->blog_name == '') || (!$cur->blog_name)) {
            throw new Exception(__('No blog name'));
        }

        if (($cur->blog_url !== null && $cur->blog_url == '') || (!$cur->blog_url)) {
            throw new Exception(__('No blog URL'));
        }

        if ($cur->blog_desc !== null) {
            $cur->blog_desc = html::clean($cur->blog_desc);
        }
    }

    /**
     * Removes a given blog.
     * @warning This will remove everything related to the blog (posts,
     * categories, comments, links...)
     *
     * @param      string     $id     The blog identifier
     *
     * @throws     Exception
     */
    public function delBlog($id)
    {
        if (!$this->auth->isSuperAdmin()) {
            throw new Exception(__('You are not an administrator'));
        }

        $strReq = 'DELETE FROM ' . $this->prefix . 'blog ' .
        "WHERE blog_id = '" . $this->con->escape($id) . "' ";

        $this->con->execute($strReq);
    }

    /**
     * Determines if blog exists.
     *
     * @param      string  $id     The blog identifier
     *
     * @return     bool  True if blog exists, False otherwise.
     */
    public function blogExists($id)
    {
        $strReq = 'SELECT blog_id ' .
        'FROM ' . $this->prefix . 'blog ' .
        "WHERE blog_id = '" . $this->con->escape($id) . "' ";

        $rs = $this->con->select($strReq);

        return !$rs->isEmpty();
    }

    /**
     * Counts the number of blog posts.
     *
     * @param      string  $id     The blog identifier
     * @param      mixed   $type   The post type
     *
     * @return     integer  Number of blog posts.
     */
    public function countBlogPosts($id, $type = null)
    {
        $strReq = 'SELECT COUNT(post_id) ' .
        'FROM ' . $this->prefix . 'post ' .
        "WHERE blog_id = '" . $this->con->escape($id) . "' ";

        if ($type) {
            $strReq .= "AND post_type = '" . $this->con->escape($type) . "' ";
        }

        return $this->con->select($strReq)->f(0);
    }
    //@}

    /// @name HTML Filter methods
    //@{
    /**
     * Calls HTML filter to drop bad tags and produce valid XHTML output (if
     * tidy extension is present). If <b>enable_html_filter</b> blog setting is
     * false, returns not filtered string.
     *
     * @param      string  $str    The string
     *
     * @return     string
     */
    public function HTMLfilter($str)
    {
        if ($this->blog instanceof dcBlog && !$this->blog->settings->system->enable_html_filter) {
            return $str;
        }

        $options = new ArrayObject([
            'keep_aria' => false,
            'keep_data' => false,
            'keep_js'   => false
        ]);
        $this->callBehavior('HTMLfilter', $options);

        $filter = new htmlFilter($options['keep_aria'], $options['keep_data'], $options['keep_js']);
        $str    = trim($filter->apply($str));

        return $str;
    }
    //@}

    /// @name wiki2xhtml methods
    //@{

    /**
     * Initializes the wiki2xhtml methods.
     */
    private function initWiki()
    {
        $this->wiki2xhtml = new wiki2xhtml;
    }

    /**
     * Returns a transformed string with wiki2xhtml.
     *
     * @param      string  $str    The string
     *
     * @return     string
     */
    public function wikiTransform($str)
    {
        if (!($this->wiki2xhtml instanceof wiki2xhtml)) {
            $this->initWiki();
        }

        return $this->wiki2xhtml->transform($str);
    }

    /**
     * Inits <var>wiki2xhtml</var> property for blog post.
     */
    public function initWikiPost()
    {
        $this->initWiki();

        $this->wiki2xhtml->setOpts([
            'active_title'        => 1,
            'active_setext_title' => 0,
            'active_hr'           => 1,
            'active_lists'        => 1,
            'active_defl'         => 1,
            'active_quote'        => 1,
            'active_pre'          => 1,
            'active_empty'        => 1,
            'active_auto_urls'    => 0,
            'active_auto_br'      => 0,
            'active_antispam'     => 1,
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
            'active_aside'        => 1,
            'active_sup'          => 1,
            'active_sub'          => 1,
            'active_i'            => 1,
            'active_span'         => 1,
            'parse_pre'           => 1,
            'active_fr_syntax'    => 0,
            'first_title_level'   => 3,
            'note_prefix'         => 'wiki-footnote',
            'note_str'            => '<div class="footnotes"><h4>Notes</h4>%s</div>',
            'img_style_center'    => 'display:table; margin:0 auto;'
        ]);

        $this->wiki2xhtml->registerFunction('url:post', [$this, 'wikiPostLink']);

        # --BEHAVIOR-- coreWikiPostInit
        $this->callBehavior('coreInitWikiPost', $this->wiki2xhtml);
    }

    /**
     * Inits <var>wiki2xhtml</var> property for simple blog comment (basic syntax).
     */
    public function initWikiSimpleComment()
    {
        $this->initWiki();

        $this->wiki2xhtml->setOpts([
            'active_title'        => 0,
            'active_setext_title' => 0,
            'active_hr'           => 0,
            'active_lists'        => 0,
            'active_defl'         => 0,
            'active_quote'        => 0,
            'active_pre'          => 0,
            'active_empty'        => 0,
            'active_auto_urls'    => 1,
            'active_auto_br'      => 1,
            'active_antispam'     => 1,
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
            'active_inline_html'  => 0,
            'active_footnotes'    => 0,
            'active_wikiwords'    => 0,
            'active_macros'       => 0,
            'active_mark'         => 0,
            'active_aside'        => 0,
            'active_sup'          => 0,
            'active_sub'          => 0,
            'active_i'            => 0,
            'active_span'         => 0,
            'parse_pre'           => 0,
            'active_fr_syntax'    => 0
        ]);

        # --BEHAVIOR-- coreInitWikiSimpleComment
        $this->callBehavior('coreInitWikiSimpleComment', $this->wiki2xhtml);
    }

    /**
     * Inits <var>wiki2xhtml</var> property for blog comment.
     */
    public function initWikiComment()
    {
        $this->initWiki();

        $this->wiki2xhtml->setOpts([
            'active_title'        => 0,
            'active_setext_title' => 0,
            'active_hr'           => 0,
            'active_lists'        => 1,
            'active_defl'         => 0,
            'active_quote'        => 1,
            'active_pre'          => 1,
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
            'active_inline_html'  => 0,
            'active_wikiwords'    => 0,
            'active_macros'       => 0,
            'active_mark'         => 1,
            'active_aside'        => 0,
            'active_sup'          => 1,
            'active_sub'          => 1,
            'active_i'            => 1,
            'active_span'         => 0,
            'parse_pre'           => 0,
            'active_fr_syntax'    => 0
        ]);

        # --BEHAVIOR-- coreInitWikiComment
        $this->callBehavior('coreInitWikiComment', $this->wiki2xhtml);
    }

    /**
     * Get info about a post:id wiki macro
     *
     * @param      string  $url      The post url
     * @param      string  $content  The content
     *
     * @return     array
     */
    public function wikiPostLink($url, $content)
    {
        if (!($this->blog instanceof dcBlog)) {
            return [];
        }

        $post_id = abs((integer) substr($url, 5));
        if (!$post_id) {
            return [];
        }

        $post = $this->blog->getPosts(['post_id' => $post_id]);
        if ($post->isEmpty()) {
            return [];
        }

        $res        = ['url' => $post->getURL()];
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
     * Creates default settings for active blog. Optionnal parameter
     * <var>defaults</var> replaces default params while needed.
     *
     * @param      array  $defaults  The defaults settings
     */
    public function blogDefaults($defaults = null)
    {
        if (!is_array($defaults)) {
            $defaults = [
                ['allow_comments', 'boolean', true,
                    'Allow comments on blog'],
                ['allow_trackbacks', 'boolean', true,
                    'Allow trackbacks on blog'],
                ['blog_timezone', 'string', 'Europe/London',
                    'Blog timezone'],
                ['comments_nofollow', 'boolean', true,
                    'Add rel="nofollow" to comments URLs'],
                ['comments_pub', 'boolean', true,
                    'Publish comments immediately'],
                ['comments_ttl', 'integer', 0,
                    'Number of days to keep comments open (0 means no ttl)'],
                ['copyright_notice', 'string', '', 'Copyright notice (simple text)'],
                ['date_format', 'string', '%A, %B %e %Y',
                    'Date format. See PHP strftime function for patterns'],
                ['editor', 'string', '',
                    'Person responsible of the content'],
                ['enable_html_filter', 'boolean', 0,
                    'Enable HTML filter'],
                ['enable_xmlrpc', 'boolean', 0,
                    'Enable XML/RPC interface'],
                ['lang', 'string', 'en',
                    'Default blog language'],
                ['media_exclusion', 'string', '/\.(phps?|pht(ml)?|phl|.?html?|xml|js|htaccess)[0-9]*$/i',
                    'File name exclusion pattern in media manager. (PCRE value)'],
                ['media_img_m_size', 'integer', 448,
                    'Image medium size in media manager'],
                ['media_img_s_size', 'integer', 240,
                    'Image small size in media manager'],
                ['media_img_t_size', 'integer', 100,
                    'Image thumbnail size in media manager'],
                ['media_img_title_pattern', 'string', 'Title ;; Date(%b %Y) ;; separator(, )',
                    'Pattern to set image title when you insert it in a post'],
                ['media_video_width', 'integer', 400,
                    'Video width in media manager'],
                ['media_video_height', 'integer', 300,
                    'Video height in media manager'],
                ['nb_post_for_home', 'integer', 20,
                    'Number of entries on first home page'],
                ['nb_post_per_page', 'integer', 20,
                    'Number of entries on home pages and category pages'],
                ['nb_post_per_feed', 'integer', 20,
                    'Number of entries on feeds'],
                ['nb_comment_per_feed', 'integer', 20,
                    'Number of comments on feeds'],
                ['post_url_format', 'string', '{y}/{m}/{d}/{t}',
                    'Post URL format. {y}: year, {m}: month, {d}: day, {id}: post id, {t}: entry title'],
                ['public_path', 'string', 'public',
                    'Path to public directory, begins with a / for a full system path'],
                ['public_url', 'string', '/public',
                    'URL to public directory'],
                ['robots_policy', 'string', 'INDEX,FOLLOW',
                    'Search engines robots policy'],
                ['short_feed_items', 'boolean', false,
                    'Display short feed items'],
                ['theme', 'string', 'berlin',
                    'Blog theme'],
                ['themes_path', 'string', 'themes',
                    'Themes root path'],
                ['themes_url', 'string', '/themes',
                    'Themes root URL'],
                ['time_format', 'string', '%H:%M',
                    'Time format. See PHP strftime function for patterns'],
                ['tpl_allow_php', 'boolean', false,
                    'Allow PHP code in templates'],
                ['tpl_use_cache', 'boolean', true,
                    'Use template caching'],
                ['trackbacks_pub', 'boolean', true,
                    'Publish trackbacks immediately'],
                ['trackbacks_ttl', 'integer', 0,
                    'Number of days to keep trackbacks open (0 means no ttl)'],
                ['url_scan', 'string', 'query_string',
                    'URL handle mode (path_info or query_string)'],
                ['use_smilies', 'boolean', false,
                    'Show smilies on entries and comments'],
                ['no_search', 'boolean', false,
                    'Disable search'],
                ['inc_subcats', 'boolean', false,
                    'Include sub-categories in category page and category posts feed'],
                ['wiki_comments', 'boolean', false,
                    'Allow commenters to use a subset of wiki syntax'],
                ['import_feed_url_control', 'boolean', true,
                    'Control feed URL before import'],
                ['import_feed_no_private_ip', 'boolean', true,
                    'Prevent import feed from private IP'],
                ['import_feed_ip_regexp', 'string', '',
                    'Authorize import feed only from this IP regexp'],
                ['import_feed_port_regexp', 'string', '/^(80|443)$/',
                    'Authorize import feed only from this port regexp'],
                ['jquery_needed', 'boolean', true,
                    'Load jQuery library']
            ];
        }

        $settings = new dcSettings($this, null);
        $settings->addNamespace('system');

        foreach ($defaults as $v) {
            $settings->system->put($v[0], $v[2], $v[1], $v[3], false, true);
        }
    }

    /**
     * Recreates entries search engine index.
     *
     * @param      mixed   $start  The start entry index
     * @param      mixed   $limit  The limit of entry to index
     *
     * @return     mixed   sum of <var>$start</var> and <var>$limit</var>
     */
    public function indexAllPosts($start = null, $limit = null)
    {
        $strReq = 'SELECT COUNT(post_id) ' .
        'FROM ' . $this->prefix . 'post';
        $rs    = $this->con->select($strReq);
        $count = $rs->f(0);

        $strReq = 'SELECT post_id, post_title, post_excerpt_xhtml, post_content_xhtml ' .
        'FROM ' . $this->prefix . 'post ';

        if ($start !== null && $limit !== null) {
            $strReq .= $this->con->limit($start, $limit);
        }

        $rs = $this->con->select($strReq, true);

        $cur = $this->con->openCursor($this->prefix . 'post');

        while ($rs->fetch()) {
            $words = $rs->post_title . ' ' . $rs->post_excerpt_xhtml . ' ' .
            $rs->post_content_xhtml;

            $cur->post_words = implode(' ', text::splitWords($words));
            $cur->update('WHERE post_id = ' . (integer) $rs->post_id);
            $cur->clean();
        }

        if ($start + $limit > $count) {
            return;
        }

        return $start + $limit;
    }

    /**
     * Recreates comments search engine index.
     *
     * @param      mixed   $start  The start comment index
     * @param      mixed   $limit  The limit of comment to index
     *
     * @return     mixed   sum of <var>$start</var> and <var>$limit</var>
     */
    public function indexAllComments($start = null, $limit = null)
    {
        $strReq = 'SELECT COUNT(comment_id) ' .
        'FROM ' . $this->prefix . 'comment';
        $rs    = $this->con->select($strReq);
        $count = $rs->f(0);

        $strReq = 'SELECT comment_id, comment_content ' .
        'FROM ' . $this->prefix . 'comment ';

        if ($start !== null && $limit !== null) {
            $strReq .= $this->con->limit($start, $limit);
        }

        $rs = $this->con->select($strReq);

        $cur = $this->con->openCursor($this->prefix . 'comment');

        while ($rs->fetch()) {
            $cur->comment_words = implode(' ', text::splitWords($rs->comment_content));
            $cur->update('WHERE comment_id = ' . (integer) $rs->comment_id);
            $cur->clean();
        }

        if ($start + $limit > $count) {
            return;
        }

        return $start + $limit;
    }

    /**
     * Reinits nb_comment and nb_trackback in post table.
     */
    public function countAllComments()
    {
        $updCommentReq = 'UPDATE ' . $this->prefix . 'post P ' .
        'SET nb_comment = (' .
        'SELECT COUNT(C.comment_id) from ' . $this->prefix . 'comment C ' .
            'WHERE C.post_id = P.post_id AND C.comment_trackback <> 1 ' .
            'AND C.comment_status = 1 ' .
            ')';
        $updTrackbackReq = 'UPDATE ' . $this->prefix . 'post P ' .
        'SET nb_trackback = (' .
        'SELECT COUNT(C.comment_id) from ' . $this->prefix . 'comment C ' .
            'WHERE C.post_id = P.post_id AND C.comment_trackback = 1 ' .
            'AND C.comment_status = 1 ' .
            ')';
        $this->con->execute($updCommentReq);
        $this->con->execute($updTrackbackReq);
    }

    /**
     * Empty templates cache directory
     */
    public function emptyTemplatesCache()
    {
        if (is_dir(DC_TPL_CACHE . '/cbtpl')) {
            files::deltree(DC_TPL_CACHE . '/cbtpl');
        }
    }

    /**
     * Return elapsed time since script has been started
     *
     * @param      mixed   $mtime  timestamp (microtime format) to evaluate
     * delta from current time is taken if null
     *
     * @return     mixed   The elapsed time.
     */
    public function getElapsedTime($mtime = null)
    {
        if ($mtime !== null) {
            return $mtime - $this->stime;
        }

        return microtime(true) - $this->stime;
    }
    //@}
}
