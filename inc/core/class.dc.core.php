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

use Dotclear\App;
use Dotclear\Database\Statement\DeleteStatement;
use Dotclear\Database\Statement\JoinStatement;
use Dotclear\Database\Statement\SelectStatement;
use Dotclear\Database\Statement\UpdateStatement;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Html\HtmlFilter;
use Dotclear\Helper\Html\Template\Template;
use Dotclear\Helper\Html\WikiToHtml;
use Dotclear\Helper\Text;

final class dcCore
{
    use dcTraitDynamicProperties;

    // Constants

    /**
     * Session table name
     *
     * @var string
     */
    public const SESSION_TABLE_NAME = 'session';

    /**
     * Versions table name
     *
     * @var string
     */
    public const VERSION_TABLE_NAME = 'version';

    // Properties

    /**
     * dcCore singleton instance
     *
     * @var dcCore|null
     */
    private static $instance = null;

    /**
     * Database connection
     *
     * @var dbLayer
     */
    public $con;

    /**
     * Database tables prefix
     *
     * @var string
     */
    public $prefix;

    /**
     * dcBlog instance
     *
     * @var dcBlog|null
     */
    public $blog;

    /**
     * dcAuth instance
     *
     * @var dcAuth|null
     */
    public $auth;

    /**
     * sessionDB instance
     *
     * @var sessionDB
     */
    public $session;

    /**
     * dcUrlHandlers instance
     *
     * @var dcUrlHandlers
     */
    public $url;

    /**
     * dcRestServer instance
     *
     * @var dcRestServer
     */
    public $rest;

    /**
     * WikiToHtml instance
     *
     * @var WikiToHtml
     */
    public $wiki;

    /**
     * WikiToHtml instance
     *
     * alias of $this->wiki
     *
     * @var WikiToHtml
     */
    public $wiki2xhtml;

    /**
     * Plugins
     *
     * @var dcPlugins
     */
    public $plugins;

    /**
     * Themes
     *
     * @var dcThemes
     */
    public $themes;

    /**
     * dcMedia instance
     *
     * @var dcMedia|null
     */
    public $media;

    /**
     * dcPostMedia instance
     *
     * @var dcPostMedia
     */
    public $postmedia;

    /**
     * dcMeta instance
     *
     * @var dcMeta
     */
    public $meta;

    /**
     * dcError instance
     *
     * @var dcError
     */
    public $error;

    /**
     * dcNotices instance
     *
     * @var dcNotices
     */
    public $notices;

    /**
     * dcLog instance
     *
     * @var dcLog
     */
    public $log;

    /**
     * Starting time
     *
     * @var float
     */
    public $stime;

    /**
     * Current language
     *
     * @var string
     */
    public $lang;

    /**
     * Php namespace autoloader
     *
     * @var Autoloader
     *
     * @deprecated since 2.26, use App::autoload() instead
     */
    public $autoload;

    // Admin context

    /**
     * dcAdmin instance
     *
     * @var dcAdmin
     */
    public $admin;

    /**
     * dcAdminURL instance
     *
     * May be transfered as property of dcAdmin instance in future
     *
     * @var dcAdminURL|null
     */
    public $adminurl;

    /**
     * dcFavorites instance
     *
     * May be transfered as property of dcAdmin instance in future
     *
     * @var dcFavorites
     */
    public $favs;

    /**
     * Array of several dcMenu instance
     *
     * May be transfered as property of dcAdmin instance in future
     *
     * @var ArrayObject
     */
    public $menu;

    /**
     * Array of resources
     *
     * May be transfered as property of dcAdmin instance in future
     *
     * @var array
     */
    public $resources = [];

    // Public context

    /**
     * dcPublic instance
     *
     * @var dcPublic
     */
    public $public;

    /**
     * dcTemplate instance
     *
     * May be transfered as property of dcPublic instance in future
     *
     * @var dcTemplate
     */
    public $tpl;

    /**
     * context instance
     *
     * May be transfered as property of dcPublic instance in future
     *
     * @var context|null
     */
    public $ctx;

    /**
     * HTTP Cache stack
     *
     * May be transfered as property of dcPublic instance in future
     *
     * @var array
     */
    public $cache = [
        'mod_files' => [],
        'mod_ts'    => [],
    ];

    /**
     * Array of antispam filters (names)
     *
     * May be transfered as property of dcPublic instance in future
     *
     * @var array|null
     */
    public $spamfilters = [];

    // Private

    /**
     * Stack of registered versions (core, modules)
     *
     * @var       array|null
     */
    private $versions = null;

    /**
     * Stack of registered content formaters
     *
     * @var        array
     */
    private $formaters = [];

    /**
     * Stack of registered content formaters' name
     *
     * @var        array
     */
    private $formaters_names = [];

    /**
     * Stack of registered behaviors
     *
     * @var        array
     */
    private $behaviors = [];

    /**
     * List of known post types
     *
     * @var        array
     */
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
    public function __construct(string $driver, string $host, string $db, string $user, string $password, string $prefix, bool $persist)
    {
        // Singleton mode
        if (self::$instance) {
            throw new Exception('Application can not be started twice.', 500);
        }
        self::$instance = $this;

        if (defined('DC_START_TIME')) {
            $this->stime = DC_START_TIME;
        } else {
            $this->stime = microtime(true);
        }

        // Deprecated since 2.26
        $this->autoload = App::autoload();

        $this->con = dbLayer::init($driver, $host, $db, $user, $password, $persist);

        // Define weak_locks for mysql
        // Begin test by mysqlimb4Connection as it extends mysqliConnection
        if ($this->con instanceof mysqlimb4Connection) {
            mysqlimb4Connection::$weak_locks = true;
        } elseif ($this->con instanceof mysqliConnection) {
            mysqliConnection::$weak_locks = true;
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
            if (substr(trim((string) $ttl), 0, 1) != '-') {
                // Clearbricks requires negative session TTL
                $ttl = '-' . trim((string) $ttl);
            }
        }

        $this->error   = new dcError();
        $this->auth    = $this->authInstance();
        $this->session = new sessionDB($this->con, $this->prefix . self::SESSION_TABLE_NAME, DC_SESSION_NAME, '', null, DC_ADMIN_SSL, $ttl);
        $this->url     = new dcUrlHandlers();
        $this->plugins = new dcPlugins();
        $this->rest    = new dcRestServer();
        $this->meta    = new dcMeta();
        $this->log     = new dcLog();

        if (defined('DC_CONTEXT_ADMIN')) {
            /*
             * @deprecated Since 2.23, use dcCore::app()->resources instead
             */
            $GLOBALS['__resources'] = &$this->resources;
        }
    }

    /**
     * Get dcCore singleton instance
     *
     * @return     dcCore
     */
    public static function app(): dcCore
    {
        return self::$instance;
    }

    /**
     * Create a new instance of authentication class (user-defined or default)
     *
     * @throws     Exception
     *
     * @return     dcAuth|mixed
     */
    private function authInstance()
    {
        // You can set DC_AUTH_CLASS to whatever you want.
        // Your new class *should* inherits dcAuth.
        $class = defined('DC_AUTH_CLASS') ? DC_AUTH_CLASS : dcAuth::class;

        if (!class_exists($class)) {
            throw new Exception('Authentication class ' . $class . ' does not exist.');
        }

        if ($class !== dcAuth::class && !is_subclass_of($class, dcAuth::class)) {
            throw new Exception('Authentication class ' . $class . ' does not inherit dcAuth.');
        }

        return new $class($this);
    }

    /**
     * Kill admin session helper
     */
    public function killAdminSession(): void
    {
        // Kill session
        dcCore::app()->session->destroy();

        // Unset cookie if necessary
        if (isset($_COOKIE['dc_admin'])) {
            unset($_COOKIE['dc_admin']);
            setcookie('dc_admin', '', -600, '', '', DC_ADMIN_SSL);
        }
    }

    /// @name Blog init methods
    //@{
    /**
     * Sets the blog to use.
     *
     * @param      string  $id     The blog ID
     */
    public function setBlog($id): void
    {
        $this->blog = new dcBlog($id);
    }

    /**
     * Unsets blog property
     */
    public function unsetBlog(): void
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
    public function getAllBlogStatus(): array
    {
        return [
            dcBlog::BLOG_ONLINE  => __('online'),
            dcBlog::BLOG_OFFLINE => __('offline'),
            dcBlog::BLOG_REMOVED => __('removed'),
        ];
    }

    /**
     * Returns a blog status name given to a code. This is intended to be
     * human-readable and will be translated, so never use it for tests.
     * If status code does not exist, returns <i>offline</i>.
     *
     * @param      int      $s      Status code
     *
     * @return     string   The blog status name.
     */
    public function getBlogStatus(int $s): string
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
    public function getNonce(): string
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
    public function checkNonce(string $secret): bool
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
     * @param bool  $render     Should render element?
     *
     * @return     mixed
     */
    public function formNonce(bool $render = true)
    {
        if (!session_id()) {
            return;
        }

        $element = new Hidden(['xd_check'], $this->getNonce());

        return $render ? $element->render() : $element;
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
    public function addEditorFormater(string $editor_id, string $name, $func): void
    {
        if (is_callable($func)) {
            $this->formaters[$editor_id][$name] = $func;
        }
    }

    /**
     * Adds a new dcLegacyEditor text formater which will call the function
     * <var>$func</var> to transform text. The function must be a valid callback
     * and takes one argument: the string to transform. It returns the transformed string.
     *
     * @param      string    $name       The formater name
     * @param      callable  $func       The function to use, must be a valid and callable callback
     */
    public function addFormater(string $name, $func): void
    {
        $this->addEditorFormater('dcLegacyEditor', $name, $func);
    }

    /**
     * Adds a formater name.
     *
     * @param      string  $format  The format
     * @param      string  $name    The name
     */
    public function addFormaterName(string $format, string $name): void
    {
        $this->formaters_names[$format] = $name;
    }

    /**
     * Gets the formater name.
     *
     * @param      string  $format  The format
     *
     * @return     string  The formater name.
     */
    public function getFormaterName(string $format): string
    {
        return $this->formaters_names[$format] ?? $format;
    }

    /**
     * Gets the editors list.
     *
     * @return     array  The editors.
     */
    public function getEditors(): array
    {
        $editors = [];

        foreach (array_keys($this->formaters) as $editor_id) {
            $editors[$editor_id] = $this->plugins->moduleInfo($editor_id, 'name');
        }

        return $editors;
    }

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
    public function getFormaters(string $editor_id = ''): array
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
    public function callEditorFormater(string $editor_id, string $name, string $str): string
    {
        if (isset($this->formaters[$editor_id]) && isset($this->formaters[$editor_id][$name])) {
            return call_user_func($this->formaters[$editor_id][$name], $str);
        }
        // Fallback with another editor if possible
        foreach ($this->formaters as $editor => $formaters) {
            if (array_key_exists($name, $formaters)) {
                return call_user_func($this->formaters[$editor][$name], $str);
            }
        }

        return $str;
    }

    /**
     * If <var>$name</var> is a valid dcLegacyEditor formater, it returns
     * <var>$str</var> transformed using that formater.
     *
     * @param      string  $name   The name
     * @param      string  $str    The string
     *
     * @return     string
     */
    public function callFormater(string $name, string $str): string
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
     * @param      string           $behavior  The behavior
     * @param      callable|array   $func      The function
     */
    public function addBehavior(string $behavior, $func): void
    {
        if (is_callable($func)) {
            $this->behaviors[$behavior][] = $func;
        }
    }

    /**
     * Adds a behaviour (alias).
     *
     * @param      string           $behaviour  The behaviour
     * @param      callable|array   $func       The function
     */
    public function addBehaviour(string $behaviour, $func): void
    {
        $this->addBehavior($behaviour, $func);
    }

    /**
     * Adds new behaviors to behaviors stack. Each row must
     * contains the behavior and a valid callable callback.
     *
     * @param      array    $behaviors  The behaviors
     */
    public function addBehaviors(array $behaviors): void
    {
        foreach ($behaviors as $behavior => $func) {
            $this->addBehavior($behavior, $func);
        }
    }

    /**
     * Adds behaviours (alias).
     *
     * @param      array    $behaviours  The behaviours
     */
    public function addBehaviours(array $behaviours): void
    {
        $this->addBehaviors($behaviours);
    }

    /**
     * Determines if behavior exists in behaviors stack.
     *
     * @param      string  $behavior  The behavior
     *
     * @return     bool    True if behavior exists, False otherwise.
     */
    public function hasBehavior(string $behavior): bool
    {
        return isset($this->behaviors[$behavior]);
    }

    /**
     * Determines if behaviour exists (alias).
     *
     * @param      string  $behaviour  The behavior
     *
     * @return     bool    True if behaviour, False otherwise.
     */
    public function hasBehaviour(string $behaviour): bool
    {
        return $this->hasBehavior($behaviour);
    }

    /**
     * Gets the behaviors stack (or part of).
     *
     * @param      string  $behavior  The behavior
     *
     * @return     mixed   The behaviors.
     */
    public function getBehaviors(string $behavior = '')
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
     * Gets the behaviours stack (alias).
     *
     * @param      string  $behaviour  The behaviour
     *
     * @return     mixed  The behaviours.
     */
    public function getBehaviours(string $behaviour = '')
    {
        return $this->getBehaviors($behaviour);
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
    public function callBehavior(string $behavior, ...$args)
    {
        if (isset($this->behaviors[$behavior])) {
            $res = '';

            foreach ($this->behaviors[$behavior] as $f) {
                $res .= $f(...$args);
            }

            return $res;
        }
    }

    /**
     * Calls every function in behaviours stack (alias of self::callBehavior)
     *
     * @param      string  $behaviour  The behaviour
     * @param      mixed   ...$args    The arguments
     *
     * @return     mixed
     */
    public function callBehaviour(string $behaviour, ...$args)
    {
        return $this->callBehavior($behaviour, ...$args);
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
    public function getPostAdminURL(string $type, $post_id, bool $escaped = true): string
    {
        if (!isset($this->post_types[$type])) {
            $type = 'post';
        }

        $url = sprintf($this->post_types[$type]['admin_url'], $post_id);

        return $escaped ? Html::escapeURL($url) : $url;
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
    public function getPostPublicURL(string $type, string $post_url, bool $escaped = true): string
    {
        if (!isset($this->post_types[$type])) {
            $type = 'post';
        }

        $url = sprintf($this->post_types[$type]['public_url'], $post_url);

        return $escaped ? Html::escapeURL($url) : $url;
    }

    /**
     * Sets the post type.
     *
     * @param      string  $type        The type
     * @param      string  $admin_url   The admin url
     * @param      string  $public_url  The public url
     * @param      string  $label       The label
     */
    public function setPostType(string $type, string $admin_url, string $public_url, string $label = '')
    {
        $this->post_types[$type] = [
            'admin_url'  => $admin_url,
            'public_url' => $public_url,
            'label'      => ($label !== '' ? $label : $type),
        ];
    }

    /**
     * Gets the post types.
     *
     * @return     array  The post types.
     */
    public function getPostTypes(): array
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
    public function getVersion(string $module = 'core')
    {
        # Fetch versions if needed
        if (!is_array($this->versions)) {
            $rs = (new SelectStatement())
                ->columns([
                    'module',
                    'version',
                ])
                ->from($this->prefix . self::VERSION_TABLE_NAME)
                ->select();

            while ($rs->fetch()) {
                $this->versions[$rs->module] = $rs->version;
            }
        }

        if (isset($this->versions[$module])) {
            return $this->versions[$module];
        }
    }

    /**
     * Gets all known versions
     *
     * @return     array  The versions.
     */
    public function getVersions(): array
    {
        // Fetch versions if needed
        if (!is_array($this->versions)) {
            $rs = (new SelectStatement())
                ->columns([
                    'module',
                    'version',
                ])
                ->from($this->prefix . self::VERSION_TABLE_NAME)
                ->select();

            while ($rs->fetch()) {
                $this->versions[$rs->module] = $rs->version;
            }
        }

        return $this->versions;
    }

    /**
     * Sets the version of a module.
     *
     * @param      string  $module   The module
     * @param      string  $version  The version
     */
    public function setVersion(string $module, string $version)
    {
        $cur_version = $this->getVersion($module);

        $cur          = $this->con->openCursor($this->prefix . self::VERSION_TABLE_NAME);
        $cur->module  = $module;
        $cur->version = $version;

        if ($cur_version === null) {
            $cur->insert();
        } else {
            $sql = new UpdateStatement();
            $sql->where('module = ' . $sql->quote($module));

            $sql->update($cur);
        }

        $this->versions[$module] = $version;
    }

    /**
     * Compare the given version of a module with the registered one
     *
     * Returned values:
     *
     * -1 : newer version already installed
     * 0 : same version installed
     * 1 : older version is installed
     *
     * @param      string  $module   The module
     * @param      string  $version  The version
     *
     * @return     int  return the result of the test
     */
    public function testVersion(string $module, string $version): int
    {
        return version_compare($version, (string) $this->getVersion($module));
    }

    /**
     * Test if a version is a new one
     *
     * @param      string  $module   The module
     * @param      string  $version  The version
     *
     * @return     bool
     */
    public function newVersion(string $module, string $version): bool
    {
        return $this->testVersion($module, $version) === 1;
    }

    /**
     * Remove a module version entry
     *
     * @param      string  $module  The module
     */
    public function delVersion(string $module)
    {
        $sql = new DeleteStatement();
        $sql
            ->from($this->prefix . self::VERSION_TABLE_NAME)
            ->where('module = ' . $sql->quote($module));

        $sql->delete();

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
     * @return     dcRecord  The user.
     */
    public function getUser(string $id): dcRecord
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
     * @return     dcRecord  The users.
     */
    public function getUsers($params = [], bool $count_only = false): dcRecord
    {
        $sql = new SelectStatement();

        if ($count_only) {
            $sql
                ->column($sql->count('U.user_id'))
                ->from($sql->as($this->prefix . dcAuth::USER_TABLE_NAME, 'U'))
                ->where('NULL IS NULL');
        } else {
            $sql
                ->columns([
                    'U.user_id',
                    'user_super',
                    'user_status',
                    'user_pwd',
                    'user_change_pwd',
                    'user_name',
                    'user_firstname',
                    'user_displayname',
                    'user_email',
                    'user_url',
                    'user_desc',
                    'user_lang',
                    'user_tz',
                    'user_post_status',
                    'user_options',
                    $sql->count('P.post_id', 'nb_post'),
                ])
                ->from($sql->as($this->prefix . dcAuth::USER_TABLE_NAME, 'U'));

            if (!empty($params['columns'])) {
                $sql->columns($params['columns']);
            }
            $sql
                ->join(
                    (new JoinStatement())
                        ->left()
                        ->from($sql->as($this->prefix . dcBlog::POST_TABLE_NAME, 'P'))
                        ->on('U.user_id = P.user_id')
                        ->statement()
                )
                ->where('NULL IS NULL');
        }

        if (!empty($params['q'])) {
            $q = $sql->escape(str_replace('*', '%', strtolower($params['q'])));
            $sql->andGroup([
                $sql->or($sql->like('LOWER(U.user_id)', $q)),
                $sql->or($sql->like('LOWER(user_name)', $q)),
                $sql->or($sql->like('LOWER(user_firstname)', $q)),
            ]);
        }

        if (!empty($params['user_id'])) {
            $sql->and('U.user_id = ' . $sql->quote($params['user_id']));
        }

        if (!$count_only) {
            $sql->group([
                'U.user_id',
                'user_super',
                'user_status',
                'user_pwd',
                'user_change_pwd',
                'user_name',
                'user_firstname',
                'user_displayname',
                'user_email',
                'user_url',
                'user_desc',
                'user_lang',
                'user_tz',
                'user_post_status',
                'user_options',
            ]);

            if (!empty($params['order'])) {
                if (preg_match('`^([^. ]+) (?:asc|desc)`i', $params['order'], $matches)) {
                    if (in_array($matches[1], ['user_id', 'user_name', 'user_firstname', 'user_displayname'])) {
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

        $rs = $sql->select();
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
    public function addUser(cursor $cur): string
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

        $this->fillUserCursor($cur);

        if ($cur->user_creadt === null) {
            $cur->user_creadt = date('Y-m-d H:i:s');
        }

        $cur->insert();

        # --BEHAVIOR-- coreAfterAddUser -- cursor
        $this->callBehavior('coreAfterAddUser', $cur);

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
    public function updUser(string $id, cursor $cur): string
    {
        $this->fillUserCursor($cur);

        if (($cur->user_id !== null || $id != $this->auth->userID()) && !$this->auth->isSuperAdmin()) {
            throw new Exception(__('You are not an administrator'));
        }

        $sql = new UpdateStatement();
        $sql->where('user_id = ' . $sql->quote($id));

        $sql->update($cur);

        # --BEHAVIOR-- coreAfterUpdUser -- cursor
        $this->callBehavior('coreAfterUpdUser', $cur);

        if ($cur->user_id !== null) {
            $id = $cur->user_id;
        }

        # Updating all user's blogs
        $sql = new SelectStatement();
        $sql
            ->distinct()
            ->column('blog_id')
            ->from($this->prefix . dcBlog::POST_TABLE_NAME)
            ->where('user_id = ' . $sql->quote($id));

        $rs = $sql->select();

        while ($rs->fetch()) {
            $b = new dcBlog($rs->blog_id);
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
    public function delUser(string $id): void
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

        $sql = new DeleteStatement();
        $sql
            ->from($this->prefix . dcAuth::USER_TABLE_NAME)
            ->where('user_id = ' . $sql->quote($id));

        $sql->delete();

        # --BEHAVIOR-- coreAfterDelUser -- string
        $this->callBehavior('coreAfterDelUser', $id);
    }

    /**
     * Determines if user exists.
     *
     * @param      string  $id     The identifier
     *
     * @return      bool  True if user exists, False otherwise.
     */
    public function userExists(string $id): bool
    {
        $sql = new SelectStatement();
        $sql
            ->column('user_id')
            ->from($this->prefix . dcAuth::USER_TABLE_NAME)
            ->where('user_id = ' . $sql->quote($id));

        $rs = $sql->select();

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
    public function getUserPermissions(string $id): array
    {
        $sql = new SelectStatement();
        $sql
            ->columns([
                'B.blog_id',
                'blog_name',
                'blog_url',
                'permissions',
            ])
            ->from($sql->as($this->prefix . dcAuth::PERMISSIONS_TABLE_NAME, 'P'))
            ->join(
                (new JoinStatement())
                ->inner()
                ->from($sql->as($this->prefix . dcBlog::BLOG_TABLE_NAME, 'B'))
                ->on('P.blog_id = B.blog_id')
                ->statement()
            )
            ->where('user_id = ' . $sql->quote($id));

        $rs = $sql->select();

        $res = [];

        while ($rs->fetch()) {
            $res[$rs->blog_id] = [
                'name' => $rs->blog_name,
                'url'  => $rs->blog_url,
                'p'    => $this->auth->parsePermissions($rs->permissions),
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
    public function setUserPermissions(string $id, array $perms): void
    {
        if (!$this->auth->isSuperAdmin()) {
            throw new Exception(__('You are not an administrator'));
        }

        $sql = new DeleteStatement();
        $sql
            ->from($this->prefix . dcAuth::PERMISSIONS_TABLE_NAME)
            ->where('user_id = ' . $sql->quote($id));

        $sql->delete();

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
    public function setUserBlogPermissions(string $id, string $blog_id, array $perms, bool $delete_first = true): void
    {
        if (!$this->auth->isSuperAdmin()) {
            throw new Exception(__('You are not an administrator'));
        }

        $no_perm = empty($perms);

        $perms = '|' . implode('|', array_keys($perms)) . '|';

        $cur = $this->con->openCursor($this->prefix . dcAuth::PERMISSIONS_TABLE_NAME);

        $cur->user_id     = (string) $id;
        $cur->blog_id     = (string) $blog_id;
        $cur->permissions = $perms;

        if ($delete_first || $no_perm) {
            $sql = new DeleteStatement();
            $sql
                ->from($this->prefix . dcAuth::PERMISSIONS_TABLE_NAME)
                ->where('blog_id = ' . $sql->quote($blog_id))
                ->and('user_id = ' . $sql->quote($id));

            $sql->delete();
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
    public function setUserDefaultBlog(string $id, string $blog_id): void
    {
        $cur = $this->con->openCursor($this->prefix . dcAuth::USER_TABLE_NAME);

        $cur->user_default_blog = (string) $blog_id;

        $sql = new UpdateStatement();
        $sql->where('user_id = ' . $sql->quote($id));

        $sql->update($cur);
    }

    /**
     * Removes users default blogs.
     *
     * @param      array  $ids    The blogs to remove
     */
    public function removeUsersDefaultBlogs(array $ids)
    {
        $cur = $this->con->openCursor($this->prefix . dcAuth::USER_TABLE_NAME);

        $cur->user_default_blog = null;

        $sql = new UpdateStatement();
        $sql->where('user_default_blog' . $sql->in($ids));

        $sql->update($cur);
    }

    /**
     * Fills the user cursor.
     *
     * @param      cursor     $cur    The user cursor
     *
     * @throws     Exception
     */
    private function fillUserCursor(cursor $cur)
    {
        if ($cur->isField('user_id')
            && !preg_match('/^[A-Za-z0-9@._-]{2,}$/', (string) $cur->user_id)) {
            throw new Exception(__('User ID must contain at least 2 characters using letters, numbers or symbols.'));
        }

        if ($cur->user_url !== null && $cur->user_url != '') {
            if (!preg_match('|^https?://|', (string) $cur->user_url)) {
                $cur->user_url = 'http://' . $cur->user_url;
            }
        }

        if ($cur->isField('user_pwd')) {
            if (strlen($cur->user_pwd) < 6) {
                throw new Exception(__('Password must contain at least 6 characters.'));
            }
            $cur->user_pwd = $this->auth->crypt($cur->user_pwd);
        }

        if ($cur->user_lang !== null && !preg_match('/^[a-z]{2}(-[a-z]{2})?$/', (string) $cur->user_lang)) {
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
    public function userDefaults(): array
    {
        return [
            'edit_size'      => 24,
            'enable_wysiwyg' => true,
            'toolbar_bottom' => false,
            'editor'         => ['xhtml' => 'dcCKEditor', 'wiki' => 'dcLegacyEditor'],
            'post_format'    => 'xhtml',
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
    public function getBlogPermissions(string $id, bool $with_super = true): array
    {
        $sql = new SelectStatement();
        $sql
            ->columns([
                'U.user_id as user_id',
                'user_super',
                'user_name',
                'user_firstname',
                'user_displayname',
                'user_email',
                'permissions',
            ])
            ->from($sql->as($this->prefix . dcAuth::USER_TABLE_NAME, 'U'))
            ->join((new JoinStatement())
                ->from($sql->as($this->prefix . dcAuth::PERMISSIONS_TABLE_NAME, 'P'))
                ->on('U.user_id = P.user_id')
                ->statement())
            ->where('blog_id = ' . $sql->quote($id));

        if ($with_super) {
            $sql->union(
                (new SelectStatement())
                ->columns([
                    'U.user_id as user_id',
                    'user_super',
                    'user_name',
                    'user_firstname',
                    'user_displayname',
                    'user_email',
                    'NULL AS permissions',
                ])
                ->from($sql->as($this->prefix . dcAuth::USER_TABLE_NAME, 'U'))
                ->where('user_super = 1')
                ->statement()
            );
        }

        $rs = $sql->select();

        $res = [];

        while ($rs->fetch()) {
            $res[$rs->user_id] = [
                'name'        => $rs->user_name,
                'firstname'   => $rs->user_firstname,
                'displayname' => $rs->user_displayname,
                'email'       => $rs->user_email,
                'super'       => (bool) $rs->user_super,
                'p'           => $this->auth->parsePermissions($rs->permissions),
            ];
        }

        return $res;
    }

    /**
     * Gets the blog.
     *
     * @param      string  $id     The blog identifier
     *
     * @return     dcRecord|false    The blog.
     */
    public function getBlog(string $id)
    {
        $blog = $this->getBlogs(['blog_id' => $id]);

        if ($blog->isEmpty()) {
            return false;
        }

        return $blog;
    }

    /**
     * Returns a dcRecord of blogs. <b>$params</b> is an array with the following
     * optionnal parameters:
     *
     * - <var>blog_id</var>: Blog ID
     * - <var>q</var>: Search string on blog_id, blog_name and blog_url
     * - <var>limit</var>: limit results
     *
     * @param      array|ArrayObject    $params      The parameters
     * @param      bool                 $count_only  Count only results
     *
     * @return     dcRecord  The blogs.
     */
    public function getBlogs($params = [], bool $count_only = false): dcRecord
    {
        $join  = ''; // %1$s
        $where = ''; // %2$s

        if ($count_only) {
            $strReq = 'SELECT count(B.blog_id) ' .
            'FROM ' . $this->prefix . dcBlog::BLOG_TABLE_NAME . ' B ' .
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
            $strReq .= 'FROM ' . $this->prefix . dcBlog::BLOG_TABLE_NAME . ' B ' .
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
            $join  = 'INNER JOIN ' . $this->prefix . dcAuth::PERMISSIONS_TABLE_NAME . ' PE ON B.blog_id = PE.blog_id ';
            $where = "AND PE.user_id = '" . $this->con->escape($this->auth->userID()) . "' " .
                "AND (permissions LIKE '%|usage|%' OR permissions LIKE '%|admin|%' OR permissions LIKE '%|contentadmin|%') " .
                'AND blog_status IN (' . (string) dcBlog::BLOG_ONLINE . ',' . (string) dcBlog::BLOG_OFFLINE . ') ';
        } elseif (!$this->auth->userID()) {
            $where = 'AND blog_status IN (' . (string) dcBlog::BLOG_ONLINE . ',' . (string) dcBlog::BLOG_OFFLINE . ') ';
        }

        if (isset($params['blog_status']) && $params['blog_status'] !== '' && $this->auth->isSuperAdmin()) {
            $where .= 'AND blog_status = ' . (int) $params['blog_status'] . ' ';
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

        return new dcRecord($this->con->select($strReq));
    }

    /**
     * Adds a new blog.
     *
     * @param      cursor     $cur    The blog cursor
     *
     * @throws     Exception
     */
    public function addBlog(cursor $cur): void
    {
        if (!$this->auth->isSuperAdmin()) {
            throw new Exception(__('You are not an administrator'));
        }

        $this->fillBlogCursor($cur);

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
    public function updBlog(string $id, cursor $cur): void
    {
        $this->fillBlogCursor($cur);

        $cur->blog_upddt = date('Y-m-d H:i:s');

        $cur->update("WHERE blog_id = '" . $this->con->escape($id) . "'");
    }

    /**
     * Fills the blog cursor.
     *
     * @param      cursor  $cur    The cursor
     *
     * @throws     Exception
     */
    private function fillBlogCursor(cursor $cur): void
    {
        if (($cur->blog_id !== null
            && !preg_match('/^[A-Za-z0-9._-]{2,}$/', (string) $cur->blog_id)) || (!$cur->blog_id)) {
            throw new Exception(__('Blog ID must contain at least 2 characters using letters, numbers or symbols.'));
        }

        if (($cur->blog_name !== null && $cur->blog_name == '') || (!$cur->blog_name)) {
            throw new Exception(__('No blog name'));
        }

        if (($cur->blog_url !== null && $cur->blog_url == '') || (!$cur->blog_url)) {
            throw new Exception(__('No blog URL'));
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
    public function delBlog(string $id): void
    {
        if (!$this->auth->isSuperAdmin()) {
            throw new Exception(__('You are not an administrator'));
        }

        $strReq = 'DELETE FROM ' . $this->prefix . dcBlog::BLOG_TABLE_NAME . ' ' .
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
    public function blogExists(string $id): bool
    {
        $strReq = 'SELECT blog_id ' .
        'FROM ' . $this->prefix . dcBlog::BLOG_TABLE_NAME . ' ' .
        "WHERE blog_id = '" . $this->con->escape($id) . "' ";

        $rs = new dcRecord($this->con->select($strReq));

        return !$rs->isEmpty();
    }

    /**
     * Counts the number of blog posts.
     *
     * @param      string  $id     The blog identifier
     * @param      mixed   $type   The post type
     *
     * @return     int  Number of blog posts.
     */
    public function countBlogPosts(string $id, $type = null): int
    {
        $strReq = 'SELECT COUNT(post_id) ' .
        'FROM ' . $this->prefix . dcBlog::POST_TABLE_NAME . ' ' .
        "WHERE blog_id = '" . $this->con->escape($id) . "' ";

        if ($type) {
            $strReq .= "AND post_type = '" . $this->con->escape($type) . "' ";
        }

        return (new dcRecord($this->con->select($strReq)))->f(0);
    }
    //@}

    /// @name HTML Filter methods
    //@{
    /**
     * Calls HTML filter to drop bad tags and produce valid HTML output (if
     * tidy extension is present). If <b>enable_html_filter</b> blog setting is
     * false, returns not filtered string.
     *
     * @param      string  $str    The string
     *
     * @return     string
     */
    public function HTMLfilter(string $str): string
    {
        if ($this->blog instanceof dcBlog && !$this->blog->settings->system->enable_html_filter) {
            return $str;
        }

        $options = new ArrayObject([
            'keep_aria' => false,
            'keep_data' => false,
            'keep_js'   => false,
        ]);
        # --BEHAVIOR-- HTMLfilter -- ArrayObject
        $this->callBehavior('HTMLfilter', $options);

        $filter = new HtmlFilter($options['keep_aria'], $options['keep_data'], $options['keep_js']);
        $str    = trim($filter->apply($str));

        return $str;
    }
    //@}

    /// @name WikiToHtml methods
    //@{
    /**
     * Initializes the WikiToHtml methods.
     */
    private function initWiki(): void
    {
        $this->wiki       = new WikiToHtml();
        $this->wiki2xhtml = $this->wiki;
    }

    /**
     * Returns a transformed string with WikiToHtml.
     *
     * @param      string  $str    The string
     *
     * @return     string
     */
    public function wikiTransform(string $str): string
    {
        if (!($this->wiki instanceof WikiToHtml)) {
            $this->initWiki();
        }

        return $this->wiki->transform($str);
    }

    /**
     * Inits <var>wiki</var> property for blog post.
     */
    public function initWikiPost(): void
    {
        $this->initWiki();

        $this->wiki->setOpts([
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
            'img_style_center'    => 'display:table; margin:0 auto;',
        ]);

        $this->wiki->registerFunction('url:post', [$this, 'wikiPostLink']);

        # --BEHAVIOR-- coreWikiPostInit -- WikiToHtml
        $this->callBehavior('coreInitWikiPost', $this->wiki);
    }

    /**
     * Inits <var>wiki</var> property for simple blog comment (basic syntax).
     */
    public function initWikiSimpleComment(): void
    {
        $this->initWiki();

        $this->wiki->setOpts([
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
            'active_fr_syntax'    => 0,
        ]);

        # --BEHAVIOR-- coreInitWikiSimpleComment -- 
        # --BEHAVIOR-- coreWikiPostInit -- WikiToHtml
        $this->callBehavior('coreInitWikiSimpleComment', $this->wiki);
    }

    /**
     * Inits <var>wiki</var> property for blog comment.
     */
    public function initWikiComment(): void
    {
        $this->initWiki();

        $this->wiki->setOpts([
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
            'active_fr_syntax'    => 0,
        ]);

        # --BEHAVIOR-- coreInitWikiComment -- 
        # --BEHAVIOR-- coreWikiPostInit -- WikiToHtml
        $this->callBehavior('coreInitWikiComment', $this->wiki);
    }

    /**
     * Get info about a post:id wiki macro
     *
     * @param      string  $url      The post url
     * @param      string  $content  The content
     *
     * @return     array
     */
    public function wikiPostLink(string $url, string $content): array
    {
        if (!($this->blog instanceof dcBlog)) {
            return [];
        }

        $post_id = abs((int) substr($url, 5));
        if (!$post_id) {
            return [];
        }

        $post = $this->blog->getPosts(['post_id' => $post_id]);
        if ($post->isEmpty()) {
            return [];
        }

        $res = ['url' => $post->getURL()];

        if ($content != $url) {
            $res['title'] = Html::escapeHTML($post->post_title);
        }

        if ($content == '' || $content == $url) {
            $res['content'] = Html::escapeHTML($post->post_title);
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
    public function blogDefaults(?array $defaults = null): void
    {
        if (!is_array($defaults)) {
            $defaults = [
                ['allow_comments', 'boolean', true,
                    'Allow comments on blog', ],
                ['allow_trackbacks', 'boolean', true,
                    'Allow trackbacks on blog', ],
                ['blog_timezone', 'string', 'Europe/London',
                    'Blog timezone', ],
                ['comments_nofollow', 'boolean', true,
                    'Add rel="nofollow" to comments URLs', ],
                ['comments_pub', 'boolean', true,
                    'Publish comments immediately', ],
                ['comments_ttl', 'integer', 0,
                    'Number of days to keep comments open (0 means no ttl)', ],
                ['copyright_notice', 'string', '', 'Copyright notice (simple text)'],
                ['date_format', 'string', '%A, %B %e %Y',
                    'Date format. See PHP strftime function for patterns', ],
                ['editor', 'string', '',
                    'Person responsible of the content', ],
                ['enable_html_filter', 'boolean', 0,
                    'Enable HTML filter', ],
                ['lang', 'string', 'en',
                    'Default blog language', ],
                ['media_exclusion', 'string', '/\.(phps?|pht(ml)?|phl|phar|.?html?|xml|js|htaccess)[0-9]*$/i',
                    'File name exclusion pattern in media manager. (PCRE value)', ],
                ['media_img_m_size', 'integer', 448,
                    'Image medium size in media manager', ],
                ['media_img_s_size', 'integer', 240,
                    'Image small size in media manager', ],
                ['media_img_t_size', 'integer', 100,
                    'Image thumbnail size in media manager', ],
                ['media_img_title_pattern', 'string', 'Title ;; Date(%b %Y) ;; separator(, )',
                    'Pattern to set image title when you insert it in a post', ],
                ['media_video_width', 'integer', 400,
                    'Video width in media manager', ],
                ['media_video_height', 'integer', 300,
                    'Video height in media manager', ],
                ['nb_post_for_home', 'integer', 20,
                    'Number of entries on first home page', ],
                ['nb_post_per_page', 'integer', 20,
                    'Number of entries on home pages and category pages', ],
                ['nb_post_per_feed', 'integer', 20,
                    'Number of entries on feeds', ],
                ['nb_comment_per_feed', 'integer', 20,
                    'Number of comments on feeds', ],
                ['post_url_format', 'string', '{y}/{m}/{d}/{t}',
                    'Post URL format. {y}: year, {m}: month, {d}: day, {id}: post id, {t}: entry title', ],
                ['public_path', 'string', 'public',
                    'Path to public directory, begins with a / for a full system path', ],
                ['public_url', 'string', '/public',
                    'URL to public directory', ],
                ['robots_policy', 'string', 'INDEX,FOLLOW',
                    'Search engines robots policy', ],
                ['short_feed_items', 'boolean', false,
                    'Display short feed items', ],
                ['theme', 'string', DC_DEFAULT_THEME,
                    'Blog theme', ],
                ['themes_path', 'string', 'themes',
                    'Themes root path', ],
                ['themes_url', 'string', '/themes',
                    'Themes root URL', ],
                ['time_format', 'string', '%H:%M',
                    'Time format. See PHP strftime function for patterns', ],
                ['tpl_allow_php', 'boolean', false,
                    'Allow PHP code in templates', ],
                ['tpl_use_cache', 'boolean', true,
                    'Use template caching', ],
                ['trackbacks_pub', 'boolean', true,
                    'Publish trackbacks immediately', ],
                ['trackbacks_ttl', 'integer', 0,
                    'Number of days to keep trackbacks open (0 means no ttl)', ],
                ['url_scan', 'string', 'query_string',
                    'URL handle mode (path_info or query_string)', ],
                ['use_smilies', 'boolean', false,
                    'Show smilies on entries and comments', ],
                ['no_search', 'boolean', false,
                    'Disable search', ],
                ['inc_subcats', 'boolean', false,
                    'Include sub-categories in category page and category posts feed', ],
                ['wiki_comments', 'boolean', false,
                    'Allow commenters to use a subset of wiki syntax', ],
                ['import_feed_url_control', 'boolean', true,
                    'Control feed URL before import', ],
                ['import_feed_no_private_ip', 'boolean', true,
                    'Prevent import feed from private IP', ],
                ['import_feed_ip_regexp', 'string', '',
                    'Authorize import feed only from this IP regexp', ],
                ['import_feed_port_regexp', 'string', '/^(80|443)$/',
                    'Authorize import feed only from this port regexp', ],
                ['jquery_needed', 'boolean', true,
                    'Load jQuery library', ],
                ['sleepmode_timeout', 'integer', 31536000,
                    'Sleep mode timeout', ],
            ];
        }

        $settings = new dcSettings(null);

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
        'FROM ' . $this->prefix . dcBlog::POST_TABLE_NAME;
        $rs    = new dcRecord($this->con->select($strReq));
        $count = $rs->f(0);

        $strReq = 'SELECT post_id, post_title, post_excerpt_xhtml, post_content_xhtml ' .
        'FROM ' . $this->prefix . dcBlog::POST_TABLE_NAME . ' ';

        if ($start !== null && $limit !== null) {
            $strReq .= $this->con->limit($start, $limit);
        }

        $rs = new dcRecord($this->con->select($strReq));

        $cur = $this->con->openCursor($this->prefix . dcBlog::POST_TABLE_NAME);

        while ($rs->fetch()) {
            $words = $rs->post_title . ' ' . $rs->post_excerpt_xhtml . ' ' .
            $rs->post_content_xhtml;

            $cur->post_words = implode(' ', Text::splitWords($words));
            $cur->update('WHERE post_id = ' . (int) $rs->post_id);
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
     * @param      int   $start  The start comment index
     * @param      int   $limit  The limit of comment to index
     *
     * @return     mixed   sum of <var>$start</var> and <var>$limit</var>
     */
    public function indexAllComments(?int $start = null, ?int $limit = null)
    {
        $strReq = 'SELECT COUNT(comment_id) ' .
        'FROM ' . $this->prefix . dcBlog::COMMENT_TABLE_NAME;
        $rs    = new dcRecord($this->con->select($strReq));
        $count = $rs->f(0);

        $strReq = 'SELECT comment_id, comment_content ' .
        'FROM ' . $this->prefix . dcBlog::COMMENT_TABLE_NAME . ' ';

        if ($start !== null && $limit !== null) {
            $strReq .= $this->con->limit($start, $limit);
        }

        $rs = new dcRecord($this->con->select($strReq));

        $cur = $this->con->openCursor($this->prefix . dcBlog::COMMENT_TABLE_NAME);

        while ($rs->fetch()) {
            $cur->comment_words = implode(' ', Text::splitWords($rs->comment_content));
            $cur->update('WHERE comment_id = ' . (int) $rs->comment_id);
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
    public function countAllComments(): void
    {
        $sql_com = new UpdateStatement();
        $sql_com
            ->ref($sql_com->alias($this->prefix . dcBlog::POST_TABLE_NAME, 'P'));

        $sql_tb = clone $sql_com;

        $sql_count_com = new SelectStatement();
        $sql_count_com
            ->field($sql_count_com->count('C.comment_id'))
            ->from($sql_count_com->alias($this->prefix . dcBlog::COMMENT_TABLE_NAME, 'C'))
            ->where('C.post_id = P.post_id')
            ->and('C.comment_status = ' . (string) dcBlog::COMMENT_PUBLISHED);

        $sql_count_tb = clone $sql_count_com;

        $sql_count_com->and('C.comment_trackback <> 1');    // Count comment only
        $sql_count_tb->and('C.comment_trackback = 1');      // Count trackback only

        $sql_com->set('nb_comment = (' . $sql_count_com->statement() . ')');
        $sql_com->update();

        $sql_tb->set('nb_trackback = (' . $sql_count_tb->statement() . ')');
        $sql_tb->update();
    }

    /**
     * Empty templates cache directory
     */
    public function emptyTemplatesCache(): void
    {
        if (is_dir(DC_TPL_CACHE . DIRECTORY_SEPARATOR . Template::CACHE_FOLDER)) {
            Files::deltree(DC_TPL_CACHE . DIRECTORY_SEPARATOR . Template::CACHE_FOLDER);
        }
    }

    /**
     * Serve or not the REST requests (using a file as token)
     *
     * @param      bool  $serve  The flag
     */
    public function enableRestServer(bool $serve = true)
    {
        try {
            if ($serve && file_exists(DC_UPGRADE)) {
                // Remove watchdog file
                unlink(DC_UPGRADE);
            } elseif (!$serve && !file_exists(DC_UPGRADE)) {
                // Create watchdog file
                touch(DC_UPGRADE);
            }
        } catch (Exception $e) {
        }
    }

    /**
     * Check if we need to serve REST requests
     *
     * @return     bool
     */
    public function serveRestRequests(): bool
    {
        return !file_exists(DC_UPGRADE) && DC_REST_SERVICES;
    }

    /**
     * Return elapsed time since script has been started
     *
     * @param      float   $mtime  timestamp (microtime format) to evaluate delta from current time is taken if null
     *
     * @return     float   The elapsed time.
     */
    public function getElapsedTime(?float $mtime = null): float
    {
        if ($mtime !== null) {
            return $mtime - $this->stime;
        }

        return microtime(true) - $this->stime;
    }
    //@}
}
