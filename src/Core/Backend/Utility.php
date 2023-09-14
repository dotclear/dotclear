<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core\Backend;

use dcCore;
use Dotclear\App;
use Dotclear\Core\PostType;
use Dotclear\Core\Process;
use Dotclear\Fault;
use Dotclear\Helper\L10n;
use Dotclear\Helper\Network\Http;
use Dotclear\Helper\TraitDynamicProperties;
use Exception;

/**
 * Utility class for admin context.
 */
class Utility extends Process
{
    /** Allow dynamic properties */
    use TraitDynamicProperties;

    /** @var    string  Current admin page URL */
    private string $p_url = '';

    /** @var    Url     Backend (admin) Url handler instance */
    public Url $url;

    /** @var    Favorites   Backend (admin) Favorites handler instance */
    public Favorites $favs;

    /** @var    Menus   Backend (admin) Menus handler instance */
    public Menus $menus;

    /** @var    Resources   Backend help resources instance */
    public Resources $resources;

    /** @deprecated since 2.27, use Menus::MENU_FAVORITES */
    public const MENU_FAVORITES = Menus::MENU_FAVORITES;

    /** @deprecated since 2.27, use Menus::MENU_BLOG */
    public const MENU_BLOG = Menus::MENU_BLOG;

    /** @deprecated since 2.27, use Menus::MENU_SYSTEM */
    public const MENU_SYSTEM = Menus::MENU_SYSTEM;

    /** @deprecated since 2.27, use Menus::MENU_PLUGINS */
    public const MENU_PLUGINS = Menus::MENU_PLUGINS;

    /**
     * Constructs a new instance.
     *
     * @throws     Exception  (if not admin context)
     */
    public function __construct()
    {
        if (!App::context('BACKEND')) {
            throw new Exception('Application is not in administrative context.', 500);
        }

        // HTTP/1.1
        header('Expires: Mon, 13 Aug 2003 07:48:00 GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
    }

    /**
     * Initialize application utility.
     */
    public static function init(): bool
    {
        return true;
    }

    /**
     * Process application utility and set up a singleton instance.
     */
    public static function process(): bool
    {
        // Instanciate Backend instance
        App::backend();

        // New admin url instance
        App::backend()->url = new Url();

        // deprecated since 2.27, use App::backend()->url instead
        dcCore::app()->adminurl = App::backend()->url;

        if (App::auth()->sessionExists()) {
            // If we have a session we launch it now
            try {
                if (!App::auth()->checkSession()) {
                    // Avoid loop caused by old cookie
                    $p    = App::session()->getCookieParameters(false, -600);
                    $p[3] = '/';
                    setcookie(...$p);

                    // Preserve safe_mode if necessary
                    $params = !empty($_REQUEST['safe_mode']) ? ['safe_mode' => 1] : [];
                    App::backend()->url->redirect('admin.auth', $params);
                }
            } catch (Exception $e) {
                new Fault(__('Database error'), __('There seems to be no Session table in your database. Is Dotclear completly installed?'), Fault::DATABASE_ISSUE);
            }

            // Fake process to logout (kill session) and return to auth page.
            if (!empty($_REQUEST['process']) && $_REQUEST['process'] == 'Logout') {
                // Enable REST service if disabled, for next requests
                if (!App::rest()->serveRestRequests()) {
                    App::rest()->enableRestServer(true);
                }
                // Kill admin session
                App::backend()->killAdminSession();
                // Logout
                App::backend()->url->redirect('admin.auth');
                exit;
            }

            // Check nonce from POST requests
            if (!empty($_POST) && (empty($_POST['xd_check']) || !App::nonce()->checkNonce($_POST['xd_check']))) {
                new Fault('Precondition Failed', __('Precondition Failed'), 412);
            }

            // Switch blog
            if (!empty($_REQUEST['switchblog']) && App::auth()->getPermissions($_REQUEST['switchblog']) !== false) {
                $_SESSION['sess_blog_id'] = $_REQUEST['switchblog'];

                if (!empty($_REQUEST['redir'])) {
                    // Keep context as far as possible
                    $redir = (string) $_REQUEST['redir'];
                } else {
                    // Removing switchblog from URL
                    $redir = (string) $_SERVER['REQUEST_URI'];
                    $redir = (string) preg_replace('/switchblog=(.*?)(&|$)/', '', $redir);
                    $redir = (string) preg_replace('/\?$/', '', $redir);
                }

                App::auth()->prefs()->interface->drop('media_manager_dir');

                if (!empty($_REQUEST['process']) && $_REQUEST['process'] == 'Media' || strstr($redir, 'media.php') !== false) {
                    // Remove current media dir from media manager URL
                    $redir = (string) preg_replace('/d=(.*?)(&|$)/', '', $redir);
                }

                Http::redirect($redir);
                exit;
            }

            // Check blog to use and log out if no result
            if (isset($_SESSION['sess_blog_id'])) {
                if (App::auth()->getPermissions($_SESSION['sess_blog_id']) === false) {
                    unset($_SESSION['sess_blog_id']);
                }
            } else {
                if (($b = App::auth()->findUserBlog(App::auth()->getInfo('user_default_blog'), false)) !== false) {
                    $_SESSION['sess_blog_id'] = $b;
                    unset($b);
                }
            }

            // Load locales
            Helper::loadLocales();

            // deprecated since 2.27, use App::lang() instead
            $GLOBALS['_lang'] = App::lang();

            // Load blog
            if (isset($_SESSION['sess_blog_id'])) {
                App::blogLoader()->setBlog($_SESSION['sess_blog_id']);
            } else {
                App::session()->destroy();
                App::backend()->url->redirect('admin.auth');
            }
        }

        // Set default backend URLs
        App::backend()->url->setDefaultURLs();

        // (re)set post type with real backend URL (as admin URL handler is known yet)
        App::postTypes()->set(new PostType('post', urldecode(App::backend()->url->get('admin.post', ['id' => '%d'], '&')), App::url()->getURLFor('post', '%s'), 'Posts'));

        // No user nor blog, do not load more stuff
        if (!(App::auth()->userID() && App::blog()->isDefined())) {
            return true;
        }

        // Load resources and help files
        App::backend()->resources = new Resources();

        require implode(DIRECTORY_SEPARATOR, [App::config()->l10nRoot(), 'en', 'resources.php']);
        if ($f = L10n::getFilePath(App::config()->l10nRoot(), '/resources.php', App::lang())) {
            require $f;
        }
        unset($f);

        if (($hfiles = @scandir(implode(DIRECTORY_SEPARATOR, [App::config()->l10nRoot(), App::lang(), 'help']))) !== false) {
            foreach ($hfiles as $hfile) {
                if (preg_match('/^(.*)\.html$/', $hfile, $m)) {
                    App::backend()->resources->set('help', $m[1], implode(DIRECTORY_SEPARATOR, [App::config()->l10nRoot(), App::lang(), 'help', $hfile]));
                }
            }
        }
        unset($hfiles);
        // Contextual help flag
        App::backend()->resources->context(false);

        $user_ui_nofavmenu = App::auth()->prefs()->interface->nofavmenu;

        App::backend()->favs  = new Favorites();
        App::backend()->menus = new Menus();

        // deprecated since 2.27, use App::backend()->favs instead
        dcCore::app()->favs = App::backend()->favs;

        // deprecated since 2.27, use App::backend()->menus instead
        dcCore::app()->menu = App::backend()->menus;

        // deprecated Since 2.23, use App::backend()->menus instead
        $GLOBALS['_menu'] = App::backend()->menus;

        // Set default menu
        App::backend()->menus->setDefaultItems();

        if (!$user_ui_nofavmenu) {
            App::backend()->favs->appendMenuSection(App::backend()->menus);
        }

        // deprecated since 2.28, use App::media() instead
        dcCore::app()->media = App::media();

        // Load plugins
        App::plugins()->loadModules(App::config()->pluginsRoot(), 'admin', App::lang());
        App::backend()->favs->setup();

        if (!$user_ui_nofavmenu) {
            App::backend()->favs->appendMenu(App::backend()->menus);
        }

        if (empty(App::blog()->settings()->system->jquery_migrate_mute)) {
            App::blog()->settings()->system->put('jquery_migrate_mute', true, 'boolean', 'Mute warnings for jquery migrate plugin ?', false);
        }
        if (empty(App::blog()->settings()->system->jquery_allow_old_version)) {
            App::blog()->settings()->system->put('jquery_allow_old_version', false, 'boolean', 'Allow older version of jQuery', false, true);
        }

        // Load themes
        if (App::themes()->isEmpty()) {
            App::themes()->loadModules(App::blog()->themesPath(), 'admin', App::lang());

            // deprecated Since 2.28, use App::themes()->menus instead
            dcCore::app()->themes = App::themes();
        }

        // Admin behaviors
        App::behavior()->addBehavior('adminPopupPosts', BlogPref::adminPopupPosts(...));

        return true;
    }

    /**
     * Set the admin page URL.
     *
     * @param   string  $url  The URL
     */
    public function setPageURL(string $url): void
    {
        $this->p_url = $url;

        // deprecated since 2.24, use App::backend()->setPageURL() and App::backend()->getPageURL() instaed
        $GLOBALS['p_url'] = $url;
    }

    /**
     * Get the admin page URL.
     *
     * @return  string  The URL
     */
    public function getPageURL(): string
    {
        return $this->p_url;
    }

    /**
     * Kill admin session helper
     */
    public function killAdminSession(): void
    {
        // Kill session
        App::session()->destroy();

        // Unset cookie if necessary
        if (isset($_COOKIE['dc_admin'])) {
            unset($_COOKIE['dc_admin']);
            setcookie('dc_admin', '', -600, '', '', App::config()->adminSsl());
        }
    }
}
