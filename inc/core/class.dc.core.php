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

use Dotclear\Core\Blogs;
use Dotclear\Core\Core;
use Dotclear\Core\Filter;
use Dotclear\Core\Formater;
use Dotclear\Core\Nonce;
use Dotclear\Core\PostType;
use Dotclear\Core\PostTypes;
use Dotclear\Core\Users;
use Dotclear\Core\Version;
use Dotclear\Core\Backend\Utility as Backend;
use Dotclear\Core\Frontend\Tpl;
use Dotclear\Core\Frontend\Url;
use Dotclear\Core\Frontend\Utility as Frontend;
use Dotclear\Core\Install\Utils;
use Dotclear\Database\AbstractHandler;
use Dotclear\Database\Cursor;
use Dotclear\Database\MetaRecord;
use Dotclear\Database\Session;
use Dotclear\Helper\Behavior;
use Dotclear\Helper\Html\WikiToHtml;

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
     * @deprecated since 2.28, use Core::con() instead
     *
     * @var AbstractHandler
     */
    public readonly AbstractHandler $con;

    /**
     * Database tables prefix
     *
     * @deprecated since 2.28, use Core::con()->prefix() instead
     *
     * @var string
     */
    public readonly string $prefix;

    /**
     * dcBlog instance
     *
     * @deprecated since 2.28, use Core::blog() instead
     *
     * @var dcBlog|null
     */
    public $blog;

    /**
     * dcAuth instance
     *
     * @deprecated since 2.28, use Core::auth() instead
     *
     * @var dcAuth
     */
    public readonly dcAuth $auth;

    /**
     * Session in database instance
     *
     * @deprecated since 2.28, use Core::session() instead
     *
     * @var Session
     */
    public readonly Session $session;

    /**
     * Url instance
     *
     * @deprecated since 2.28, use Core::url() instead
     *
     * @var Url
     */
    public readonly Url $url;

    /**
     * dcRestServer instance
     *
     * @deprecated since 2.28, use Core::rest() instead
     *
     * @var dcRestServer
     */
    public readonly dcRestServer $rest;

    /**
     * WikiToHtml instance
     *
     * @deprecated since 2.27, use Core::filter()->wiki instead
     *
     * @var WikiToHtml
     */
    public $wiki;

    /**
     * WikiToHtml instance
     *
     * @deprecated since 2.27, use Core::filter()->wiki instead
     *
     * @var WikiToHtml
     */
    public $wiki2xhtml;

    /**
     * Plugins
     *
     * @deprecated since 2.28, Use Core::plugins() instead
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
     * @deprecated since 2.28, use Core::postMedia() instead
     *
     * @var dcPostMedia
     */
    public $postmedia;

    /**
     * dcMeta instance
     *
     * @deprecated since 2.28, use Core::meta() instead
     *
     * @var dcMeta
     */
    public readonly dcMeta $meta;

    /**
     * dcError instance
     *
     * @deprecated since 2.28, use Core::session() instead
     *
     * @var dcError
     */
    public readonly dcError $error;

    /**
     * dcNotices instance
     *
     * @deprecated since 2.28, Use Core::notice() instead
     *
     * @var dcNotices
     */
    public readonly dcNotices $notices;

    /**
     * dcLog instance
     *
     * @deprecated since 2.28, Use Core::log() instead
     *
     * @var dcLog
     */
    public readonly dcLog $log;

    /**
     * Current language
     *
     * @deprecated since 2.28, Use Core::lang() and Core::setLang() instead
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
     * @deprecated since 2.28, Use Core::backend() instead
     *
     * @var \Dotclear\Core\Backend\Utility
     */
    public Backend $admin;

    /**
     * Backend Url instance.
     *
     * @deprecated since 2.28, Use Core::backend()->url instead
     *
     * @var \Dotclear\Core\Backend\Url
     */
    public \Dotclear\Core\Backend\Url $adminurl;

    /**
     * Bakcend Favorites instance.
     *
     * @deprecated since 2.28, Use Core::backend()->favs instead
     *
     * @var \Dotclear\Core\Backend\Favorites
     */
    public \Dotclear\Core\Backend\Favorites $favs;

    /**
     * Backend Menus instance.
     *
     * @deprecated since 2.28, Use Core::backend()->menus instead
     *
     * @var \Dotclear\Core\Backend\Menus
     */
    public \Dotclear\Core\Backend\Menus $menu;

    /**
     * Array of resources
     *
     * @deprecated since 2.28, Use Core::backend()->resources instance
     *
     * @var array
     */
    public $resources = [];

    // Public context

    /**
     * Frontend Utility instance
     *
     * @deprecated since 2.28, Use Core::frontend() instead
     *
     * @var \Dotclear\Core\Frontend\Utility
     */
    public Frontend $public;

    /**
     * Tpl instance
     *
     * @deprecated since 2.28, Use Core::frontend()->tpl instead
     *
     * @var Tpl
     */
    public Tpl $tpl;

    /**
     * context instance
     *
     * @deprecated since 2.28, Use Core::frontend()->ctx instead
     *
     * @var context|null
     */
    public $ctx;

    /**
     * HTTP Cache stack
     *
     * @deprecated since 2.28, permanently moved to Core::frontend()->cache()
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

        $this->con     = Core::con();
        $this->prefix  = Core::con()->prefix();
        $this->error   = Core::error();
        $this->auth    = Core::auth();
        $this->session = Core::session();
        $this->url     = Core::url();
        $this->plugins = Core::plugins();
        $this->rest    = Core::rest();
        $this->meta    = Core::meta();
        $this->log     = Core::log();
        $this->notices = Core::notice();

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
     * @deprecated since 2.28, nothing to use instead
     *
     * @return     dcCore
     */
    public static function app(): dcCore
    {
        return self::$instance;
    }

    /**
     * Kill admin session helper.
     *
     * @deprecated since 2.28, use Core::backend()->killAdminSession() instead
     */
    public function killAdminSession(): void
    {
        Core::backend()->killAdminSession();
    }

    /// @name Blog init methods
    //@{
    /**
     * Sets the blog to use.
     *
     * @deprecated since 2.28, use Core::setBlog() instead
     *
     * @param      string  $id     The blog ID
     */
    public function setBlog($id): void
    {
        Core::setBlog($id);
    }

    /**
     * Unsets blog property.
     *
     * @deprecated since 2.28, use Core::unsetBlog() instead
     */
    public function unsetBlog(): void
    {
        Core::unsetBlog();
    }
    //@}

    /// @name Blog status methods
    //@{
    /**
     * Gets all blog status.
     *
     * @deprecated since 2.28, use Core::blogs()->getAllBlogStatus() instead
     *
     * @return     array
     */
    public function getAllBlogStatus(): array
    {
        return Core::blogs()->getAllBlogStatus();
    }

    /**
     * Returns a blog status name given to a code.
     *
     * @deprecated since 2.28, use Core::blogs()->getBlogStatus() instead
     *
     * @param      int      $s      Status code
     *
     * @return     string
     */
    public function getBlogStatus(int $s): string
    {
        return Core::blogs()->getBlogStatus($s);
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
     * @deprecated since 2.28, use Core::behavior()->getBehaviors() or Core::behavior()->getBehavior() instead
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
     * @return     null|string
     */
    public function getVersion(string $module = 'core'): ?string
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
    public function setVersion(string $module, string $version): void
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
     * Remove a module version entry.
     *
     * @deprecated since 2.28, use Core::version()->unsetVersion() instead
     *
     * @param      string  $module  The module
     */
    public function delVersion(string $module): void
    {
        Core::version()->unsetVersion($module);
    }
    //@}

    /// @name Users management methods
    //@{
    /**
     * Gets the user by its ID.
     *
     * @deprecated since 2.28, use Core::users()->getUser() instead
     *
     * @param      string  $id     The identifier
     *
     * @return     MetaRecord.
     */
    public function getUser(string $id): MetaRecord
    {
        return Core::users()->getUser($id);
    }

    /**
     * Returns a users list.
     *
     * @deprecated since 2.28, use Core::users()->getUsers() instead
     *
     * @param      array|ArrayObject    $params      The parameters
     * @param      bool                 $count_only  Count only results
     *
     * @return     MetaRecord
     */
    public function getUsers($params = [], bool $count_only = false): MetaRecord
    {
        return Core::users()->getUsers($params, $count_only);
    }

    /**
     * Adds a new user.
     *
     * @deprecated since 2.28, use Core::users()->addUser() instead
     *
     * @param      Cursor     $cur    The user Cursor
     *
     * @throws     Exception
     *
     * @return     string
     */
    public function addUser(Cursor $cur): string
    {
        return Core::users()->addUser($cur);
    }

    /**
     * Updates an existing user. Returns the user ID.
     *
     * @deprecated since 2.28, use Core::users()->updUser() instead
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
        return Core::users()->updUser($id, $cur);
    }

    /**
     * Deletes a user.
     *
     * @deprecated since 2.28, use Core::users()->delUser() instead
     *
     * @param      string     $id     The user identifier
     *
     * @throws     Exception
     */
    public function delUser(string $id): void
    {
        Core::users()->delUser($id);
    }

    /**
     * Determines if user exists.
     *
     * @deprecated since 2.28, use Core::users()->userExists() instead
     *
     * @param      string  $id     The identifier
     *
     * @return      bool
     */
    public function userExists(string $id): bool
    {
        return Core::users()->userExists($id);
        ;
    }

    /**
     * Returns all user permissions as an array.
     *
     * @deprecated since 2.28, use Core::users()->getUserPermissions() instead
     *
     * @param      string  $id     The user identifier
     *
     * @return     array
     */
    public function getUserPermissions(string $id): array
    {
        return Core::users()->getUserPermissions($id);
    }

    /**
     * Sets user permissions.
     *
     * @deprecated since 2.28, use Core::users()->setUserPermissions() instead
     *
     * @param      string     $id     The user identifier
     * @param      array      $perms  The permissions
     *
     * @throws     Exception
     */
    public function setUserPermissions(string $id, array $perms): void
    {
        Core::users()->setUserPermissions($id, $perms);
    }

    /**
     * Sets the user blog permissions.
     *
     * @deprecated since 2.28, use Core::users()->setUserBlogPermissions() instead
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
        Core::users()->setUserBlogPermissions($id, $blog_id, $perms, $delete_first);
    }

    /**
     * Sets the user default blog. This blog will be selected when user log in.
     *
     * @deprecated since 2.28, use Core::users()->setUserDefaultBlog() instead
     *
     * @param      string  $id       The user identifier
     * @param      string  $blog_id  The blog identifier
     */
    public function setUserDefaultBlog(string $id, string $blog_id): void
    {
        Core::users()->setUserDefaultBlog($id, $blog_id);
    }

    /**
     * Removes users default blogs.
     *
     * @deprecated since 2.28, use Core::users()->removeUsersDefaultBlogs() instead
     *
     * @param      array  $ids    The blogs to remove
     */
    public function removeUsersDefaultBlogs(array $ids): void
    {
        Core::users()->removeUsersDefaultBlogs($ids);
    }

    /**
     * Returns user default settings in an associative array with setting names in keys.
     *
     * @deprecated since 2.28, use Core::users()->userDefaults() instead
     *
     * @return     array
     */
    public function userDefaults(): array
    {
        return Core::users()->userDefaults();
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
     * @deprecated since 2.28, use Core::rest()->enableRestServer() instead
     *
     * @param      bool  $serve  The flag
     */
    public function enableRestServer(bool $serve = true)
    {
        Core::rest()->enableRestServer($serve);
    }

    /**
     * Check if we need to serve REST requests.
     *
     * @deprecated since 2.28, use Core::rest()->serveRestRequests() instead
     *
     * @return     bool
     */
    public function serveRestRequests(): bool
    {
        return Core::rest()->serveRestRequests();
    }
    //@}
}
