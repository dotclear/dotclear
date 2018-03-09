<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

define('DC_CONTEXT_ADMIN', true);

require_once dirname(__FILE__) . '/../prepend.php';

// HTTP/1.1
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');

// HTTP/1.0
header("Pragma: no-cache");

function dc_load_locales()
{
    global $_lang, $core;

    $_lang = $core->auth->getInfo('user_lang');
    $_lang = preg_match('/^[a-z]{2}(-[a-z]{2})?$/', $_lang) ? $_lang : 'en';

    l10n::lang($_lang);
    if (l10n::set(dirname(__FILE__) . '/../../locales/' . $_lang . '/date') === false && $_lang != 'en') {
        l10n::set(dirname(__FILE__) . '/../../locales/en/date');
    }
    l10n::set(dirname(__FILE__) . '/../../locales/' . $_lang . '/main');
    l10n::set(dirname(__FILE__) . '/../../locales/' . $_lang . '/public');
    l10n::set(dirname(__FILE__) . '/../../locales/' . $_lang . '/plugins');

    // Set lexical lang
    dcUtils::setlexicalLang('admin', $_lang);
}

function dc_admin_icon_url($img)
{
    global $core;

    $core->auth->user_prefs->addWorkspace('interface');
    $user_ui_iconset = @$core->auth->user_prefs->interface->iconset;
    if (($user_ui_iconset) && ($img)) {
        $icon = false;
        if ((preg_match('/^images\/menu\/(.+)$/', $img, $m)) ||
            (preg_match('/^index\.php\?pf=(.+)$/', $img, $m))) {
            if ($m[1]) {
                $icon = path::real(dirname(__FILE__) . '/../../admin/images/iconset/' . $user_ui_iconset . '/' . $m[1], false);
                if ($icon !== false) {
                    $allow_types = array('png', 'jpg', 'jpeg', 'gif');
                    if (is_file($icon) && is_readable($icon) && in_array(files::getExtension($icon), $allow_types)) {
                        return DC_ADMIN_URL . 'images/iconset/' . $user_ui_iconset . '/' . $m[1];
                    }
                }
            }
        }
    }
    return $img;
}

function addMenuItem($section, $desc, $adminurl, $icon, $perm, $pinned = false)
{
    global $core, $_menu;

    $url = $core->adminurl->get($adminurl);
    $_menu[$section]->prependItem($desc, $url, $icon,
        preg_match('/' . preg_quote($url) . '(\?.*)?$/', $_SERVER['REQUEST_URI']), $perm, null, null, $pinned);
}

if (defined('DC_AUTH_SESS_ID') && defined('DC_AUTH_SESS_UID')) {
    # We have session information in constants
    $_COOKIE[DC_SESSION_NAME] = DC_AUTH_SESS_ID;

    if (!$core->auth->checkSession(DC_AUTH_SESS_UID)) {
        throw new Exception('Invalid session data.');
    }

    # Check nonce from POST requests
    if (!empty($_POST)) {
        if (empty($_POST['xd_check']) || !$core->checkNonce($_POST['xd_check'])) {
            throw new Exception('Precondition Failed.');
        }
    }

    if (empty($_SESSION['sess_blog_id'])) {
        throw new Exception('Permission denied.');
    }

    # Loading locales
    dc_load_locales();

    $core->setBlog($_SESSION['sess_blog_id']);
    if (!$core->blog->id) {
        throw new Exception('Permission denied.');
    }
} elseif ($core->auth->sessionExists()) {
    # If we have a session we launch it now
    try {
        if (!$core->auth->checkSession()) {
            # Avoid loop caused by old cookie
            $p    = $core->session->getCookieParameters(false, -600);
            $p[3] = '/';
            call_user_func_array('setcookie', $p);

            http::redirect('auth.php');
        }
    } catch (Exception $e) {
        __error(__('Database error')
            , __('There seems to be no Session table in your database. Is Dotclear completly installed?')
            , 20);
    }

    # Check nonce from POST requests
    if (!empty($_POST)) {
        if (empty($_POST['xd_check']) || !$core->checkNonce($_POST['xd_check'])) {
            http::head(412);
            header('Content-Type: text/plain');
            echo 'Precondition Failed';
            exit;
        }
    }

    if (!empty($_REQUEST['switchblog'])
        && $core->auth->getPermissions($_REQUEST['switchblog']) !== false) {
        $_SESSION['sess_blog_id'] = $_REQUEST['switchblog'];
        if (isset($_SESSION['media_manager_dir'])) {
            unset($_SESSION['media_manager_dir']);
        }
        if (isset($_SESSION['media_manager_page'])) {
            unset($_SESSION['media_manager_page']);
        }

        # Removing switchblog from URL
        $redir = $_SERVER['REQUEST_URI'];
        $redir = preg_replace('/switchblog=(.*?)(&|$)/', '', $redir);
        $redir = preg_replace('/\?$/', '', $redir);
        http::redirect($redir);
        exit;
    }

    # Check blog to use and log out if no result
    if (isset($_SESSION['sess_blog_id'])) {
        if ($core->auth->getPermissions($_SESSION['sess_blog_id']) === false) {
            unset($_SESSION['sess_blog_id']);
        }
    } else {
        if (($b = $core->auth->findUserBlog($core->auth->getInfo('user_default_blog'))) !== false) {
            $_SESSION['sess_blog_id'] = $b;
            unset($b);
        }
    }

    # Loading locales
    dc_load_locales();

    if (isset($_SESSION['sess_blog_id'])) {
        $core->setBlog($_SESSION['sess_blog_id']);
    } else {
        $core->session->destroy();
        http::redirect('auth.php');
    }
}

$core->adminurl = new dcAdminURL($core);

$core->adminurl->register('admin.posts', 'posts.php');
$core->adminurl->register('admin.popup_posts', 'popup_posts.php');
$core->adminurl->register('admin.post', 'post.php');
$core->adminurl->register('admin.post.media', 'post_media.php');
$core->adminurl->register('admin.blog.theme', 'blog_theme.php');
$core->adminurl->register('admin.blog.pref', 'blog_pref.php');
$core->adminurl->register('admin.blog.del', 'blog_del.php');
$core->adminurl->register('admin.blog', 'blog.php');
$core->adminurl->register('admin.blogs', 'blogs.php');
$core->adminurl->register('admin.categories', 'categories.php');
$core->adminurl->register('admin.category', 'category.php');
$core->adminurl->register('admin.comments', 'comments.php');
$core->adminurl->register('admin.comment', 'comment.php');
$core->adminurl->register('admin.help', 'help.php');
$core->adminurl->register('admin.home', 'index.php');
$core->adminurl->register('admin.langs', 'langs.php');
$core->adminurl->register('admin.media', 'media.php');
$core->adminurl->register('admin.media.item', 'media_item.php');
$core->adminurl->register('admin.plugins', 'plugins.php');
$core->adminurl->register('admin.plugin', 'plugin.php');
$core->adminurl->register('admin.search', 'search.php');
$core->adminurl->register('admin.user.preferences', 'preferences.php');
$core->adminurl->register('admin.user', 'user.php');
$core->adminurl->register('admin.user.actions', 'users_actions.php');
$core->adminurl->register('admin.users', 'users.php');
$core->adminurl->register('admin.auth', 'auth.php');
$core->adminurl->register('admin.help', 'help.php');
$core->adminurl->register('admin.update', 'update.php');

$core->adminurl->registercopy('load.plugin.file', 'admin.home', array('pf' => 'dummy.css'));
$core->adminurl->registercopy('load.var.file', 'admin.home', array('vf' => 'dummy.json'));

if ($core->auth->userID() && $core->blog !== null) {
    # Loading resources and help files
    $locales_root = dirname(__FILE__) . '/../../locales/';
    require $locales_root . '/en/resources.php';
    if (($f = l10n::getFilePath($locales_root, 'resources.php', $_lang))) {
        require $f;
    }
    unset($f);

    if (($hfiles = @scandir($locales_root . $_lang . '/help')) !== false) {
        foreach ($hfiles as $hfile) {
            if (preg_match('/^(.*)\.html$/', $hfile, $m)) {
                $GLOBALS['__resources']['help'][$m[1]] = $locales_root . $_lang . '/help/' . $hfile;
            }
        }
    }
    unset($hfiles, $locales_root);
    // Contextual help flag
    $GLOBALS['__resources']['ctxhelp'] = false;

    $core->auth->user_prefs->addWorkspace('interface');
    $user_ui_nofavmenu = $core->auth->user_prefs->interface->nofavmenu;

    $core->favs = new dcFavorites($core);

    # [] : Title, URL, small icon, large icon, permissions, id, class
    # NB : '*' in permissions means any, null means super admin only

    # Menus creation
    $_menu              = new ArrayObject();
    $_menu['Dashboard'] = new dcMenu('dashboard-menu', null);
    if (!$user_ui_nofavmenu) {
        $core->favs->appendMenuTitle($_menu);
    }
    $_menu['Blog']    = new dcMenu('blog-menu', 'Blog');
    $_menu['System']  = new dcMenu('system-menu', 'System');
    $_menu['Plugins'] = new dcMenu('plugins-menu', 'Plugins');
    # Loading plugins
    $core->plugins->loadModules(DC_PLUGINS_ROOT, 'admin', $_lang);
    $core->favs->setup();

    if (!$user_ui_nofavmenu) {
        $core->favs->appendMenu($_menu);
    }

    # Set menu titles

    $_menu['System']->title  = __('System settings');
    $_menu['Blog']->title    = __('Blog');
    $_menu['Plugins']->title = __('Plugins');

    addMenuItem('Blog', __('Blog appearance'), 'admin.blog.theme', 'images/menu/themes.png',
        $core->auth->check('admin', $core->blog->id));
    addMenuItem('Blog', __('Blog settings'), 'admin.blog.pref', 'images/menu/blog-pref.png',
        $core->auth->check('admin', $core->blog->id));
    addMenuItem('Blog', __('Media manager'), 'admin.media', 'images/menu/media.png',
        $core->auth->check('media,media_admin', $core->blog->id));
    addMenuItem('Blog', __('Categories'), 'admin.categories', 'images/menu/categories.png',
        $core->auth->check('categories', $core->blog->id));
    addMenuItem('Blog', __('Search'), 'admin.search', 'images/menu/search.png',
        $core->auth->check('usage,contentadmin', $core->blog->id));
    addMenuItem('Blog', __('Comments'), 'admin.comments', 'images/menu/comments.png',
        $core->auth->check('usage,contentadmin', $core->blog->id));
    addMenuItem('Blog', __('Entries'), 'admin.posts', 'images/menu/entries.png',
        $core->auth->check('usage,contentadmin', $core->blog->id));
    addMenuItem('Blog', __('New entry'), 'admin.post', 'images/menu/edit.png',
        $core->auth->check('usage,contentadmin', $core->blog->id), true);

    addMenuItem('System', __('Update'), 'admin.update', 'images/menu/update.png',
        $core->auth->isSuperAdmin() && is_readable(DC_DIGESTS));
    addMenuItem('System', __('Languages'), 'admin.langs', 'images/menu/langs.png',
        $core->auth->isSuperAdmin());
    addMenuItem('System', __('Plugins management'), 'admin.plugins', 'images/menu/plugins.png',
        $core->auth->isSuperAdmin());
    addMenuItem('System', __('Users'), 'admin.users', 'images/menu/users.png',
        $core->auth->isSuperAdmin());
    addMenuItem('System', __('Blogs'), 'admin.blogs', 'images/menu/blogs.png',
        $core->auth->isSuperAdmin() ||
        $core->auth->check('usage,contentadmin', $core->blog->id) && $core->auth->getBlogCount() > 1);

    if (empty($core->blog->settings->system->jquery_migrate_mute)) {
        $core->blog->settings->system->put('jquery_migrate_mute', true, 'boolean', 'Mute warnings for jquery migrate plugin ?', false);
    }
}
