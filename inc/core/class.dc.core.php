<?php
/**
 * @brief Dotclear core class
 *
 * dcCore is deprecated since 2.28.
 * Use App and their methods instead...
 *
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

use Dotclear\Core\Blogs;
use Dotclear\App;
use Dotclear\Core\Filter;
use Dotclear\Core\Formater;
use Dotclear\Core\Nonce;
use Dotclear\Core\PostType;
use Dotclear\Core\PostTypes;
use Dotclear\Core\Session;
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
use Dotclear\Helper\Behavior;
use Dotclear\Helper\Html\WikiToHtml;

final class dcCore
{
    // deprecated as App class does not allow dynamic properties
    use dcTraitDynamicProperties;

    // Constants

    /**
     * Session table name
     *
     * @deprecated since 2.28, use App::VERSION_TABLE_NAME
     *
     * @var string
     */
    public const SESSION_TABLE_NAME = Session::SESSION_TABLE_NAME;

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
     * @deprecated since 2.28, use App::con() instead
     *
     * @var AbstractHandler
     */
    public readonly AbstractHandler $con;

    /**
     * Database tables prefix
     *
     * @deprecated since 2.28, use App::con()->prefix() instead
     *
     * @var string
     */
    public readonly string $prefix;

    /**
     * dcBlog instance
     *
     * @deprecated since 2.28, use App::blogLodaer()->getBlog() instead
     *
     * @var dcBlog|null
     */
    public $blog;

    /**
     * dcAuth instance
     *
     * @deprecated since 2.28, use App::auth() instead
     *
     * @var dcAuth
     */
    public readonly dcAuth $auth;

    /**
     * Session in database instance
     *
     * @deprecated since 2.28, use App::session() instead
     *
     * @var Session
     */
    public readonly Session $session;

    /**
     * Url instance
     *
     * @deprecated since 2.28, use App::url() instead
     *
     * @var Url
     */
    public readonly Url $url;

    /**
     * dcRestServer instance
     *
     * @deprecated since 2.28, use App::rest() instead
     *
     * @var dcRestServer
     */
    public readonly dcRestServer $rest;

    /**
     * WikiToHtml instance
     *
     * @deprecated since 2.27, use App::filter()->wiki() instead
     *
     * @var WikiToHtml
     */
    public $wiki;

    /**
     * WikiToHtml instance
     *
     * @deprecated since 2.27, use App::filter()->wiki() instead
     *
     * @var WikiToHtml
     */
    public $wiki2xhtml;

    /**
     * Plugins
     *
     * @deprecated since 2.28, Use App::plugins() instead
     *
     * @var dcPlugins
     */
    public readonly dcPlugins $plugins;

    /**
     * Themes
     *
     * @deprecated since 2.28, Use App::themes() instead
     *
     * @var dcThemes
     */
    public $themes;

    /**
     * dcMedia instance
     *
     * @deprecated since 2.28, use App::media() instead
     *
     * @var dcMedia|null
     */
    public $media;

    /**
     * dcPostMedia instance
     *
     * @deprecated since 2.28, use App::postMedia() instead
     *
     * @var dcPostMedia
     */
    public $postmedia;

    /**
     * dcMeta instance
     *
     * @deprecated since 2.28, use App::meta() instead
     *
     * @var dcMeta
     */
    public readonly dcMeta $meta;

    /**
     * dcError instance
     *
     * @deprecated since 2.28, use App::session() instead
     *
     * @var dcError
     */
    public readonly dcError $error;

    /**
     * dcNotices instance
     *
     * @deprecated since 2.28, Use App::notice() instead
     *
     * @var dcNotices
     */
    public readonly dcNotices $notices;

    /**
     * dcLog instance
     *
     * @deprecated since 2.28, Use App::log() instead
     *
     * @var dcLog
     */
    public readonly dcLog $log;

    /**
     * Current language
     *
     * @deprecated since 2.28, Use App::lang() and App::setLang() instead
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
     * @deprecated since 2.28, Use App::backend() instead
     *
     * @var \Dotclear\Core\Backend\Utility
     */
    public Backend $admin;

    /**
     * Backend Url instance.
     *
     * @deprecated since 2.28, Use App::backend()->url instead
     *
     * @var \Dotclear\Core\Backend\Url
     */
    public \Dotclear\Core\Backend\Url $adminurl;

    /**
     * Bakcend Favorites instance.
     *
     * @deprecated since 2.28, Use App::backend()->favs instead
     *
     * @var \Dotclear\Core\Backend\Favorites
     */
    public \Dotclear\Core\Backend\Favorites $favs;

    /**
     * Backend Menus instance.
     *
     * @deprecated since 2.28, Use App::backend()->menus instead
     *
     * @var \Dotclear\Core\Backend\Menus
     */
    public \Dotclear\Core\Backend\Menus $menu;

    /**
     * Array of resources
     *
     * @deprecated since 2.28, Use App::backend()->resources instance
     *
     * @var array
     */
    public $resources = [];

    // Public context

    /**
     * Frontend Utility instance
     *
     * @deprecated since 2.28, Use App::frontend() instead
     *
     * @var \Dotclear\Core\Frontend\Utility
     */
    public Frontend $public;

    /**
     * Tpl instance
     *
     * @deprecated since 2.28, Use App::frontend()->tpl instead
     *
     * @var Tpl
     */
    public Tpl $tpl;

    /**
     * context instance
     *
     * @deprecated since 2.28, Use App::frontend()->ctx instead
     *
     * @var context|null
     */
    public $ctx;

    /**
     * HTTP Cache stack
     *
     * @deprecated since 2.28, permanently moved to App::frontend()->cache()
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
     * @deprecated since 2.28, Use AntispamInitFilters behavior instead
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

        // deprecated since 2.26, use Autoloader:me() instead
        $this->autoload = Autoloader::me();

        // deprecated since 2.28, use Autoloader:me() instead
        $this->con     = App::con();
        $this->prefix  = App::con()->prefix();
        $this->error   = App::error();
        $this->auth    = App::auth();
        $this->session = App::session();
        $this->url     = App::url();
        $this->plugins = App::plugins();
        $this->rest    = App::rest();
        $this->meta    = App::meta();
        $this->log     = App::log();
        $this->notices = App::notice();

        if (defined('DC_CONTEXT_ADMIN')) {
            // deprecated since 2.23, use App::backend()->resources instance instead
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
     * @deprecated since 2.28, use App::backend()->killAdminSession() instead
     */
    public function killAdminSession(): void
    {
        App::backend()->killAdminSession();
    }

    /// @name Blog init methods
    //@{
    /**
     * Sets the blog to use.
     *
     * @deprecated since 2.28, use App::blogLoader()->setBlog() instead
     *
     * @param      string  $id     The blog ID
     */
    public function setBlog($id): void
    {
        App::blogLoader()->setBlog($id);
    }

    /**
     * Unsets blog property.
     *
     * @deprecated since 2.28, use App::blogLoader()->unsetBlog() instead
     */
    public function unsetBlog(): void
    {
        App::blogLoader()->unsetBlog();
    }
    //@}

    /// @name Blog status methods
    //@{
    /**
     * Gets all blog status.
     *
     * @deprecated since 2.28, use App::blogs()->getAllBlogStatus() instead
     *
     * @return     array
     */
    public function getAllBlogStatus(): array
    {
        return App::blogs()->getAllBlogStatus();
    }

    /**
     * Returns a blog status name given to a code.
     *
     * @deprecated since 2.28, use App::blogs()->getBlogStatus() instead
     *
     * @param      int      $s      Status code
     *
     * @return     string
     */
    public function getBlogStatus(int $s): string
    {
        return App::blogs()->getBlogStatus($s);
    }
    //@}

    /// @name Admin nonce secret methods
    //@{
    /**
     * Gets the nonce.
     *
     * @deprecated since 2.28, use App::nonce()->getNonce() instead
     *
     * @return     string
     */
    public function getNonce(): string
    {
        return App::nonce()->getNonce();
    }

    /**
     * Check the nonce.
     *
     * @deprecated since 2.28, use App::nonce()->checkNonce() instead
     *
     * @param      string  $secret  The nonce
     *
     * @return     bool
     */
    public function checkNonce(string $secret): bool
    {
        return App::nonce()->checkNonce($secret);
    }

    /**
     * Get the nonce HTML code.
     *
     * @deprecated since 2.28, use App::nonce()->formNonce() or App::nonce()->getFormNonce()instead
     *
     * @param      bool     $render     Should render element?
     *
     * @return     mixed
     */
    public function formNonce(bool $render = true)
    {
        return $render ? App::nonce()->getFormNonce() : App::nonce()->formNonce();
    }
    //@}

    /// @name Text Formatters methods
    //@{
    /**
     * Adds a new text formater.
     *
     * @deprecated since 2.28, use App::formater()->addEditorFormater() instead
     *
     * @param      string    $editor_id  The editor identifier (dcLegacyEditor, dcCKEditor, ...)
     * @param      string    $name       The formater name
     * @param      callable  $func       The function to use, must be a valid and callable callback
     */
    public function addEditorFormater(string $editor_id, string $name, $func): void
    {
        App::formater()->addEditorFormater($editor_id, $name, $func);
    }

    /**
     * Adds a new dcLegacyEditor text formater.
     *
     * @deprecated since 2.28, use App::formater()->addEditorFormater('dcLegacyEditor', ...) instead
     *
     * @param      string    $name       The formater name
     * @param      callable  $func       The function to use, must be a valid and callable callback
     */
    public function addFormater(string $name, $func): void
    {
        App::formater()->addEditorFormater('dcLegacyEditor', $name, $func);
    }

    /**
     * Adds a formater name.
     *
     * @deprecated since 2.28, use App::formater()->addFormaterName() instead
     *
     * @param      string  $format  The format
     * @param      string  $name    The name
     */
    public function addFormaterName(string $format, string $name): void
    {
        App::formater()->addFormaterName($format, $name);
    }

    /**
     * Gets the formater name.
     *
     * @deprecated since 2.28, use App::formater()->getFormaterName() instead
     *
     * @param      string  $format  The format
     *
     * @return     string
     */
    public function getFormaterName(string $format): string
    {
        return App::formater()->getFormaterName($format);
    }

    /**
     * Gets the editors list.
     *
     * @deprecated since 2.28, use App::formater()->getEditors() instead
     *
     * @return     array
     */
    public function getEditors(): array
    {
        return App::formater()->getEditors();
    }

    /**
     * Gets the formaters.
     *
     * @deprecated since 2.28, use App::formater()->getFormaters() or App::formater()->getFormater(xxx) instead
     *
     * @param      string  $editor_id  The editor identifier (dcLegacyEditor, dcCKEditor, ...)
     *
     * @return     array
     */
    public function getFormaters(string $editor_id = ''): array
    {
        return empty($editor_id) ? App::formater()->getFormaters() : App::formater()->getFormater($editor_id);
    }

    /**
     * Call editor formater.
     *
     * @deprecated since 2.28, use App::formater()->callEditorFormater() instead
     *
     * @param      string  $editor_id  The editor identifier (dcLegacyEditor, dcCKEditor, ...)
     * @param      string  $name       The formater name
     * @param      string  $str        The string to transform
     *
     * @return     string
     */
    public function callEditorFormater(string $editor_id, string $name, string $str): string
    {
        return App::formater()->callEditorFormater($editor_id, $name, $str);
    }

    /**
     * Call formater.
     *
     * @deprecated since 2.28, use App::formater()->callEditorFormater('dcLegacyEditor', ...) instead
     *
     * @param      string  $name   The name
     * @param      string  $str    The string
     *
     * @return     string
     */
    public function callFormater(string $name, string $str): string
    {
        return App::formater()->callEditorFormater('dcLegacyEditor', $name, $str);
    }
    //@}

    /// @name Behaviors methods
    //@{
    /**
     * Adds a new behavior to behaviors stack.
     *
     * @deprecated since 2.28, use App::behavior()->addBehavior() instead
     *
     * @param      string           $behavior  The behavior
     * @param      callable|array   $func      The function
     */
    public function addBehavior(string $behavior, $func): void
    {
        App::behavior()->addBehavior($behavior, $func);
    }

    /**
     * Adds a behaviour (alias).
     *
     * @deprecated since 2.28, use App::behavior()->addBehavior() instead
     *
     * @param      string           $behaviour  The behaviour
     * @param      callable|array   $func       The function
     */
    public function addBehaviour(string $behaviour, $func): void
    {
        App::behavior()->addBehavior($behaviour, $func);
    }

    /**
     * Adds new behaviors to behaviors stack.
     *
     * @deprecated since 2.28, use App::behavior()->addBehaviors() instead
     *
     * @param      array    $behaviors  The behaviors
     */
    public function addBehaviors(array $behaviors): void
    {
        App::behavior()->addBehaviors($behaviors);
    }

    /**
     * Adds behaviours (alias).
     *
     * @deprecated since 2.28, use App::behavior()->addBehaviors() instead
     *
     * @param      array    $behaviours  The behaviours
     */
    public function addBehaviours(array $behaviours): void
    {
        App::behavior()->addBehaviors($behaviours);
    }

    /**
     * Determines if behavior exists in behaviors stack.
     *
     * @deprecated since 2.28, use App::behavior()->hasBehavior() instead
     *
     * @param      string  $behavior  The behavior
     *
     * @return     bool
     */
    public function hasBehavior(string $behavior): bool
    {
        return App::behavior()->hasBehavior($behavior);
    }

    /**
     * Determines if behaviour exists (alias).
     *
     * @deprecated since 2.28, use App::behavior()->hasBehavior() instead
     *
     * @param      string  $behaviour  The behavior
     *
     * @return     bool
     */
    public function hasBehaviour(string $behaviour): bool
    {
        return App::behavior()->hasBehavior($behaviour);
    }

    /**
     * Gets the behaviors stack (or part of).
     *
     * @deprecated since 2.28, use App::behavior()->getBehaviors() or App::behavior()->getBehavior()instead
     *
     * @param      string  $behavior  The behavior
     *
     * @return     mixed
     */
    public function getBehaviors(string $behavior = '')
    {
        return empty($behavior) ? App::behavior()->getBehaviors() : App::behavior()->getBehavior($behavior);
    }

    /**
     * Gets the behaviours stack (alias).
     *
     * @deprecated since 2.28, use App::behavior()->getBehaviors() or App::behavior()->getBehavior() instead
     *
     * @param      string  $behaviour  The behaviour
     *
     * @return     mixed
     */
    public function getBehaviours(string $behaviour = '')
    {
        return empty($behaviour) ? App::behavior()->getBehaviors() : App::behavior()->getBehavior($behaviour);
    }

    /**
     * Calls every function in behaviors stack for a given behavior and returns
     * concatened result of each function.
     *
     * @deprecated since 2.28, use App::behavior()->callBehavior() instead
     *
     * @param      string  $behavior  The behavior
     * @param      mixed   ...$args   The arguments
     *
     * @return     mixed
     */
    public function callBehavior(string $behavior, ...$args)
    {
        return App::behavior()->callBehavior($behavior, ...$args);
    }

    /**
     * Calls every function in behaviours stack (alias).
     *
     * @deprecated since 2.28, use App::behavior()->callBehavior() instead
     *
     * @param      string  $behaviour  The behaviour
     * @param      mixed   ...$args    The arguments
     *
     * @return     mixed
     */
    public function callBehaviour(string $behaviour, ...$args)
    {
        return App::behavior()->callBehavior($behaviour, ...$args);
    }
    //@}

    /// @name Post types URLs management
    //@{

    /**
     * Gets the post admin url.
     *
     * @deprecated since 2.28, use App::postTypes()->get($type)->adminUrl() instead
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
        return App::postTypes()->get($type)->adminUrl($post_id, $escaped, $params);
    }

    /**
     * Gets the post public url.
     *
     * @deprecated since 2.28, use App::postTypes()->get($type)->publicUrl() instead
     *
     * @param      string  $type      The type
     * @param      string  $post_url  The post url
     * @param      bool    $escaped   Escape the URL
     *
     * @return     string    The post public url.
     */
    public function getPostPublicURL(string $type, string $post_url, bool $escaped = true): string
    {
        return App::postTypes()->get($type)->publicUrl($post_url, $escaped);
    }

    /**
     * Sets the post type.
     *
     * @deprecated since 2.28, use App::postTypes()->set(new PostType()) instead
     *
     * @param      string  $type        The type
     * @param      string  $admin_url   The admin url
     * @param      string  $public_url  The public url
     * @param      string  $label       The label
     */
    public function setPostType(string $type, string $admin_url, string $public_url, string $label = ''): void
    {
        App::postTypes()->set(new PostType($type, $admin_url, $public_url, $label));
    }

    /**
     * Gets the post types.
     *
     * @deprecated since 2.28, use App::postTypes()->dump() instead
     *
     * @return     array  The post types.
     */
    public function getPostTypes(): array
    {
        return App::postTypes()->getPostTypes();
    }
    //@}

    /// @name Versions management methods
    //@{
    /**
     * Gets the version of a module.
     *
     * @deprecated since 2.28, use App::version()->getVersion() instead
     *
     * @param      string  $module  The module
     *
     * @return     null|string
     */
    public function getVersion(string $module = 'core'): ?string
    {
        $v = App::version()->getVersion($module);

        // keep compatibility with old return type
        return $v === '' ? null : $v;
    }

    /**
     * Gets all known versions.
     *
     * @deprecated since 2.28, use App::version()->getVersions() instead
     *
     * @return     array
     */
    public function getVersions(): array
    {
        return App::version()->getVersions();
    }

    /**
     * Sets the version of a module.
     *
     * @deprecated since 2.28, use App::version()->setVersion() instead
     *
     * @param      string  $module   The module
     * @param      string  $version  The version
     */
    public function setVersion(string $module, string $version): void
    {
        App::version()->setVersion($module, $version);
    }

    /**
     * Compare the given version of a module with the registered one.
     *
     * @deprecated since 2.28, use App::version()->compareVersion() instead
     *
     * @param      string  $module   The module
     * @param      string  $version  The version
     *
     * @return     int
     */
    public function testVersion(string $module, string $version): int
    {
        return App::version()->compareVersion($module, $version);
    }

    /**
     * Test if a version is a new one.
     *
     * @deprecated since 2.28, use App::version()->newerVersion() instead
     *
     * @param      string  $module   The module
     * @param      string  $version  The version
     *
     * @return     bool
     */
    public function newVersion(string $module, string $version): bool
    {
        return App::version()->newerVersion($module, $version);
    }

    /**
     * Remove a module version entry.
     *
     * @deprecated since 2.28, use App::version()->unsetVersion() instead
     *
     * @param      string  $module  The module
     */
    public function delVersion(string $module): void
    {
        App::version()->unsetVersion($module);
    }
    //@}

    /// @name Users management methods
    //@{
    /**
     * Gets the user by its ID.
     *
     * @deprecated since 2.28, use App::users()->getUser() instead
     *
     * @param      string  $id     The identifier
     *
     * @return     MetaRecord.
     */
    public function getUser(string $id): MetaRecord
    {
        return App::users()->getUser($id);
    }

    /**
     * Returns a users list.
     *
     * @deprecated since 2.28, use App::users()->getUsers() instead
     *
     * @param      array|ArrayObject    $params      The parameters
     * @param      bool                 $count_only  Count only results
     *
     * @return     MetaRecord
     */
    public function getUsers($params = [], bool $count_only = false): MetaRecord
    {
        return App::users()->getUsers($params, $count_only);
    }

    /**
     * Adds a new user.
     *
     * @deprecated since 2.28, use App::users()->addUser() instead
     *
     * @param      Cursor     $cur    The user Cursor
     *
     * @throws     Exception
     *
     * @return     string
     */
    public function addUser(Cursor $cur): string
    {
        return App::users()->addUser($cur);
    }

    /**
     * Updates an existing user. Returns the user ID.
     *
     * @deprecated since 2.28, use App::users()->updUser() instead
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
        return App::users()->updUser($id, $cur);
    }

    /**
     * Deletes a user.
     *
     * @deprecated since 2.28, use App::users()->delUser() instead
     *
     * @param      string     $id     The user identifier
     *
     * @throws     Exception
     */
    public function delUser(string $id): void
    {
        App::users()->delUser($id);
    }

    /**
     * Determines if user exists.
     *
     * @deprecated since 2.28, use App::users()->userExists() instead
     *
     * @param      string  $id     The identifier
     *
     * @return      bool
     */
    public function userExists(string $id): bool
    {
        return App::users()->userExists($id);
        ;
    }

    /**
     * Returns all user permissions as an array.
     *
     * @deprecated since 2.28, use App::users()->getUserPermissions() instead
     *
     * @param      string  $id     The user identifier
     *
     * @return     array
     */
    public function getUserPermissions(string $id): array
    {
        return App::users()->getUserPermissions($id);
    }

    /**
     * Sets user permissions.
     *
     * @deprecated since 2.28, use App::users()->setUserPermissions() instead
     *
     * @param      string     $id     The user identifier
     * @param      array      $perms  The permissions
     *
     * @throws     Exception
     */
    public function setUserPermissions(string $id, array $perms): void
    {
        App::users()->setUserPermissions($id, $perms);
    }

    /**
     * Sets the user blog permissions.
     *
     * @deprecated since 2.28, use App::users()->setUserBlogPermissions() instead
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
        App::users()->setUserBlogPermissions($id, $blog_id, $perms, $delete_first);
    }

    /**
     * Sets the user default blog. This blog will be selected when user log in.
     *
     * @deprecated since 2.28, use App::users()->setUserDefaultBlog() instead
     *
     * @param      string  $id       The user identifier
     * @param      string  $blog_id  The blog identifier
     */
    public function setUserDefaultBlog(string $id, string $blog_id): void
    {
        App::users()->setUserDefaultBlog($id, $blog_id);
    }

    /**
     * Removes users default blogs.
     *
     * @deprecated since 2.28, use App::users()->removeUsersDefaultBlogs() instead
     *
     * @param      array  $ids    The blogs to remove
     */
    public function removeUsersDefaultBlogs(array $ids): void
    {
        App::users()->removeUsersDefaultBlogs($ids);
    }

    /**
     * Returns user default settings in an associative array with setting names in keys.
     *
     * @deprecated since 2.28, use App::users()->userDefaults() instead
     *
     * @return     array
     */
    public function userDefaults(): array
    {
        return App::users()->userDefaults();
    }
    //@}

    /// @name Blog management methods
    //@{
    /**
     * Returns all blog permissions (users) as an array.
     *
     * @deprecated since 2.28, use App::blogs()->getBlogPermissions() instead
     *
     * @param      string  $id          The blog identifier
     * @param      bool    $with_super  Includes super admins in result
     *
     * @return     array
     */
    public function getBlogPermissions(string $id, bool $with_super = true): array
    {
        return App::blogs()->getBlogPermissions($id, $with_super);
    }

    /**
     * Gets the blog.
     *
     * @deprecated since 2.28, use App::blogs()->getBlogPermissions() instead
     *
     * @param      string  $id     The blog identifier
     *
     * @return     MetaRecord|false.
     */
    public function getBlog(string $id)
    {
        return App::blogs()->getBlog($id);
    }

    /**
     * Returns a MetaRecord of blogs.
     *
     * @deprecated since 2.28, use App::blogs()->getBlogs() instead
     *
     * @param      array|ArrayObject    $params      The parameters
     * @param      bool                 $count_only  Count only results
     *
     * @return     MetaRecord.
     */
    public function getBlogs($params = [], bool $count_only = false): MetaRecord
    {
        return App::blogs()->getBlogs($params, $count_only);
    }

    /**
     * Adds a new blog.
     *
     * @deprecated since 2.28, use App::blogs()->addBlog() instead
     *
     * @param      Cursor     $cur    The blog Cursor
     *
     * @throws     Exception
     */
    public function addBlog(Cursor $cur): void
    {
        App::blogs()->addBlog($cur);
    }

    /**
     * Updates a given blog.
     *
     * @deprecated since 2.28, use App::blogs()->updBlog() instead
     *
     * @param      string  $id     The blog identifier
     * @param      Cursor  $cur    The Cursor
     */
    public function updBlog(string $id, Cursor $cur): void
    {
        App::blogs()->updBlog($id, $cur);
    }

    /**
     * Removes a given blog.
     *
     * @deprecated since 2.28, use App::blogs()->delBlog() instead
     *
     * @param      string     $id     The blog identifier
     *
     * @throws     Exception
     */
    public function delBlog(string $id): void
    {
        App::blogs()->delBlog($id);
    }

    /**
     * Determines if blog exists.
     *
     * @deprecated since 2.28, use App::blogs()->blogExists() instead
     *
     * @param      string  $id     The blog identifier
     *
     * @return     bool
     */
    public function blogExists(string $id): bool
    {
        return App::blogs()->blogExists($id);
    }

    /**
     * Counts the number of blog posts.
     *
     * @deprecated since 2.28, use App::blogs()->countBlogPosts() instead
     *
     * @param      string        $id     The blog identifier
     * @param      null|string   $type   The post type
     *
     * @return     int
     */
    public function countBlogPosts(string $id, $type = null): int
    {
        return App::blogs()->countBlogPosts($id, $type);
    }
    //@}

    /// @name HTML Filter methods
    //@{
    /**
     * Calls HTML filter to drop bad tags and produce valid HTML output.
     *
     * @deprecated since 2.28, use App::filter()->HTMLfilter() instead
     *
     * @param      string  $str    The string
     *
     * @return     string
     */
    public function HTMLfilter(string $str): string
    {
        return App::filter()->HTMLfilter($str);
    }
    //@}

    /// @name WikiToHtml methods
    //@{
    /**
     * Returns a transformed string with WikiToHtml.
     *
     * @deprecated since 2.28, use App::filter()->wikiTransform() instead
     *
     * @param      string  $str    The string
     *
     * @return     string
     */
    public function wikiTransform(string $str): string
    {
        return App::filter()->wikiTransform($str);
    }

    /**
     * Inits <var>wiki</var> property for blog post.
     *
     * @deprecated since 2.28, use App::filter()->initWikiPost() instead
     */
    public function initWikiPost(): void
    {
        App::filter()->initWikiPost();
    }

    /**
     * Inits <var>wiki</var> property for simple blog comment (basic syntax).
     *
     * @deprecated since 2.28, use App::filter()->initWikiSimpleComment() instead
     */
    public function initWikiSimpleComment(): void
    {
        App::filter()->initWikiSimpleComment();
    }

    /**
     * Inits <var>wiki</var> property for blog comment.
     *
     * @deprecated since 2.28, use App::filter()->initWikiComment() instead
     */
    public function initWikiComment(): void
    {
        App::filter()->initWikiComment();
    }

    /**
     * Get info about a post:id wiki macro.
     *
     * @deprecated since 2.28, use App::filter()->wikiPostLink() instead
     *
     * @param      string  $url      The post url
     * @param      string  $content  The content
     *
     * @return     array
     */
    public function wikiPostLink(string $url, string $content): array
    {
        return App::filter()->wikiPostLink($url, $content);
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
     * @deprecated since 2.28, use App::rest()->enableRestServer() instead
     *
     * @param      bool  $serve  The flag
     */
    public function enableRestServer(bool $serve = true)
    {
        App::rest()->enableRestServer($serve);
    }

    /**
     * Check if we need to serve REST requests.
     *
     * @deprecated since 2.28, use App::rest()->serveRestRequests() instead
     *
     * @return     bool
     */
    public function serveRestRequests(): bool
    {
        return App::rest()->serveRestRequests();
    }
    //@}
}
