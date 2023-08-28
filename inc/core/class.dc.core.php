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

use Dotclear\Core\Core;

use Dotclear\Core\Blogs;
use Dotclear\Core\Filter;
use Dotclear\Core\Formater;
use Dotclear\Core\Nonce;
use Dotclear\Core\PostType;
use Dotclear\Core\PostTypes;
use Dotclear\Core\Version;
use Dotclear\Core\Backend\Utility as Backend;
use Dotclear\Core\Frontend\Url;
use Dotclear\Core\Frontend\Utility as Frontend;
use Dotclear\Core\Install\Utils;
use Dotclear\Database\AbstractHandler;
use Dotclear\Database\Cursor;
use Dotclear\Database\MetaRecord;
use Dotclear\Database\Session;
use Dotclear\Database\Statement\DeleteStatement;
use Dotclear\Database\Statement\JoinStatement;
use Dotclear\Database\Statement\SelectStatement;
use Dotclear\Database\Statement\UpdateStatement;
use Dotclear\Helper\Behavior;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Html\HtmlFilter;
use Dotclear\Helper\Html\WikiToHtml;
use Dotclear\Helper\Text;

final class dcCore
{
    use dcTraitDynamicProperties;

    // Constants

    /**
     * Session table name
     *
     * @deprecated since 2.28, use Core::VERSION_TABLE_NAME
     *
     * @var string
     */
    public const SESSION_TABLE_NAME = Core::SESSION_TABLE_NAME;

    /**
     * Versions table name
     *
     * @deprecated since 2.28, use Version::VERSION_TABLE_NAME
     *
     * @var string
     */
    public const VERSION_TABLE_NAME = Version::VERSION_TABLE_NAME;

    // Properties

    /**
     * dcCore singleton instance
     *
     * @var dcCore
     */
    private static dcCore $instance;

    /**
     * Database connection
     *
     * @var AbstractHandler
     */
    public readonly AbstractHandler $con;

    /**
     * Database tables prefix
     *
     * May be deprecated as 2.28 con->prefix() method
     *
     * @var string
     */
    public readonly string $prefix;

    /**
     * dcBlog instance
     *
     * @var dcBlog|null
     */
    public $blog;

    /**
     * dcAuth instance
     *
     * @var dcAuth
     */
    public readonly dcAuth $auth;

    /**
     * Session in database instance
     *
     * @var Session
     */
    public readonly Session $session;

    /**
     * Url instance
     *
     * @var Url
     */
    public readonly Url $url;

    /**
     * dcRestServer instance
     *
     * @var dcRestServer
     */
    public readonly dcRestServer $rest;

    /**
     * WikiToHtml instance
     *
     * @deprecated since 2.27, use dcCora::app()->filter->wiki instead
     *
     * @var WikiToHtml
     */
    public $wiki;

    /**
     * WikiToHtml instance
     *
     * @deprecated since 2.27, use dcCora::app()->filtre->wiki instead
     *
     * @var WikiToHtml
     */
    public $wiki2xhtml;

    /**
     * Plugins
     *
     * @var dcPlugins
     */
    public readonly dcPlugins $plugins;

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
    public readonly dcMeta $meta;

    /**
     * dcError instance
     *
     * @var dcError
     */
    public readonly dcError $error;

    /**
     * dcNotices instance
     *
     * @var dcNotices
     */
    public readonly dcNotices $notices;

    /**
     * dcLog instance
     *
     * @var dcLog
     */
    public readonly dcLog $log;

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
     * @deprecated since 2.27, use Autoloader::me() instead
     */
    public $autoload;

    // Admin context

    /**
     * Backend Utility instance
     *
     * @var \Dotclear\Core\Backend\Utility
     */
    public Backend $admin;

    /**
     * Backend Url instance.
     *
     * @deprecated since 2.27, Use dcCore::app()->admin->url
     *
     * @var \Dotclear\Core\Backend\Url
     */
    public \Dotclear\Core\Backend\Url $adminurl;

    /**
     * Bakcend Favorites instance.
     *
     * @deprecated since 2.27, Use dcCore::app()->admin->favs
     *
     * @var \Dotclear\Core\Backend\Favorites
     */
    public \Dotclear\Core\Backend\Favorites $favs;

    /**
     * Backend Menus instance.
     *
     * @deprecated since 2.27, Use dcCore::app()->admin->menus
     *
     * @var \Dotclear\Core\Backend\Menus
     */
    public \Dotclear\Core\Backend\Menus $menu;

    /**
     * Array of resources
     *
     * @deprecated since 2.28, Use dcCore::app()->admin->resources instance
     *
     * @var array
     */
    public $resources = [];

    // Public context

    /**
     * Frontend Utility instance
     *
     * @var \Dotclear\Core\Frontend\Utility
     */
    public Frontend $public;

    /**
     * dcTemplate instance
     *
     * May be transfered as property of frontend Utility instance in future
     *
     * @var dcTemplate
     */
    public $tpl;

    /**
     * context instance
     *
     * May be transfered as property of frontend Utility instance in future
     *
     * @var context|null
     */
    public $ctx;

    /**
     * HTTP Cache stack
     *
     * May be transfered as property of frontend Utility instance in future
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
     * May be transfered as property of frontend Utility instance in future
     *
     * @var array|null
     */
    public $spamfilters = [];

    /**
     * dcCore constructor inits everything related to Dotclear.
     */
    public function __construct()
    {
        // Singleton mode
        if (isset(self::$instance)) {
            throw new Exception('Application can not be started twice.', 500);
        }
        self::$instance = $this;

        // Deprecated since 2.26
        $this->autoload = Autoloader::me();

        $this->con        = Core::con();
        $this->prefix     = Core::con()->prefix();
        $this->error      = new dcError();
        $this->auth       = dcAuth::init();
        $this->session    = Core::session();
        $this->url        = new Url();
        $this->plugins    = new dcPlugins();
        $this->rest       = new dcRestServer();
        $this->meta       = new dcMeta();
        $this->log        = new dcLog();
        $this->notices    = new dcNotices();

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
        Core::app()->setBlog($id);
        $this->blog = Core::app()->blog();
    }

    /**
     * Unsets blog property
     */
    public function unsetBlog(): void
    {
        Core::app()->unsetBlog();
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
     * @deprecated since 2.28, use Core::nonce()->getNonce() instead
     *
     * @return     string
     */
    public function getNonce(): string
    {
        return Core::nonce()->getNonce();
    }

    /**
     * Check the nonce.
     *
     * @deprecated since 2.28, use Core::nonce()->checkNonce() instead
     *
     * @param      string  $secret  The nonce
     *
     * @return     bool
     */
    public function checkNonce(string $secret): bool
    {
        return Core::nonce()->checkNonce($secret);
    }

    /**
     * Get the nonce HTML code.
     *
     * @deprecated since 2.28, use Core::nonce()->formNonce() or Core::nonce()->getFormNonce()instead
     *
     * @param      bool     $render     Should render element?
     *
     * @return     mixed
     */
    public function formNonce(bool $render = true)
    {
        return $render ? Core::nonce()->getFormNonce() : Core::nonce()->formNonce();
    }
    //@}

    /// @name Text Formatters methods
    //@{
    /**
     * Adds a new text formater.
     *
     * @deprecated since 2.28, use Core::formater()->addEditorFormater() instead
     *
     * @param      string    $editor_id  The editor identifier (dcLegacyEditor, dcCKEditor, ...)
     * @param      string    $name       The formater name
     * @param      callable  $func       The function to use, must be a valid and callable callback
     */
    public function addEditorFormater(string $editor_id, string $name, $func): void
    {
        Core::formater()->addEditorFormater($editor_id, $name, $func);
    }

    /**
     * Adds a new dcLegacyEditor text formater.
     *
     * @deprecated since 2.28, use Core::formater()->addEditorFormater('dcLegacyEditor', ...) instead
     *
     * @param      string    $name       The formater name
     * @param      callable  $func       The function to use, must be a valid and callable callback
     */
    public function addFormater(string $name, $func): void
    {
        Core::formater()->addEditorFormater('dcLegacyEditor', $name, $func);
    }

    /**
     * Adds a formater name.
     *
     * @deprecated since 2.28, use Core::formater()->addFormaterName() instead
     *
     * @param      string  $format  The format
     * @param      string  $name    The name
     */
    public function addFormaterName(string $format, string $name): void
    {
        Core::formater()->addFormaterName($format, $name);
    }

    /**
     * Gets the formater name.
     *
     * @deprecated since 2.28, use Core::formater()->getFormaterName() instead
     *
     * @param      string  $format  The format
     *
     * @return     string
     */
    public function getFormaterName(string $format): string
    {
        return Core::formater()->getFormaterName($format);
    }

    /**
     * Gets the editors list.
     *
     * @deprecated since 2.28, use Core::formater()->getEditors() instead
     *
     * @return     array
     */
    public function getEditors(): array
    {
        return Core::formater()->getEditors();
    }

    /**
     * Gets the formaters.
     *
     * @deprecated since 2.28, use Core::formater()->getFormaters() or Core::formater()->getFormater(xxx) instead
     *
     * @param      string  $editor_id  The editor identifier (dcLegacyEditor, dcCKEditor, ...)
     *
     * @return     array
     */
    public function getFormaters(string $editor_id = ''): array
    {
        return empty($editor_id) ? Core::formater()->getFormaters() : Core::formater()->getFormater($editor_id);
    }

    /**
     * Call editor formater.
     *
     * @deprecated since 2.28, use Core::formater()->callEditorFormater() instead
     *
     * @param      string  $editor_id  The editor identifier (dcLegacyEditor, dcCKEditor, ...)
     * @param      string  $name       The formater name
     * @param      string  $str        The string to transform
     *
     * @return     string
     */
    public function callEditorFormater(string $editor_id, string $name, string $str): string
    {
        return Core::formater()->callEditorFormater($editor_id, $name, $str);
    }

    /**
     * Call formater.
     *
     * @deprecated since 2.28, use Core::formater()->callEditorFormater('dcLegacyEditor', ...) instead
     *
     * @param      string  $name   The name
     * @param      string  $str    The string
     *
     * @return     string
     */
    public function callFormater(string $name, string $str): string
    {
        return Core::formater()->callEditorFormater('dcLegacyEditor', $name, $str);
    }
    //@}

    /// @name Behaviors methods
    //@{
    /**
     * Adds a new behavior to behaviors stack.
     *
     * @deprecated since 2.28, use Core::behavior()->addBehavior() instead
     *
     * @param      string           $behavior  The behavior
     * @param      callable|array   $func      The function
     */
    public function addBehavior(string $behavior, $func): void
    {
        Core::behavior()->addBehavior($behavior, $func);
    }

    /**
     * Adds a behaviour (alias).
     *
     * @deprecated since 2.28, use Core::behavior()->addBehavior() instead
     *
     * @param      string           $behaviour  The behaviour
     * @param      callable|array   $func       The function
     */
    public function addBehaviour(string $behaviour, $func): void
    {
        Core::behavior()->addBehavior($behaviour, $func);
    }

    /**
     * Adds new behaviors to behaviors stack.
     *
     * @deprecated since 2.28, use Core::behavior()->addBehaviors() instead
     *
     * @param      array    $behaviors  The behaviors
     */
    public function addBehaviors(array $behaviors): void
    {
        Core::behavior()->addBehaviors($behaviors);
    }

    /**
     * Adds behaviours (alias).
     *
     * @deprecated since 2.28, use Core::behavior()->addBehaviors() instead
     *
     * @param      array    $behaviours  The behaviours
     */
    public function addBehaviours(array $behaviours): void
    {
        Core::behavior()->addBehaviors($behaviours);
    }

    /**
     * Determines if behavior exists in behaviors stack.
     *
     * @deprecated since 2.28, use Core::behavior()->hasBehavior() instead
     *
     * @param      string  $behavior  The behavior
     *
     * @return     bool
     */
    public function hasBehavior(string $behavior): bool
    {
        return Core::behavior()->hasBehavior($behavior);
    }

    /**
     * Determines if behaviour exists (alias).
     *
     * @deprecated since 2.28, use Core::behavior()->hasBehavior() instead
     *
     * @param      string  $behaviour  The behavior
     *
     * @return     bool
     */
    public function hasBehaviour(string $behaviour): bool
    {
        return Core::behavior()->hasBehavior($behaviour);
    }

    /**
     * Gets the behaviors stack (or part of).
     *
     * @deprecated since 2.28, use Core::behavior()->getBehaviors() or Core::behavior()->getBehavior()instead
     *
     * @param      string  $behavior  The behavior
     *
     * @return     mixed
     */
    public function getBehaviors(string $behavior = '')
    {
        return empty($behavior) ? Core::behavior()->getBehaviors() : Core::behavior()->getBehavior($behavior);
    }

    /**
     * Gets the behaviours stack (alias).
     *
     * @deprecated since 2.28, use Core::behavior()->getBehaviors() or Core::behavior()->getBehavior()instead
     *
     * @param      string  $behaviour  The behaviour
     *
     * @return     mixed
     */
    public function getBehaviours(string $behaviour = '')
    {
        return empty($behaviour) ? Core::behavior()->getBehaviors() : Core::behavior()->getBehavior($behaviour);
    }

    /**
     * Calls every function in behaviors stack for a given behavior and returns
     * concatened result of each function.
     *
     * @deprecated since 2.28, use Core::behavior()->callBehavior() instead
     *
     * @param      string  $behavior  The behavior
     * @param      mixed   ...$args   The arguments
     *
     * @return     mixed
     */
    public function callBehavior(string $behavior, ...$args)
    {
        return Core::behavior()->callBehavior($behavior, ...$args);
    }

    /**
     * Calls every function in behaviours stack (alias).
     *
     * @deprecated since 2.28, use Core::behavior()->callBehavior() instead
     *
     * @param      string  $behaviour  The behaviour
     * @param      mixed   ...$args    The arguments
     *
     * @return     mixed
     */
    public function callBehaviour(string $behaviour, ...$args)
    {
        return Core::behavior()->callBehavior($behaviour, ...$args);
    }
    //@}

    /// @name Post types URLs management
    //@{

    /**
     * Gets the post admin url.
     *
     * @deprecated since 2.28, use Core::postTypes()->get($type)->adminUrl() instead
     *
     * @param      string               $type     The type
     * @param      int|string           $post_id  The post identifier
     * @param      bool                 $escaped  Escape the URL
     * @param      array<string,mixed>  $params   The query string parameters (associative array)
     *
     * @return     string    The post admin url.
     */
    public function getPostAdminURL(string $type, int|string $post_id, bool $escaped = true, array $params = []): string
    {
        return Core::postTypes()->get($type)->adminUrl($post_id, $escaped, $params);
    }

    /**
     * Gets the post public url.
     *
     * @deprecated since 2.28, use Core::postTypes()->get($type)->publicUrl() instead
     *
     * @param      string  $type      The type
     * @param      string  $post_url  The post url
     * @param      bool    $escaped   Escape the URL
     *
     * @return     string    The post public url.
     */
    public function getPostPublicURL(string $type, string $post_url, bool $escaped = true): string
    {
        return Core::postTypes()->get($type)->publicUrl($post_url, $escaped);
    }

    /**
     * Sets the post type.
     *
     * @deprecated since 2.28, use Core::postTypes()->set(new PostType()) instead
     *
     * @param      string  $type        The type
     * @param      string  $admin_url   The admin url
     * @param      string  $public_url  The public url
     * @param      string  $label       The label
     */
    public function setPostType(string $type, string $admin_url, string $public_url, string $label = ''): void
    {
        Core::postTypes()->set(new PostType($type, $admin_url, $public_url, $label));
    }

    /**
     * Gets the post types.
     *
     * @deprecated since 2.28, use Core::postTypes()->dump() instead
     *
     * @return     array  The post types.
     */
    public function getPostTypes(): array
    {
        return Core::postTypes()->getPostTypes();
    }
    //@}

    /// @name Versions management methods
    //@{
    /**
     * Gets the version of a module.
     *
     * @deprecated since 2.28, use Core::version()->getVersion() instead
     *
     * @param      string  $module  The module
     *
     * @return     mixed
     */
    public function getVersion(string $module = 'core')
    {
        $v = Core::version()->getVersion($module);

        // keep compatibility with old return type
        return $v === '' ? null : $v;
    }

    /**
     * Gets all known versions.
     *
     * @deprecated since 2.28, use Core::version()->getVersions() instead
     *
     * @return     array
     */
    public function getVersions(): array
    {
        return Core::version()->getVersions();
    }

    /**
     * Sets the version of a module.
     *
     * @deprecated since 2.28, use Core::version()->setVersion() instead
     *
     * @param      string  $module   The module
     * @param      string  $version  The version
     */
    public function setVersion(string $module, string $version)
    {
        Core::version()->setVersion($module, $version);
    }

    /**
     * Compare the given version of a module with the registered one.
     *
     * @deprecated since 2.28, use Core::version()->compareVersion() instead
     *
     * @param      string  $module   The module
     * @param      string  $version  The version
     *
     * @return     int
     */
    public function testVersion(string $module, string $version): int
    {
        return Core::version()->compareVersion($module, $version);
    }

    /**
     * Test if a version is a new one.
     *
     * @deprecated since 2.28, use Core::version()->newerVersion() instead
     *
     * @param      string  $module   The module
     * @param      string  $version  The version
     *
     * @return     bool
     */
    public function newVersion(string $module, string $version): bool
    {
        return Core::version()->newerVersion($module, $version);
    }

    /**
     * Remove a module version entry
     *
     * @deprecated since 2.28, use Core::version()->unsetVersion() instead
     *
     * @param      string  $module  The module
     */
    public function delVersion(string $module)
    {
        Core::version()->unsetVersion($module);
    }
    //@}

    /// @name Users management methods
    //@{
    /**
     * Gets the user by its ID.
     *
     * @param      string  $id     The identifier
     *
     * @return     MetaRecord  The user.
     */
    public function getUser(string $id): MetaRecord
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
     * @return     MetaRecord  The users.
     */
    public function getUsers($params = [], bool $count_only = false): MetaRecord
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
            $sql->and($sql->orGroup([
                $sql->like('LOWER(U.user_id)', $q),
                $sql->like('LOWER(user_name)', $q),
                $sql->like('LOWER(user_firstname)', $q),
            ]));
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
     * Adds a new user. Takes a Cursor as input and returns the new user ID.
     *
     * @param      Cursor     $cur    The user Cursor
     *
     * @throws     Exception
     *
     * @return     string
     */
    public function addUser(Cursor $cur): string
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

        # --BEHAVIOR-- coreAfterAddUser -- Cursor
        $this->callBehavior('coreAfterAddUser', $cur);

        return $cur->user_id;
    }

    /**
     * Updates an existing user. Returns the user ID.
     *
     * @param      string     $id     The user identifier
     * @param      Cursor     $cur    The Cursor
     *
     * @throws     Exception
     *
     * @return     string
     */
    public function updUser(string $id, Cursor $cur): string
    {
        $this->fillUserCursor($cur);

        if (($cur->user_id !== null || $id != $this->auth->userID()) && !$this->auth->isSuperAdmin()) {
            throw new Exception(__('You are not an administrator'));
        }

        $sql = new UpdateStatement();
        $sql->where('user_id = ' . $sql->quote($id));

        $sql->update($cur);

        # --BEHAVIOR-- coreAfterUpdUser -- Cursor
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
     * Fills the user Cursor.
     *
     * @param      Cursor     $cur    The user Cursor
     *
     * @throws     Exception
     */
    private function fillUserCursor(Cursor $cur)
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
     * Returns all blog permissions (users) as an array.
     *
     * @deprecated since 2.28, use Core::blogs()->getBlogPermissions() instead
     *
     * @param      string  $id          The blog identifier
     * @param      bool    $with_super  Includes super admins in result
     *
     * @return     array
     */
    public function getBlogPermissions(string $id, bool $with_super = true): array
    {
        return Core::blogs()->getBlogPermissions($id, $with_super);
    }

    /**
     * Gets the blog.
     *
     * @deprecated since 2.28, use Core::blogs()->getBlogPermissions() instead
     *
     * @param      string  $id     The blog identifier
     *
     * @return     MetaRecord|false.
     */
    public function getBlog(string $id)
    {
        return Core::blogs()->getBlog($id);
    }

    /**
     * Returns a MetaRecord of blogs.
     *
     * @deprecated since 2.28, use Core::blogs()->getBlogs() instead
     *
     * @param      array|ArrayObject    $params      The parameters
     * @param      bool                 $count_only  Count only results
     *
     * @return     MetaRecord.
     */
    public function getBlogs($params = [], bool $count_only = false): MetaRecord
    {
        return Core::blogs()->getBlogs($params, $count_only);
    }

    /**
     * Adds a new blog.
     *
     * @deprecated since 2.28, use Core::blogs()->addBlog() instead
     *
     * @param      Cursor     $cur    The blog Cursor
     *
     * @throws     Exception
     */
    public function addBlog(Cursor $cur): void
    {
        Core::blogs()->addBlog($cur);
    }

    /**
     * Updates a given blog.
     *
     * @deprecated since 2.28, use Core::blogs()->updBlog() instead
     *
     * @param      string  $id     The blog identifier
     * @param      Cursor  $cur    The Cursor
     */
    public function updBlog(string $id, Cursor $cur): void
    {
        Core::blogs()->updBlog($id, $cur);
    }

    /**
     * Removes a given blog.
     *
     * @deprecated since 2.28, use Core::blogs()->delBlog() instead
     *
     * @param      string     $id     The blog identifier
     *
     * @throws     Exception
     */
    public function delBlog(string $id): void
    {
        Core::blogs()->delBlog($id);
    }

    /**
     * Determines if blog exists.
     *
     * @deprecated since 2.28, use Core::blogs()->blogExists() instead
     *
     * @param      string  $id     The blog identifier
     *
     * @return     bool
     */
    public function blogExists(string $id): bool
    {
        return Core::blogs()->blogExists($id);
    }

    /**
     * Counts the number of blog posts.
     *
     * @deprecated since 2.28, use Core::blogs()->countBlogPosts() instead
     *
     * @param      string        $id     The blog identifier
     * @param      null|string   $type   The post type
     *
     * @return     int
     */
    public function countBlogPosts(string $id, $type = null): int
    {
        return Core::blogs()->countBlogPosts($id, $type);
    }
    //@}

    /// @name HTML Filter methods
    //@{
    /**
     * Calls HTML filter to drop bad tags and produce valid HTML output.
     *
     * @deprecated since 2.28, use Core::filter()->HTMLfilter() instead
     *
     * @param      string  $str    The string
     *
     * @return     string
     */
    public function HTMLfilter(string $str): string
    {
        return Core::filter()->HTMLfilter($str);
    }
    //@}

    /// @name WikiToHtml methods
    //@{
    /**
     * Returns a transformed string with WikiToHtml.
     *
     * @deprecated since 2.28, use Core::filter()->wikiTransform() instead
     *
     * @param      string  $str    The string
     *
     * @return     string
     */
    public function wikiTransform(string $str): string
    {
        return Core::filter()->wikiTransform($str);
    }

    /**
     * Inits <var>wiki</var> property for blog post.
     *
     * @deprecated since 2.28, use Core::filter()->initWikiPost() instead
     */
    public function initWikiPost(): void
    {
        Core::filter()->initWikiPost();
    }

    /**
     * Inits <var>wiki</var> property for simple blog comment (basic syntax).
     *
     * @deprecated since 2.28, use Core::filter()->initWikiSimpleComment() instead
     */
    public function initWikiSimpleComment(): void
    {
        Core::filter()->initWikiSimpleComment();
    }

    /**
     * Inits <var>wiki</var> property for blog comment.
     *
     * @deprecated since 2.28, use Core::filter()->initWikiComment() instead
     */
    public function initWikiComment(): void
    {
        Core::filter()->initWikiComment();
    }

    /**
     * Get info about a post:id wiki macro.
     *
     * @deprecated since 2.28, use Core::filter()->wikiPostLink() instead
     *
     * @param      string  $url      The post url
     * @param      string  $content  The content
     *
     * @return     array
     */
    public function wikiPostLink(string $url, string $content): array
    {
        return Core::filter()->wikiPostLink($url, $content);
    }
    //@}

    /// @name Maintenance methods
    //@{
    /**
     * Creates default settings for active blog.
     *
     * @deprecated since 2.28, use Dotclear\Core\Install\Utils::blogDefault() instead
     *
     * @param      array  $defaults  The defaults settings
     */
    public function blogDefaults(?array $defaults = null): void
    {
        Utils::blogDefaults($defaults);
    }

    /**
     * Recreates entries search engine index.
     *
     * @deprecated since 2.28, permanently moved to plugin maintenance
     *
     * @param      mixed   $start  The start entry index
     * @param      mixed   $limit  The limit of entry to index
     */
    public function indexAllPosts($start = null, $limit = null)
    {
    }

    /**
     * Recreates comments search engine index.
     *
     * @deprecated since 2.28, permanently moved to plugin maintenance
     *
     * @param      int   $start  The start comment index
     * @param      int   $limit  The limit of comment to index
     */
    public function indexAllComments(?int $start = null, ?int $limit = null)
    {
    }

    /**
     * Reinits nb_comment and nb_trackback in post table.
     *
     * @deprecated since 2.28, permanently moved to plugin maintenance
     */
    public function countAllComments(): void
    {
    }

    /**
     * Empty templates cache directory.
     *
     * @deprecated since 2.28, use dcUtils::emptyTemplatesCache() instead
     */
    public function emptyTemplatesCache(): void
    {
        dcUtils::emptyTemplatesCache();
    }

    /**
     * Serve or not the REST requests.
     *
     * @deprecated since 2.28, use dcCore::app()->rest->enableRestServer() instead
     *
     * @param      bool  $serve  The flag
     */
    public function enableRestServer(bool $serve = true)
    {
        $this->rest->enableRestServer($serve);
    }

    /**
     * Check if we need to serve REST requests.
     *
     * @deprecated since 2.28, use dcCore::app()->rest->serveRestRequests() instead
     *
     * @return     bool
     */
    public function serveRestRequests(): bool
    {
        return $this->rest->serveRestRequests();
    }
    //@}
}
