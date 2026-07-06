<?php

/**
 * @package         Dotclear
 * @subpackage      Backend
 *
 * @defsubpackage   Backend        Application backend services
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
use Dotclear\Helper\Network\Http;
use Dotclear\Helper\Process\AbstractUtility;
use Dotclear\Exception\ContextException;
use Dotclear\Exception\PreconditionException;
use Dotclear\Exception\SessionException;

/**
 * Utility class for admin context.
 */
class Utility extends AbstractUtility
{
    public const CONTAINER_ID = 'Backend';

    public const UTILITY_PROCESS = [
        \Dotclear\Process\Backend\Auth::class,
        \Dotclear\Process\Backend\Blog::class,
        \Dotclear\Process\Backend\BlogDel::class,
        \Dotclear\Process\Backend\BlogPref::class,
        \Dotclear\Process\Backend\Blogs::class,
        \Dotclear\Process\Backend\BlogTheme::class,
        \Dotclear\Process\Backend\Categories::class,
        \Dotclear\Process\Backend\Category::class,
        \Dotclear\Process\Backend\Comment::class,
        \Dotclear\Process\Backend\Comments::class,
        \Dotclear\Process\Backend\CspReport::class,
        \Dotclear\Process\Backend\Help::class,
        \Dotclear\Process\Backend\HelpCharte::class,
        \Dotclear\Process\Backend\Home::class,
        \Dotclear\Process\Backend\Langs::class,
        \Dotclear\Process\Backend\LinkPopup::class,
        \Dotclear\Process\Backend\Logout::class,
        \Dotclear\Process\Backend\Media::class,
        \Dotclear\Process\Backend\MediaItem::class,
        \Dotclear\Process\Backend\Plugin::class,
        \Dotclear\Process\Backend\Plugins::class,
        \Dotclear\Process\Backend\Post::class,
        \Dotclear\Process\Backend\PostMedia::class,
        \Dotclear\Process\Backend\Posts::class,
        \Dotclear\Process\Backend\PostsPopup::class,
        \Dotclear\Process\Backend\Rest::class,
        \Dotclear\Process\Backend\Search::class,
        \Dotclear\Process\Backend\Settings::class,
        \Dotclear\Process\Backend\User::class,
        \Dotclear\Process\Backend\UserPreferences::class,
        \Dotclear\Process\Backend\Users::class,
        \Dotclear\Process\Backend\UsersActions::class,
    ];

    /**
     * Current admin page URL.
     */
    private string $p_url = '';

    /**
     * Backend login cookie name.
     *
     * @var     string  COOKIE_NAME
     */
    public const COOKIE_NAME = 'dc_admin';

    /** @deprecated since 2.27, use App::backend()->menus()::MENU_FAVORITES */
    public const MENU_FAVORITES = Menus::MENU_FAVORITES;

    /** @deprecated since 2.27, use App::backend()->menus()::MENU_BLOG */
    public const MENU_BLOG = Menus::MENU_BLOG;

    /** @deprecated since 2.27, use App::backend()->menus()::MENU_SYSTEM */
    public const MENU_SYSTEM = Menus::MENU_SYSTEM;

    /** @deprecated since 2.27, use App::backend()->menus()::MENU_PLUGINS */
    public const MENU_PLUGINS = Menus::MENU_PLUGINS;

    /**
     * Constructs a new instance.
     *
     * @throws     ContextException  (if not admin context)
     */
    public function __construct()
    {
        // Load utility container
        parent::__construct();

        // deprecated since 2.28, use App::backend() instead
        if (!App::config()->modern()) {
            dcCore::app()->admin = $this;
        }

        if (App::task()->checkContext('UPGRADE') && App::session()->exists()) {
            // Opening a Backend context inside a Upgrade one, nothing more to do
        } else {
            // configure backend session
            App::session()->configure(
                cookie_name: App::config()->sessionName(),
                cookie_secure: App::config()->adminSsl(),
                ttl: App::config()->sessionTtl()
            );
        }

        // HTTP/1.1
        header('Expires: Mon, 13 Aug 2003 07:48:00 GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
    }

    public function getDefaultServices(): array
    {
        return [
            Action::class      => Action::class,
            Auth::class        => Auth::class,
            Combos::class      => Combos::class,
            Favorites::class   => Favorites::class,
            Filter::class      => Filter::class,
            Helper::class      => Helper::class,
            Listing::class     => Listing::class,
            MediaPage::class   => MediaPage::class,
            Menus::class       => Menus::class,
            ModulesList::class => ModulesList::class,
            Notices::class     => Notices::class,
            Page::class        => Page::class,
            Resources::class   => Resources::class,
            ThemeConfig::class => ThemeConfig::class,
            ThemesList::class  => ThemesList::class,
            Url::class         => Url::class,
            UserPref::class    => UserPref::class,
        ];
    }

    /**
     * Get backend list action instance.
     */
    public function action(): Action
    {
        // @phpstan-ignore return.type
        return $this->get(Action::class);
    }

    /**
     * Get backend auth helpers instance.
     */
    public function auth(): Auth
    {
        // @phpstan-ignore return.type
        return $this->get(Auth::class);
    }

    /**
     * Get backend combos instance.
     */
    public function combos(): Combos
    {
        // @phpstan-ignore return.type
        return $this->get(Combos::class);
    }

    /**
     * Get backend helper instance.
     */
    public function helper(): Helper
    {
        // @phpstan-ignore return.type
        return $this->get(Helper::class);
    }

    /**
     * Get backend list filter instance.
     */
    public function filter(): Filter
    {
        // @phpstan-ignore return.type
        return $this->get(Filter::class);
    }

    /**
     * Get backend favorites instance.
     */
    public function favorites(): Favorites
    {
        // @phpstan-ignore return.type
        return $this->get(Favorites::class);
    }

    /**
     * Get backend listing instance.
     */
    public function listing(): Listing
    {
        // @phpstan-ignore return.type
        return $this->get(Listing::class);
    }

    /**
     * Get backend media page instance.
     */
    public function mediaPage(): MediaPage
    {
        // @phpstan-ignore return.type
        return $this->get(MediaPage::class);
    }

    /**
     * Get backend menus instance.
     */
    public function menus(): Menus
    {
        // @phpstan-ignore return.type
        return $this->get(Menus::class);
    }

    /**
     * Get backend modules list instance.
     */
    public function modulesList(): ModulesList
    {
        // @phpstan-ignore return.type
        return $this->get(
            ModulesList::class, // service
            false,              // reload
            modules: App::plugins(),
            modules_root: App::config()->pluginsRoot(),
            xml_url: App::config()->storePluginUrl(),
            force: empty($_GET['nocache']) ? null : true
        );
    }

    /**
     * Get backend notices instance.
     */
    public function notices(): Notices
    {
        // @phpstan-ignore return.type
        return $this->get(Notices::class);
    }

    /**
     * Get backend page instance.
     */
    public function page(): Page
    {
        // @phpstan-ignore return.type
        return $this->get(Page::class);
    }

    /**
     * Get backend resources instance.
     */
    public function resources(): Resources
    {
        // @phpstan-ignore return.type
        return $this->get(Resources::class);
    }

    /**
     * Get backend theme config helper instance.
     */
    public function themeConfig(): ThemeConfig
    {
        // @phpstan-ignore return.type
        return $this->get(ThemeConfig::class);
    }

    /**
     * Get backend themes list instance.
     */
    public function themesList(): ThemesList
    {
        // @phpstan-ignore return.type
        return $this->get(
            ThemesList::class, // service
            false,              // reload
            modules: App::themes(),
            modules_root: App::blog()->themesPath(),
            xml_url: App::config()->storeThemeUrl(),
            force: empty($_GET['nocache']) ? null : true
        );
    }

    /**
     * Get backend Url instance.
     */
    public function url(): Url
    {
        // @phpstan-ignore return.type
        return $this->get(Url::class);
    }

    /**
     * Get backend user preferences instance.
     */
    public function userPref(): UserPref
    {
        // @phpstan-ignore return.type
        return $this->get(UserPref::class);
    }

    public static function init(): bool
    {
        return !App::config()->cliMode();
    }

    /**
     * Process application utility and set up a singleton instance.
     *
     * @throws     SessionException|PreconditionException
     */
    public static function process(): bool
    {
        // Instanciate Backend instance, to configure session since 2.36
        App::backend();

        // deprecated since 2.27, use App::backend()->url() instead
        if (!App::config()->modern()) {
            dcCore::app()->adminurl = App::backend()->url();
        }

        // Always start a session, since 2.36
        App::session()->start();

        // If we have a session we launch it now
        if (App::auth()->checkSession()) {
            $process = isset($_REQUEST['process']) && is_string($process = $_REQUEST['process']) ? $process : '';

            $user_status = App::status()->user()::DISABLED;
            if ($process !== 'Logout') {
                if (App::auth()->isSuperAdmin()) {
                    $user_status = App::status()->user()::ENABLED;
                } else {
                    $user_status = is_numeric($user_status = App::auth()->getInfo('user_status'))
                        ? (int) $user_status
                        : App::status()->user()::DISABLED;
                }
            }

            // Fake process to logout (kill session) and return to auth page.
            if ($process === 'Logout' || App::status()->user()->isRestricted($user_status)) {
                // Enable REST service if disabled, for next requests
                if (!App::rest()->serveRestRequests()) {
                    App::rest()->enableRestServer(true);
                }
                // Kill admin session
                App::backend()->killAdminSession();
                // Logout
                App::backend()->url()->redirect('admin.auth');
                dotclear_exit();
            }

            // Check nonce from POST requests
            if ($_POST !== []) {
                $xd_check = isset($_POST['xd_check']) && is_string($xd_check = $_POST['xd_check']) ? $xd_check : '';
                if (!App::nonce()->checkNonce($xd_check)) {
                    throw new PreconditionException();
                }
            }

            // Switch blog
            $switch_blog = isset($_REQUEST['switchblog']) && is_string($switch_blog = $_REQUEST['switchblog']) ? $switch_blog : '';
            if ($switch_blog !== '' && App::auth()->getPermissions($switch_blog) !== false) {
                App::session()->set('sess_blog_id', $switch_blog);

                if (!empty($_REQUEST['redir']) && is_string($_REQUEST['redir'])) {
                    // Keep context as far as possible
                    $redir = $_REQUEST['redir'];
                } else {
                    // Removing switchblog from URL
                    $redir = isset($_SERVER['REQUEST_URI']) && is_string($redir = $_SERVER['REQUEST_URI'])
                        ? $redir
                        : App::backend()->url()->get('admin.home');

                    $redir = (string) preg_replace('/switchblog=(.*?)(&|$)/', '', $redir);
                    $redir = (string) preg_replace('/\?$/', '', $redir);
                }

                App::auth()->prefs()->get('interface')->drop('media_manager_dir');

                if (!empty($_REQUEST['process']) && $_REQUEST['process'] == 'Media' || str_contains($redir, 'media.php')) {
                    // Remove current media dir from media manager URL
                    $redir = (string) preg_replace('/d=(.*?)(&|$)/', '', $redir);
                }

                // Remove requested blog from URL if any
                $redir = (string) preg_replace('/(\?|&)blog=(?:[^&.]*)(&|$)/', '', $redir);

                Http::redirect($redir);
                dotclear_exit();
            }

            // Check if requested blog is in URL query (blog=blog_id)
            $request_uri = isset($_SERVER['REQUEST_URI']) && is_string($request_uri = $_SERVER['REQUEST_URI']) ? $request_uri : '';
            if (($url = parse_url($request_uri)) && isset($url['query'])) {
                $params = [];
                parse_str($url['query'], $params);
                if (isset($params['blog'])) {
                    App::session()->set('sess_blog_id', $params['blog']);
                }
            }

            // Check blog to use and log out if no result
            $sess_blog_id = is_string($sess_blog_id = App::session()->get('sess_blog_id')) ? $sess_blog_id : '';
            if ($sess_blog_id !== '') {
                if (App::auth()->getPermissions($sess_blog_id) === false) {
                    App::session()->unset('sess_blog_id');
                }
            } else {
                $user_default_blog = is_string($user_default_blog = App::auth()->getInfo('user_default_blog')) ? $user_default_blog : null;
                if ($user_default_blog === null || $user_default_blog !== '') {
                    // If no default blog (null) or a default one
                    $user_blog = App::auth()->findUserBlog($user_default_blog, false);
                    if ($user_blog !== false) {
                        App::session()->set('sess_blog_id', $user_blog);
                        unset($user_blog);
                    }
                }
            }

            // Load locales
            Helper::loadLocales();

            // deprecated since 2.27, use App::lang()->getLang() instead
            if (!App::config()->modern()) {
                $GLOBALS['_lang'] = App::lang()->getLang();
            }

            // Load blog - we don't use directly $sess_blog_id variable as this session may have been changed (see above)
            $sess_blog_id = is_string($sess_blog_id = App::session()->get('sess_blog_id')) ? $sess_blog_id : '';
            if ($sess_blog_id !== '') {
                App::blog()->loadFromBlog($sess_blog_id);
            } else {
                App::session()->destroy();
                App::backend()->url()->redirect('admin.auth');
            }
        }

        // Set default backend URLs
        App::backend()->url()->setDefaultUrls();

        // (re)set post type with real backend URL (as admin URL handler is known yet)
        App::postTypes()->set(new PostType(
            'post',
            urldecode(App::backend()->url()->get('admin.post', ['id' => '%d'], '&')),
            App::url()->getURLFor('post', '%s'),
            'Posts',
            urldecode(App::backend()->url()->get('admin.posts')),    // Admin URL for list of posts
            'images/menu/edit.svg',
            'images/menu/edit-dark.svg',
        ));

        // No user nor blog, do not load more stuff
        if (!App::auth()->userID() || !App::blog()->isDefined()) {
            return true;
        }

        if ($f = App::lang()->getFilePath(App::config()->l10nRoot(), '/resources.php', App::lang()->getLang())) {
            // Use localized resources
            require $f;
        } else {
            // Use English resources
            require implode(DIRECTORY_SEPARATOR, [App::config()->l10nRoot(), 'en', 'resources.php']);
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

        $user_ui_nofavmenu = App::auth()->prefs()->get('interface')->getBool('nofavmenu', false);

        // deprecated since 2.27, use App::backend()->favorites() instead
        if (!App::config()->modern()) {
            dcCore::app()->favs = App::backend()->favorites();
        }

        // Set default menu
        App::backend()->menus()->setDefaultItems();

        // deprecated since 2.27, use App::backend()->menus() instead
        if (!App::config()->modern()) {
            dcCore::app()->menu = App::backend()->menus();
        }
        // deprecated Since 2.23, use App::backend()->menus() instead
        if (!App::config()->modern()) {
            $GLOBALS['_menu'] = App::backend()->menus();
        }

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

        if (!App::blog()->settings()->system->settingExists('jquery_migrate_mute')) {
            App::blog()->settings()->system->put('jquery_migrate_mute', true, App::blogWorkspace()::NS_BOOL, 'Mute warnings for jquery migrate plugin ?', false);
        }
        if (App::blog()->settings()->system->settingExists('jquery_allow_old_version')) {
            App::blog()->settings()->system->put('jquery_allow_old_version', false, App::blogWorkspace()::NS_BOOL, 'Allow older version of jQuery', false, true);
        }

        // Load themes
        if (App::themes()->isEmpty()) {
            App::themes()->loadModules(App::blog()->themesPath(), 'admin', App::lang()->getLang());

            // deprecated Since 2.28, use App::themes() instead
            if (!App::config()->modern()) {
                dcCore::app()->themes = App::themes();
            }
        }

        // Admin behaviors
        App::behavior()->addBehavior('adminPopupPosts', BlogPref::adminPopupPosts(...));

        return true;
    }

    /**
     * Find a menuitem corresponding with a term (or including the term)
     *
     * @param      string             $term  The term
     */
    public function searchMenuitem(string $term): false|string
    {
        // Try to find exact term
        foreach ($this->menus() as $menu) {
            if (($link = $menu->searchMenuitem($term, true)) !== false) {
                return $link;
            }
        }
        // Try to find a menuitem including the term
        foreach ($this->menus() as $menu) {
            if (($link = $menu->searchMenuitem($term, false)) !== false) {
                return $link;
            }
        }

        return false;
    }

    /**
     * Get list of available menuitems
     *
     * @return     string[]
     */
    public function listMenus(): array
    {
        $datalist = [];
        foreach ($this->menus() as $menu) {
            $datalist = [
                ...$datalist,
                ...$menu->listMenus(),
            ];
        }

        return $datalist;
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
        if (!App::config()->modern()) {
            $GLOBALS['p_url'] = $url;
        }
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
