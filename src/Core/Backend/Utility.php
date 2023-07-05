<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * Utility class for admin context.
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core\Backend;

use ArrayObject;
use dcCore;
use dcNotices;
use dcTraitDynamicProperties;
use Dotclear\Core\Process;
use Dotclear\Fault;
use Dotclear\Helper\L10n;
use Dotclear\Helper\Network\Http;
use Exception;

class Utility extends Process
{
    use dcTraitDynamicProperties;

    /**
     * Current admin page URL
     *
     * @var string
     */
    protected $p_url;

    /** @var    Url     Backend (admin) Url handler instance */
    public Url $url;

    /** @var    Favorites   Backend (admin) Favorites handler instance */
    public Favorites $favs;

    // Constants

    // Menu sections
    public const MENU_FAVORITES = 'Favorites';
    public const MENU_BLOG      = 'Blog';
    public const MENU_SYSTEM    = 'System';
    public const MENU_PLUGINS   = 'Plugins';

    /**
     * Constructs a new instance.
     *
     * @throws     Exception  (if not admin context)
     */
    public function __construct()
    {
        if (!defined('DC_CONTEXT_ADMIN')) {
            throw new Exception('Application is not in administrative context.', 500);
        }

        // HTTP/1.1
        header('Expires: Mon, 13 Aug 2003 07:48:00 GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
    }

    /**
     * Prepare the context.
     *
     * @return     bool
     */
    public static function init(): bool
    {
        define('DC_CONTEXT_ADMIN', true);

        return true;
    }

    /**
     * Instanciate this as a singleton and initializes the context.
     */
    public static function process(): bool
    {
        if (!(dcCore::app()->admin instanceof self)) {
            dcCore::app()->admin = new self();
        }

        // New adminurl instance
        // May be moved to property of dcCore::app()->admin in a near future
        dcCore::app()->admin->url = new Url();
        dcCore::app()->admin->url->register('admin.auth', 'Auth');

        /** @deprecated since 2.27 Use dcCore::app()->admin->url */
        dcCore::app()->adminurl = dcCore::app()->admin->url;

        if (dcCore::app()->auth->sessionExists()) {
            # If we have a session we launch it now
            try {
                if (!dcCore::app()->auth->checkSession()) {
                    # Avoid loop caused by old cookie
                    $p    = dcCore::app()->session->getCookieParameters(false, -600);
                    $p[3] = '/';
                    setcookie(...$p);

                    // Preserve safe_mode if necessary
                    $params = !empty($_REQUEST['safe_mode']) ? ['safe_mode' => 1] : [];
                    dcCore::app()->admin->url->redirect('admin.auth', $params);
                }
            } catch (Exception $e) {
                new Fault(__('Database error'), __('There seems to be no Session table in your database. Is Dotclear completly installed?'), Fault::DATABASE_ISSUE);
            }

            # Fake process to logout (kill session) and return to auth page.
            dcCore::app()->admin->url->register('admin.logout', 'Logout');
            if (!empty($_REQUEST['process']) && $_REQUEST['process'] == 'Logout') {
                // Enable REST service if disabled, for next requests
                if (!dcCore::app()->serveRestRequests()) {
                    dcCore::app()->enableRestServer(true);
                }
                // Kill admin session
                dcCore::app()->killAdminSession();
                // Logout
                dcCore::app()->admin->url->redirect('admin.auth');
                exit;
            }

            # Check nonce from POST requests
            if (!empty($_POST) && (empty($_POST['xd_check']) || !dcCore::app()->checkNonce($_POST['xd_check']))) {
                new Fault('Precondition Failed', __('Precondition Failed'), 412);
            }

            if (!empty($_REQUEST['switchblog']) && dcCore::app()->auth->getPermissions($_REQUEST['switchblog']) !== false) {
                $_SESSION['sess_blog_id'] = $_REQUEST['switchblog'];

                if (!empty($_REQUEST['redir'])) {
                    # Keep context as far as possible
                    $redir = (string) $_REQUEST['redir'];
                } else {
                    # Removing switchblog from URL
                    $redir = (string) $_SERVER['REQUEST_URI'];
                    $redir = (string) preg_replace('/switchblog=(.*?)(&|$)/', '', $redir);
                    $redir = (string) preg_replace('/\?$/', '', $redir);
                }

                dcCore::app()->auth->user_prefs->interface->drop('media_manager_dir');

                if (!empty($_REQUEST['process']) && $_REQUEST['process'] == 'Media' || strstr($redir, 'media.php') !== false) {
                    // Remove current media dir from media manager URL
                    $redir = (string) preg_replace('/d=(.*?)(&|$)/', '', $redir);
                }

                Http::redirect($redir);
                exit;
            }

            # Check blog to use and log out if no result
            if (isset($_SESSION['sess_blog_id'])) {
                if (dcCore::app()->auth->getPermissions($_SESSION['sess_blog_id']) === false) {
                    unset($_SESSION['sess_blog_id']);
                }
            } else {
                if (($b = dcCore::app()->auth->findUserBlog(dcCore::app()->auth->getInfo('user_default_blog'), false)) !== false) {
                    $_SESSION['sess_blog_id'] = $b;
                    unset($b);
                }
            }

            # Loading locales
            Helper::loadLocales();
            /*
             * @var        string
             *
             * @deprecated Since 2.23, use dcCore::app()->lang instead
             */
            $GLOBALS['_lang'] = &dcCore::app()->lang;

            if (isset($_SESSION['sess_blog_id'])) {
                dcCore::app()->setBlog($_SESSION['sess_blog_id']);
            } else {
                dcCore::app()->session->destroy();
                dcCore::app()->admin->url->redirect('admin.auth');
            }
        }

        dcCore::app()->admin->url->register('admin.posts', 'Posts');
        dcCore::app()->admin->url->register('admin.popup_posts', 'PostsPopup'); //use admin.posts.popup
        dcCore::app()->admin->url->register('admin.posts.popup', 'PostsPopup');
        dcCore::app()->admin->url->register('admin.post', 'Post');
        dcCore::app()->admin->url->register('admin.post.media', 'PostMedia');
        dcCore::app()->admin->url->register('admin.blog.theme', 'BlogTheme');
        dcCore::app()->admin->url->register('admin.blog.pref', 'BlogPref');
        dcCore::app()->admin->url->register('admin.blog.del', 'BlogDel');
        dcCore::app()->admin->url->register('admin.blog', 'Blog');
        dcCore::app()->admin->url->register('admin.blogs', 'Blogs');
        dcCore::app()->admin->url->register('admin.categories', 'Categories');
        dcCore::app()->admin->url->register('admin.category', 'Category');
        dcCore::app()->admin->url->register('admin.comments', 'Comments');
        dcCore::app()->admin->url->register('admin.comment', 'Comment');
        dcCore::app()->admin->url->register('admin.help', 'Help');
        dcCore::app()->admin->url->register('admin.help.charte', 'HelpCharte');
        dcCore::app()->admin->url->register('admin.home', 'Home');
        dcCore::app()->admin->url->register('admin.langs', 'Langs');
        dcCore::app()->admin->url->register('admin.link.popup', 'LinkPopup');
        dcCore::app()->admin->url->register('admin.media', 'Media');
        dcCore::app()->admin->url->register('admin.media.item', 'MediaItem');
        dcCore::app()->admin->url->register('admin.plugins', 'Plugins');
        dcCore::app()->admin->url->register('admin.plugin', 'Plugin');
        dcCore::app()->admin->url->register('admin.search', 'Search');
        dcCore::app()->admin->url->register('admin.user.preferences', 'UserPreferences');
        dcCore::app()->admin->url->register('admin.user', 'User');
        dcCore::app()->admin->url->register('admin.user.actions', 'UsersActions');
        dcCore::app()->admin->url->register('admin.users', 'Users');
        dcCore::app()->admin->url->register('admin.help', 'Help');
        dcCore::app()->admin->url->register('admin.update', 'Update');
        dcCore::app()->admin->url->register('admin.csp.report', 'CspReport');
        dcCore::app()->admin->url->register('admin.rest', 'Rest');

        // we don't care of admin process for FileServer
        dcCore::app()->admin->url->register('load.plugin.file', 'index.php', ['pf' => 'dummy.css']);
        dcCore::app()->admin->url->register('load.var.file', 'index.php', ['vf' => 'dummy.json']);

        // (re)set post type with real backend URL (as admin URL handler is known yet)
        dcCore::app()->setPostType('post', urldecode(dcCore::app()->admin->url->get('admin.post', ['id' => '%d'], '&')), dcCore::app()->url->getURLFor('post', '%s'), 'Posts');

        if (dcCore::app()->auth->userID() && dcCore::app()->blog !== null) {
            # Loading resources and help files
            require DC_L10N_ROOT . '/en/resources.php';
            if ($f = L10n::getFilePath(DC_L10N_ROOT, '/resources.php', dcCore::app()->lang)) {
                require $f;
            }
            unset($f);

            if (($hfiles = @scandir(DC_L10N_ROOT . '/' . dcCore::app()->lang . '/help')) !== false) {
                foreach ($hfiles as $hfile) {
                    if (preg_match('/^(.*)\.html$/', $hfile, $m)) {
                        dcCore::app()->resources['help'][$m[1]] = DC_L10N_ROOT . '/' . dcCore::app()->lang . '/help/' . $hfile;
                    }
                }
            }
            unset($hfiles);
            // Contextual help flag
            dcCore::app()->resources['ctxhelp'] = false;

            $user_ui_nofavmenu = dcCore::app()->auth->user_prefs->interface->nofavmenu;

            dcCore::app()->notices = new dcNotices();
            
            dcCore::app()->admin->favs    = new Favorites();

            /** @deprecated since 2.27 Use dcCore::app()->admin->favs */
            dcCore::app()->favs = dcCore::app()->admin->favs;

            # Menus creation
            dcCore::app()->menu = new ArrayObject();

            /*
             * @var        ArrayObject
             *
             * @deprecated Since 2.23, use dcCore::app()->menu instead
             */
            $GLOBALS['_menu'] = dcCore::app()->menu;

            if (!$user_ui_nofavmenu) {
                dcCore::app()->admin->favs->appendMenuTitle(dcCore::app()->menu);
            }
            dcCore::app()->menu[self::MENU_BLOG]    = new Menu('blog-menu', 'Blog');
            dcCore::app()->menu[self::MENU_SYSTEM]  = new Menu('system-menu', 'System');
            dcCore::app()->menu[self::MENU_PLUGINS] = new Menu('plugins-menu', 'Plugins');

            # Loading plugins
            dcCore::app()->plugins->loadModules(DC_PLUGINS_ROOT, 'admin', dcCore::app()->lang);
            dcCore::app()->admin->favs->setup();

            if (!$user_ui_nofavmenu) {
                dcCore::app()->admin->favs->appendMenu(dcCore::app()->menu);
            }

            # Set menu titles

            dcCore::app()->menu[self::MENU_SYSTEM]->title  = __('System settings');   // @phpstan-ignore-line
            dcCore::app()->menu[self::MENU_BLOG]->title    = __('Blog');              // @phpstan-ignore-line
            dcCore::app()->menu[self::MENU_PLUGINS]->title = __('Plugins');           // @phpstan-ignore-line

            Helper::addMenuItem(
                self::MENU_BLOG,
                __('Blog appearance'),
                'admin.blog.theme',
                ['images/menu/themes.svg', 'images/menu/themes-dark.svg'],
                dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                    dcCore::app()->auth::PERMISSION_ADMIN,
                ]), dcCore::app()->blog->id),
                false,
                false,
                'BlogTheme'
            );
            Helper::addMenuItem(
                self::MENU_BLOG,
                __('Blog settings'),
                'admin.blog.pref',
                ['images/menu/blog-pref.svg', 'images/menu/blog-pref-dark.svg'],
                dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                    dcCore::app()->auth::PERMISSION_ADMIN,
                ]), dcCore::app()->blog->id),
                false,
                false,
                'BlogPref'
            );
            Helper::addMenuItem(
                self::MENU_BLOG,
                __('Media manager'),
                'admin.media',
                ['images/menu/media.svg', 'images/menu/media-dark.svg'],
                dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                    dcCore::app()->auth::PERMISSION_MEDIA,
                    dcCore::app()->auth::PERMISSION_MEDIA_ADMIN,
                ]), dcCore::app()->blog->id),
                false,
                false,
                'Media'
            );
            Helper::addMenuItem(
                self::MENU_BLOG,
                __('Categories'),
                'admin.categories',
                ['images/menu/categories.svg', 'images/menu/categories-dark.svg'],
                dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                    dcCore::app()->auth::PERMISSION_CATEGORIES,
                ]), dcCore::app()->blog->id),
                false,
                false,
                'Categories'
            );
            Helper::addMenuItem(
                self::MENU_BLOG,
                __('Search'),
                'admin.search',
                ['images/menu/search.svg','images/menu/search-dark.svg'],
                dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                    dcCore::app()->auth::PERMISSION_USAGE,
                    dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
                ]), dcCore::app()->blog->id),
                false,
                false,
                'Search'
            );
            Helper::addMenuItem(
                self::MENU_BLOG,
                __('Comments'),
                'admin.comments',
                ['images/menu/comments.svg', 'images/menu/comments-dark.svg'],
                dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                    dcCore::app()->auth::PERMISSION_USAGE,
                    dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
                ]), dcCore::app()->blog->id),
                false,
                false,
                'Comments'
            );
            Helper::addMenuItem(
                self::MENU_BLOG,
                __('Posts'),
                'admin.posts',
                ['images/menu/entries.svg', 'images/menu/entries-dark.svg'],
                dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                    dcCore::app()->auth::PERMISSION_USAGE,
                    dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
                ]), dcCore::app()->blog->id),
                false,
                false,
                'Posts'
            );
            Helper::addMenuItem(
                self::MENU_BLOG,
                __('New post'),
                'admin.post',
                ['images/menu/edit.svg', 'images/menu/edit-dark.svg'],
                dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                    dcCore::app()->auth::PERMISSION_USAGE,
                    dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
                ]), dcCore::app()->blog->id),
                true,
                true,
                'NewPost'
            );

            Helper::addMenuItem(
                self::MENU_SYSTEM,
                __('My preferences'),
                'admin.user.preferences',
                ['images/menu/user-pref.svg', 'images/menu/user-pref.svg'],
                true,
                false,
                false,
                'UserPref'
            );
            Helper::addMenuItem(
                self::MENU_SYSTEM,
                __('Update'),
                'admin.update',
                ['images/menu/update.svg', 'images/menu/update-dark.svg'],
                dcCore::app()->auth->isSuperAdmin() && is_readable(DC_DIGESTS),
                false,
                false,
                'Update'
            );
            Helper::addMenuItem(
                self::MENU_SYSTEM,
                __('Languages'),
                'admin.langs',
                ['images/menu/langs.svg', 'images/menu/langs-dark.svg'],
                dcCore::app()->auth->isSuperAdmin(),
                false,
                false,
                'Langs'
            );
            Helper::addMenuItem(
                self::MENU_SYSTEM,
                __('Plugins management'),
                'admin.plugins',
                ['images/menu/plugins.svg', 'images/menu/plugins-dark.svg'],
                dcCore::app()->auth->isSuperAdmin(),
                false,
                false,
                'Plugins'
            );
            Helper::addMenuItem(
                self::MENU_SYSTEM,
                __('Users'),
                'admin.users',
                'images/menu/users.svg',
                dcCore::app()->auth->isSuperAdmin(),
                false,
                false,
                'Users'
            );
            Helper::addMenuItem(
                self::MENU_SYSTEM,
                __('Blogs'),
                'admin.blogs',
                ['images/menu/blogs.svg', 'images/menu/blogs-dark.svg'],
                dcCore::app()->auth->isSuperAdmin() || dcCore::app()->auth->check(
                    dcCore::app()->auth->makePermissions([
                        dcCore::app()->auth::PERMISSION_USAGE,
                        dcCore::app()->auth::PERMISSION_CONTENT_ADMIN,
                    ]),
                    dcCore::app()->blog->id
                ) && dcCore::app()->auth->getBlogCount() > 1,
                false,
                false,
                'Blogs'
            );

            if (empty(dcCore::app()->blog->settings->system->jquery_migrate_mute)) {
                dcCore::app()->blog->settings->system->put('jquery_migrate_mute', true, 'boolean', 'Mute warnings for jquery migrate plugin ?', false);
            }
            if (empty(dcCore::app()->blog->settings->system->jquery_allow_old_version)) {
                dcCore::app()->blog->settings->system->put('jquery_allow_old_version', false, 'boolean', 'Allow older version of jQuery', false, true);
            }

            # Admin behaviors
            dcCore::app()->addBehavior('adminPopupPosts', [BlogPref::class, 'adminPopupPosts']);
        }

        return true;
    }

    /**
     * Sets the admin page URL.
     *
     * @param      string  $value  The value
     */
    public function setPageURL(string $value): void
    {
        $this->p_url = $value;

        /*
         * @deprecated since 2.24, may be removed in near future
         *
         * @var string
         */
        $GLOBALS['p_url'] = $value;
    }

    /**
     * Gets the admin page URL.
     *
     * @return     string   The URL.
     */
    public function getPageURL(): string
    {
        return (string) $this->p_url;
    }
}
