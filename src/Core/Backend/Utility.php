<?php

/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

/**
 * @namespace   Dotclear.Core.Backend
 * @brief       Dotclear application backend utilities.
 */

namespace Dotclear\Core\Backend;

use dcCore;
use Dotclear\App;
use Dotclear\Core\PostType;
use Dotclear\Core\Process;
use Dotclear\Helper\L10n;
use Dotclear\Helper\Network\Http;
use Dotclear\Helper\TraitDynamicProperties;
use Dotclear\Exception\ContextException;
use Dotclear\Exception\PreconditionException;
use Dotclear\Exception\SessionException;
use Throwable;

/**
 * Utility class for admin context.
 */
class Utility extends Process
{
    /** Allow dynamic properties */
    use TraitDynamicProperties;

    /**
     * Current admin page URL.
     *
     * @var     string  $p_url
     */
    private string $p_url = '';

    /**
     * Backend (admin) Url handler instance.
     *
     * @var     Url     $url
     */
    private Url $url;

    /**
     * Backend (admin) Favorites handler instance.
     *
     *  @var    Favorites   $favorites
     */
    private Favorites $favorites;

    /**
     * Backend (admin) Menus handler instance.
     *
     * @var     Menus   $menus
     */
    private Menus $menus;

    /**
     * Backend help resources instance.
     *
     * @var     Resources   $resources
     */
    private Resources $resources;

    /**
     * Backend login cookie name.
     *
     * @var     string  COOKIE_NAME
     */
    public const COOKIE_NAME = 'dc_admin';

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
     * @throws     ContextException  (if not admin context)
     */
    public function __construct()
    {
        if (!App::task()->checkContext('BACKEND')) {
            throw new ContextException('Application is not in administrative context.');
        }

        // deprecated since 2.28, use App::backend() instead
        dcCore::app()->admin = $this;

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
     *
     * @throws     SessionException|PreconditionException
     */
    public static function process(): bool
    {
        // Instanciate Backend instance
        App::backend();

        // deprecated since 2.28, need to load dcCore::app()->adminurl
        App::backend()->url();

        if (App::auth()->sessionExists()) {
            // If we have a session we launch it now
            try {
                if (!App::auth()->checkSession()) {
                    // Avoid loop caused by old cookie
                    $p    = App::session()->getCookieParameters(false, -600);
                    $p[3] = '/';
                    setcookie(...$p);   // @phpstan-ignore-line

                    // Preserve safe_mode if necessary
                    $params = !empty($_REQUEST['safe_mode']) ? ['safe_mode' => 1] : [];
                    App::backend()->url()->redirect('admin.auth', $params);
                }
            } catch (Throwable) {
                throw new SessionException(__('There seems to be no Session table in your database. Is Dotclear completly installed?'));
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
                App::backend()->url()->redirect('admin.auth');
                exit;
            }

            // Check nonce from POST requests
            if (!empty($_POST) && (empty($_POST['xd_check']) || !App::nonce()->checkNonce($_POST['xd_check']))) {
                throw new PreconditionException();
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

                if (!empty($_REQUEST['process']) && $_REQUEST['process'] == 'Media' || str_contains($redir, 'media.php')) {
                    // Remove current media dir from media manager URL
                    $redir = (string) preg_replace('/d=(.*?)(&|$)/', '', $redir);
                }

                // Remove requested blog from URL if any
                $redir = (string) preg_replace('/(\?|&)blog=(?:[^&.]*)(&|$)/', '', $redir);

                Http::redirect($redir);
                exit;
            }

            // Check if requested blog is in URL query (blog=blog_id)
            if ($url = parse_url((string) $_SERVER['REQUEST_URI'])) {
                if (isset($url['query'])) {
                    $params = [];
                    parse_str($url['query'], $params);
                    if (isset($params['blog'])) {
                        $_SESSION['sess_blog_id'] = $params['blog'];
                    }
                }
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

            // deprecated since 2.27, use App::lang()->getLang() instead
            $GLOBALS['_lang'] = App::lang()->getLang();

            // Load blog
            if (isset($_SESSION['sess_blog_id'])) {
                App::blog()->loadFromBlog($_SESSION['sess_blog_id']);
            } else {
                App::session()->destroy();
                App::backend()->url()->redirect('admin.auth');
            }
        }

        // Set default backend URLs
        App::backend()->url()->setDefaultURLs();

        // (re)set post type with real backend URL (as admin URL handler is known yet)
        App::postTypes()->set(new PostType('post', urldecode(App::backend()->url()->get('admin.post', ['id' => '%d'], '&')), App::url()->getURLFor('post', '%s'), 'Posts'));

        // No user nor blog, do not load more stuff
        if (!(App::auth()->userID() && App::blog()->isDefined())) {
            return true;
        }

        require implode(DIRECTORY_SEPARATOR, [App::config()->l10nRoot(), 'en', 'resources.php']);
        if ($f = L10n::getFilePath(App::config()->l10nRoot(), '/resources.php', App::lang()->getLang())) {
            require $f;
        }
        unset($f);

        if (($hfiles = @scandir(implode(DIRECTORY_SEPARATOR, [App::config()->l10nRoot(), App::lang()->getLang(), 'help']))) !== false) {
            foreach ($hfiles as $hfile) {
                if (preg_match('/^(.*)\.html$/', $hfile, $m)) {
                    App::backend()->resources()->set('help', $m[1], implode(DIRECTORY_SEPARATOR, [App::config()->l10nRoot(), App::lang()->getLang(), 'help', $hfile]));
                }
            }
        }
        unset($hfiles);
        // Contextual help flag
        App::backend()->resources()->context(false);

        $user_ui_nofavmenu = App::auth()->prefs()->interface->nofavmenu;

        // deprecated since 2.28, need to load dcCore::app()->favs
        App::backend()->favorites();

        // Set default menu
        App::backend()->menus()->setDefaultItems();

        if (!$user_ui_nofavmenu) {
            App::backend()->favorites()->appendMenuSection(App::backend()->menus());
        }

        // deprecated since 2.28, need to load dcCore::app()->media
        App::media();

        // Load plugins
        App::plugins()->loadModules(App::config()->pluginsRoot(), 'admin', App::lang()->getLang());
        App::backend()->favorites()->setup();

        if (!$user_ui_nofavmenu) {
            App::backend()->favorites()->appendMenu(App::backend()->menus());
        }

        if (empty(App::blog()->settings()->system->jquery_migrate_mute)) {
            App::blog()->settings()->system->put('jquery_migrate_mute', true, 'boolean', 'Mute warnings for jquery migrate plugin ?', false);
        }
        if (empty(App::blog()->settings()->system->jquery_allow_old_version)) {
            App::blog()->settings()->system->put('jquery_allow_old_version', false, 'boolean', 'Allow older version of jQuery', false, true);
        }

        // Load themes
        if (App::themes()->isEmpty()) {
            App::themes()->loadModules(App::blog()->themesPath(), 'admin', App::lang()->getLang());

            // deprecated Since 2.28, use App::themes() instead
            dcCore::app()->themes = App::themes();
        }

        // Admin behaviors
        App::behavior()->addBehavior('adminPopupPosts', BlogPref::adminPopupPosts(...));

        return true;
    }

    /**
     * Get backend Url instance.
     *
     * @return  Url     The backend URL handler
     */
    public function url(): Url
    {
        if (!isset($this->url)) {
            $this->url = new Url();

            // deprecated since 2.27, use App::backend()->url() instead
            dcCore::app()->adminurl = $this->url;
        }

        return $this->url;
    }

    /**
     * Get backend favorites instance.
     *
     * @return  Favorites   The favorites
     */
    public function favorites(): Favorites
    {
        if (!isset($this->favorites)) {
            $this->favorites = new Favorites();

            // deprecated since 2.27, use App::backend()->favorites() instead
            dcCore::app()->favs = $this->favorites;
        }

        return $this->favorites;
    }

    /**
     * Get backend menus instance.
     *
     * @return  Menus   The menu
     */
    public function menus(): Menus
    {
        if (!isset($this->menus)) {
            $this->menus = new Menus();

            // deprecated since 2.27, use App::backend()->menus() instead
            dcCore::app()->menu = $this->menus;

            // deprecated Since 2.23, use App::backend()->menus() instead
            $GLOBALS['_menu'] = $this->menus;
        }

        return $this->menus;
    }

    /**
     * Find a menuitem corresponding with a term (or including the term)
     *
     * @param      string             $term  The term
     *
     * @return     false|string
     */
    public function searchMenuitem(string $term): bool|string
    {
        // Try to find exact term
        foreach ($this->menus as $menu) {
            if (($link = $menu->searchMenuitem($term, true)) !== false) {
                return $link;
            }
        }
        // Try to find a menuitem including the term
        foreach ($this->menus as $menu) {
            if (($link = $menu->searchMenuitem($term, false)) !== false) {
                return $link;
            }
        }

        return false;
    }

    /**
     * Get list of available menuitems
     *
     * @return     array<int, string>
     */
    public function listMenus(): array
    {
        $datalist = [];
        foreach ($this->menus as $menu) {
            $datalist = [
                ...$datalist,
                ...$menu->listMenus(),
            ];
        }

        return $datalist;
    }

    /**
     * Get backend resources instance.
     *
     * @return  Resources   The menu
     */
    public function resources(): Resources
    {
        if (!isset($this->resources)) {
            $this->resources = new Resources();
        }

        return $this->resources;
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
        if (isset($_COOKIE[self::COOKIE_NAME])) {
            unset($_COOKIE[self::COOKIE_NAME]);
            setcookie(self::COOKIE_NAME, '', [
                'expires' => -600,
                'path'    => '',
                'domain'  => '',
                'secure'  => App::config()->adminSsl(),
            ]);
        }
    }
}
