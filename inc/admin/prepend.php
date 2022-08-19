<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
define('DC_CONTEXT_ADMIN', true);
define('DC_ADMIN_CONTEXT', true); // For dyslexic devs ;-)

require_once __DIR__ . '/../prepend.php';

// HTTP/1.1
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');

// HTTP/1.0
header('Pragma: no-cache');

if (dcCore::app()->auth->sessionExists()) {
    # If we have a session we launch it now
    try {
        if (!dcCore::app()->auth->checkSession()) {
            # Avoid loop caused by old cookie
            $p    = dcCore::app()->session->getCookieParameters(false, -600);
            $p[3] = '/';
            setcookie(...$p);

            http::redirect('auth.php');
        }
    } catch (Exception $e) {
        __error(__('Database error'), __('There seems to be no Session table in your database. Is Dotclear completly installed?'), 20);
    }

    # Check nonce from POST requests
    if (!empty($_POST)) {
        if (empty($_POST['xd_check']) || !dcCore::app()->checkNonce($_POST['xd_check'])) {
            http::head(412);
            header('Content-Type: text/plain');
            echo 'Precondition Failed';
            exit;
        }
    }

    if (!empty($_REQUEST['switchblog'])
        && dcCore::app()->auth->getPermissions($_REQUEST['switchblog']) !== false) {
        $_SESSION['sess_blog_id'] = $_REQUEST['switchblog'];
        if (isset($_SESSION['media_manager_dir'])) {
            unset($_SESSION['media_manager_dir']);
        }

        if (!empty($_REQUEST['redir'])) {
            # Keep context as far as possible
            $redir = $_REQUEST['redir'];
        } else {
            # Removing switchblog from URL
            $redir = $_SERVER['REQUEST_URI'];
            $redir = preg_replace('/switchblog=(.*?)(&|$)/', '', $redir);
            $redir = preg_replace('/\?$/', '', $redir);
        }
        http::redirect($redir);
        exit;
    }

    # Check blog to use and log out if no result
    if (isset($_SESSION['sess_blog_id'])) {
        if (dcCore::app()->auth->getPermissions($_SESSION['sess_blog_id']) === false) {
            unset($_SESSION['sess_blog_id']);
        }
    } else {
        if (($b = dcCore::app()->auth->findUserBlog(dcCore::app()->auth->getInfo('user_default_blog'))) !== false) {
            $_SESSION['sess_blog_id'] = $b;
            unset($b);
        }
    }

    # Loading locales
    dcAdminHelper::loadLocales(dcCore::app()->lang);
    /**
     * @var        string
     *
     * @deprecated Since 2.23, use dcCore::app()->lang instead
     */
    $_lang = &dcCore::app()->lang;

    if (isset($_SESSION['sess_blog_id'])) {
        dcCore::app()->setBlog($_SESSION['sess_blog_id']);
    } else {
        dcCore::app()->session->destroy();
        http::redirect('auth.php');
    }
}

dcCore::app()->admin = new dcAdmin();

dcCore::app()->adminurl = new dcAdminURL(dcCore::app());

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
dcCore::app()->adminurl->register('admin.home', 'index.php');
dcCore::app()->adminurl->register('admin.langs', 'langs.php');
dcCore::app()->adminurl->register('admin.media', 'media.php');
dcCore::app()->adminurl->register('admin.media.item', 'media_item.php');
dcCore::app()->adminurl->register('admin.plugins', 'plugins.php');
dcCore::app()->adminurl->register('admin.plugin', 'plugin.php');
dcCore::app()->adminurl->register('admin.search', 'search.php');
dcCore::app()->adminurl->register('admin.user.preferences', 'preferences.php');
dcCore::app()->adminurl->register('admin.user', 'user.php');
dcCore::app()->adminurl->register('admin.user.actions', 'users_actions.php');
dcCore::app()->adminurl->register('admin.users', 'users.php');
dcCore::app()->adminurl->register('admin.auth', 'auth.php');
dcCore::app()->adminurl->register('admin.help', 'help.php');
dcCore::app()->adminurl->register('admin.update', 'update.php');

dcCore::app()->adminurl->registercopy('load.plugin.file', 'admin.home', ['pf' => 'dummy.css']);
dcCore::app()->adminurl->registercopy('load.var.file', 'admin.home', ['vf' => 'dummy.json']);

if (dcCore::app()->auth->userID() && dcCore::app()->blog !== null) {
    # Loading resources and help files
    $locales_root = __DIR__ . '/../../locales/';
    require $locales_root . '/en/resources.php';
    if (($f = l10n::getFilePath($locales_root, 'resources.php', dcCore::app()->lang))) {
        require $f;
    }
    unset($f);

    if (($hfiles = @scandir($locales_root . dcCore::app()->lang . '/help')) !== false) {
        foreach ($hfiles as $hfile) {
            if (preg_match('/^(.*)\.html$/', $hfile, $m)) {
                dcCore::app()->resources['help'][$m[1]] = $locales_root . dcCore::app()->lang . '/help/' . $hfile;
            }
        }
    }
    unset($hfiles, $locales_root);
    // Contextual help flag
    dcCore::app()->resources['ctxhelp'] = false;

    dcCore::app()->auth->user_prefs->addWorkspace('interface');
    $user_ui_nofavmenu = dcCore::app()->auth->user_prefs->interface->nofavmenu;

    dcCore::app()->notices = new dcNotices(dcCore::app());
    dcCore::app()->favs    = new dcFavorites(dcCore::app());
    # [] : Title, URL, small icon, large icon, permissions, id, class
    # NB : '*' in permissions means any, null means super admin only

    # Menus creation
    dcCore::app()->menu = new ArrayObject();

    /**
     * @var        ArrayObject
     *
     * @deprecated Since 2.23, use dcCore::app()->menu instead
     */
    $_menu = dcCore::app()->menu;

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
        dcCore::app()->auth->check('admin', dcCore::app()->blog->id)
    );
    dcAdminHelper::addMenuItem(
        dcAdmin::MENU_BLOG,
        __('Blog settings'),
        'admin.blog.pref',
        ['images/menu/blog-pref.svg', 'images/menu/blog-pref-dark.svg'],
        dcCore::app()->auth->check('admin', dcCore::app()->blog->id)
    );
    dcAdminHelper::addMenuItem(
        dcAdmin::MENU_BLOG,
        __('Media manager'),
        'admin.media',
        ['images/menu/media.svg', 'images/menu/media-dark.svg'],
        dcCore::app()->auth->check('media,media_admin', dcCore::app()->blog->id)
    );
    dcAdminHelper::addMenuItem(
        dcAdmin::MENU_BLOG,
        __('Categories'),
        'admin.categories',
        ['images/menu/categories.svg', 'images/menu/categories-dark.svg'],
        dcCore::app()->auth->check('categories', dcCore::app()->blog->id)
    );
    dcAdminHelper::addMenuItem(
        dcAdmin::MENU_BLOG,
        __('Search'),
        'admin.search',
        ['images/menu/search.svg','images/menu/search-dark.svg'],
        dcCore::app()->auth->check('usage,contentadmin', dcCore::app()->blog->id)
    );
    dcAdminHelper::addMenuItem(
        dcAdmin::MENU_BLOG,
        __('Comments'),
        'admin.comments',
        ['images/menu/comments.svg', 'images/menu/comments-dark.svg'],
        dcCore::app()->auth->check('usage,contentadmin', dcCore::app()->blog->id)
    );
    dcAdminHelper::addMenuItem(
        dcAdmin::MENU_BLOG,
        __('Posts'),
        'admin.posts',
        ['images/menu/entries.svg', 'images/menu/entries-dark.svg'],
        dcCore::app()->auth->check('usage,contentadmin', dcCore::app()->blog->id)
    );
    dcAdminHelper::addMenuItem(
        dcAdmin::MENU_BLOG,
        __('New post'),
        'admin.post',
        ['images/menu/edit.svg', 'images/menu/edit-dark.svg'],
        dcCore::app()->auth->check('usage,contentadmin', dcCore::app()->blog->id),
        true,
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
        dcCore::app()->auth->isSuperAdmin() || dcCore::app()->auth->check('usage,contentadmin', dcCore::app()->blog->id) && dcCore::app()->auth->getBlogCount() > 1
    );

    if (empty(dcCore::app()->blog->settings->system->jquery_migrate_mute)) {
        dcCore::app()->blog->settings->system->put('jquery_migrate_mute', true, 'boolean', 'Mute warnings for jquery migrate plugin ?', false);
    }
    if (empty(dcCore::app()->blog->settings->system->jquery_allow_old_version)) {
        dcCore::app()->blog->settings->system->put('jquery_allow_old_version', false, 'boolean', 'Allow older version of jQuery', false, true);
    }

    # Ensure theme's settings namespace exists
    dcCore::app()->blog->settings->addNamespace('themes');

    # Admin behaviors
    dcCore::app()->addBehavior('adminPopupPosts', ['dcAdminBlogPref', 'adminPopupPosts']);
}
