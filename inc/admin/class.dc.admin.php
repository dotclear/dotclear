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

use Dotclear\Fault;
use Dotclear\Helper\L10n;
use Dotclear\Helper\Network\Http;

class dcAdmin
{
    use dcTraitDynamicProperties;

    /**
     * Current admin page URL
     *
     * @var string
     */
    protected $p_url;

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
     * Instanciate this as a singleton
     *
     * @return     self
     */
    public static function bootstrap(): self
    {
        if (!dcCore::app()->admin) {
            // Init singleton
            dcCore::app()->admin = new self();
        }

        return dcCore::app()->admin;
    }

    /**
     * Initializes the context.
     */
    public function init(): bool
    {
        // New adminurl instance
        // May be moved to property of dcCore::app()->admin in a near future
        dcCore::app()->adminurl = new dcAdminURL();
        dcCore::app()->adminurl->register('admin.auth', 'index.php', ['process' => 'Auth']);

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
                    Http::redirect(dcCore::app()->adminurl->get('admin.auth', $params));
                }
            } catch (Exception $e) {
                new Fault(__('Database error'), __('There seems to be no Session table in your database. Is Dotclear completly installed?'), Fault::DATABASE_ISSUE);
            }

            # Check nonce from POST requests
            if (!empty($_POST)) {
                if (empty($_POST['xd_check']) || !dcCore::app()->checkNonce($_POST['xd_check'])) {
                    Http::head(412);
                    header('Content-Type: text/plain');
                    echo 'Precondition Failed';
                    exit;
                }
            }

            if (!empty($_REQUEST['switchblog']) && dcCore::app()->auth->getPermissions($_REQUEST['switchblog']) !== false) {
                $_SESSION['sess_blog_id'] = $_REQUEST['switchblog'];

                if (!empty($_REQUEST['redir'])) {
                    # Keep context as far as possible
                    $redir = (string) $_REQUEST['redir'];
                } else {
                    # Removing switchblog from URL
                    $redir = (string) $_SERVER['REQUEST_URI'];
                    $redir = preg_replace('/switchblog=(.*?)(&|$)/', '', $redir);
                    $redir = preg_replace('/\?$/', '', $redir);
                }

                dcCore::app()->auth->user_prefs->interface->drop('media_manager_dir');

                if (strstr($redir, 'media.php') !== false) {
                    // Remove current media dir from media manager URL
                    $redir = preg_replace('/d=(.*?)(&|$)/', '', $redir);
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
            dcAdminHelper::loadLocales();
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
                Http::redirect(dcCore::app()->adminurl->get('admin.auth'));
            }
        }

        dcCore::app()->adminurl->register('admin.posts', 'posts.php');
        dcCore::app()->adminurl->register('admin.popup_posts', 'popup_posts.php');
        dcCore::app()->adminurl->register('admin.post', 'post.php');
        dcCore::app()->adminurl->register('admin.post.media', 'post_media.php');
        dcCore::app()->adminurl->register('admin.blog.theme', 'blog_theme.php');
        dcCore::app()->adminurl->register('admin.blog.pref', 'blog_pref.php');
        dcCore::app()->adminurl->register('admin.blog.del', 'blog_del.php');
        dcCore::app()->adminurl->register('admin.blog', 'blog.php');
        dcCore::app()->adminurl->register('admin.blogs', 'blogs.php');
        dcCore::app()->adminurl->register('admin.categories', 'categories.php');
        dcCore::app()->adminurl->register('admin.category', 'category.php');
        dcCore::app()->adminurl->register('admin.comments', 'comments.php');
        dcCore::app()->adminurl->register('admin.comment', 'comment.php');
        dcCore::app()->adminurl->register('admin.help', 'help.php');
        dcCore::app()->adminurl->register('admin.home', 'index.php', ['process' => 'Index']);
        dcCore::app()->adminurl->register('admin.langs', 'langs.php');
        dcCore::app()->adminurl->register('admin.media', 'media.php');
        dcCore::app()->adminurl->register('admin.media.item', 'media_item.php');
        dcCore::app()->adminurl->register('admin.plugins', 'plugins.php');
        dcCore::app()->adminurl->register('admin.plugin', 'plugin.php');
        dcCore::app()->adminurl->register('admin.search', 'index.php', ['process' => 'Search']);
        dcCore::app()->adminurl->register('admin.user.preferences', 'preferences.php');
        dcCore::app()->adminurl->register('admin.user', 'user.php');
        dcCore::app()->adminurl->register('admin.user.actions', 'users_actions.php');
        dcCore::app()->adminurl->register('admin.users', 'users.php');
        dcCore::app()->adminurl->register('admin.help', 'help.php');
        dcCore::app()->adminurl->register('admin.update', 'update.php');
        dcCore::app()->adminurl->register('admin.csp_report', 'csp_report.php');

        dcCore::app()->adminurl->registercopy('load.plugin.file', 'admin.home', ['pf' => 'dummy.css']);
        dcCore::app()->adminurl->registercopy('load.var.file', 'admin.home', ['vf' => 'dummy.json']);

        dcCore::app()->setPostType('post', urldecode(dcCore::app()->adminurl->get('admin.post', ['id' => '%d'], '&')), dcCore::app()->url->getURLFor('post', '%s'), 'Posts');

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
            dcCore::app()->favs    = new dcFavorites();
            # [] : Title, URL, small icon, large icon, permissions, id, class
            # NB : '*' in permissions means any, null means super admin only

            # Menus creation
            dcCore::app()->menu = new ArrayObject();

            /*
             * @var        ArrayObject
             *
             * @deprecated Since 2.23, use dcCore::app()->menu instead
             */
            $GLOBALS['_menu'] = dcCore::app()->menu;

            if (!$user_ui_nofavmenu) {
                dcCore::app()->favs->appendMenuTitle(dcCore::app()->menu);
            }
            dcCore::app()->menu[dcAdmin::MENU_BLOG]    = new dcMenu('blog-menu', 'Blog');
            dcCore::app()->menu[dcAdmin::MENU_SYSTEM]  = new dcMenu('system-menu', 'System');
            dcCore::app()->menu[dcAdmin::MENU_PLUGINS] = new dcMenu('plugins-menu', 'Plugins');

            # Loading plugins
            dcCore::app()->plugins->loadModules(DC_PLUGINS_ROOT, 'admin', dcCore::app()->lang);
            dcCore::app()->favs->setup();

            if (!$user_ui_nofavmenu) {
                dcCore::app()->favs->appendMenu(dcCore::app()->menu);
            }

            # Set menu titles

            dcCore::app()->menu[dcAdmin::MENU_SYSTEM]->title  = __('System settings');   // @phpstan-ignore-line
            dcCore::app()->menu[dcAdmin::MENU_BLOG]->title    = __('Blog');              // @phpstan-ignore-line
            dcCore::app()->menu[dcAdmin::MENU_PLUGINS]->title = __('Plugins');           // @phpstan-ignore-line

            dcAdminHelper::addMenuItem(
                dcAdmin::MENU_BLOG,
                __('Blog appearance'),
                'admin.blog.theme',
                ['images/menu/themes.svg', 'images/menu/themes-dark.svg'],
                dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                    dcAuth::PERMISSION_ADMIN,
                ]), dcCore::app()->blog->id)
            );
            dcAdminHelper::addMenuItem(
                dcAdmin::MENU_BLOG,
                __('Blog settings'),
                'admin.blog.pref',
                ['images/menu/blog-pref.svg', 'images/menu/blog-pref-dark.svg'],
                dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                    dcAuth::PERMISSION_ADMIN,
                ]), dcCore::app()->blog->id)
            );
            dcAdminHelper::addMenuItem(
                dcAdmin::MENU_BLOG,
                __('Media manager'),
                'admin.media',
                ['images/menu/media.svg', 'images/menu/media-dark.svg'],
                dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                    dcAuth::PERMISSION_MEDIA,
                    dcAuth::PERMISSION_MEDIA_ADMIN,
                ]), dcCore::app()->blog->id)
            );
            dcAdminHelper::addMenuItem(
                dcAdmin::MENU_BLOG,
                __('Categories'),
                'admin.categories',
                ['images/menu/categories.svg', 'images/menu/categories-dark.svg'],
                dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                    dcAuth::PERMISSION_CATEGORIES,
                ]), dcCore::app()->blog->id)
            );
            dcAdminHelper::addMenuItem(
                dcAdmin::MENU_BLOG,
                __('Search'),
                'admin.search',
                ['images/menu/search.svg','images/menu/search-dark.svg'],
                dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                    dcAuth::PERMISSION_USAGE,
                    dcAuth::PERMISSION_CONTENT_ADMIN,
                ]), dcCore::app()->blog->id)
            );
            dcAdminHelper::addMenuItem(
                dcAdmin::MENU_BLOG,
                __('Comments'),
                'admin.comments',
                ['images/menu/comments.svg', 'images/menu/comments-dark.svg'],
                dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                    dcAuth::PERMISSION_USAGE,
                    dcAuth::PERMISSION_CONTENT_ADMIN,
                ]), dcCore::app()->blog->id)
            );
            dcAdminHelper::addMenuItem(
                dcAdmin::MENU_BLOG,
                __('Posts'),
                'admin.posts',
                ['images/menu/entries.svg', 'images/menu/entries-dark.svg'],
                dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                    dcAuth::PERMISSION_USAGE,
                    dcAuth::PERMISSION_CONTENT_ADMIN,
                ]), dcCore::app()->blog->id)
            );
            dcAdminHelper::addMenuItem(
                dcAdmin::MENU_BLOG,
                __('New post'),
                'admin.post',
                ['images/menu/edit.svg', 'images/menu/edit-dark.svg'],
                dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                    dcAuth::PERMISSION_USAGE,
                    dcAuth::PERMISSION_CONTENT_ADMIN,
                ]), dcCore::app()->blog->id),
                true,
                true
            );

            dcAdminHelper::addMenuItem(
                dcAdmin::MENU_SYSTEM,
                __('My preferences'),
                'admin.user.preferences',
                ['images/menu/user-pref.svg', 'images/menu/user-pref.svg'],
                true
            );
            dcAdminHelper::addMenuItem(
                dcAdmin::MENU_SYSTEM,
                __('Update'),
                'admin.update',
                ['images/menu/update.svg', 'images/menu/update-dark.svg'],
                dcCore::app()->auth->isSuperAdmin() && is_readable(DC_DIGESTS)
            );
            dcAdminHelper::addMenuItem(
                dcAdmin::MENU_SYSTEM,
                __('Languages'),
                'admin.langs',
                ['images/menu/langs.svg', 'images/menu/langs-dark.svg'],
                dcCore::app()->auth->isSuperAdmin()
            );
            dcAdminHelper::addMenuItem(
                dcAdmin::MENU_SYSTEM,
                __('Plugins management'),
                'admin.plugins',
                ['images/menu/plugins.svg', 'images/menu/plugins-dark.svg'],
                dcCore::app()->auth->isSuperAdmin()
            );
            dcAdminHelper::addMenuItem(
                dcAdmin::MENU_SYSTEM,
                __('Users'),
                'admin.users',
                'images/menu/users.svg',
                dcCore::app()->auth->isSuperAdmin()
            );
            dcAdminHelper::addMenuItem(
                dcAdmin::MENU_SYSTEM,
                __('Blogs'),
                'admin.blogs',
                ['images/menu/blogs.svg', 'images/menu/blogs-dark.svg'],
                dcCore::app()->auth->isSuperAdmin() || dcCore::app()->auth->check(dcCore::app()->auth->makePermissions([
                    dcAuth::PERMISSION_USAGE,
                    dcAuth::PERMISSION_CONTENT_ADMIN,
                ]), dcCore::app()->blog->id) && dcCore::app()->auth->getBlogCount() > 1
            );

            if (empty(dcCore::app()->blog->settings->system->jquery_migrate_mute)) {
                dcCore::app()->blog->settings->system->put('jquery_migrate_mute', true, 'boolean', 'Mute warnings for jquery migrate plugin ?', false);
            }
            if (empty(dcCore::app()->blog->settings->system->jquery_allow_old_version)) {
                dcCore::app()->blog->settings->system->put('jquery_allow_old_version', false, 'boolean', 'Allow older version of jQuery', false, true);
            }

            # Admin behaviors
            dcCore::app()->addBehavior('adminPopupPosts', [dcAdminBlogPref::class, 'adminPopupPosts']);
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
