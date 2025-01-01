<?php

/**
 * @package     Dotclear
 * @subpackage  Upgrade
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

/**
 * @namespace   Dotclear.Core.Upgrade
 * @brief       Dotclear application upgrade utilities.
 */

namespace Dotclear\Core\Upgrade;

use Dotclear\App;
use Dotclear\Core\Backend\Resources;
use Dotclear\Core\Process;
use Dotclear\Exception\ContextException;
use Dotclear\Exception\PreconditionException;
use Dotclear\Exception\SessionException;
use Dotclear\Helper\L10n;
use Dotclear\Process\Upgrade\Cli;
use Throwable;

/**
 * @brief   Utility class for upgrade context.
 *
 * All upgrade process MUST be executed with safe mode.
 * Behaviors are prohibited.
 *
 * @since   2.29
 */
class Utility extends Process
{
    /**
     * Upgrade login cookie name.
     *
     * Need to use same cookie as Backend Utility.
     *
     * @var     string  COOKIE_NAME
     */
    public const COOKIE_NAME = 'dc_admin';

    /**
     * Upgrade Url handler instance.
     */
    private Url $url;

    /**
     * Upgrade Menus handler instance.
     */
    private Menus $menus;

    /**
     * Upgrade help resources instance.
     */
    private Resources $resources;

    /**
     * Constructs a new instance.
     *
     * @throws     ContextException  (if not upgrade context)
     */
    public function __construct()
    {
        if (!App::task()->checkContext('UPGRADE')) {
            throw new ContextException('Application is not in upgrade context.');
        }

        // HTTP/1.1
        header('Expires: Mon, 13 Aug 2003 07:48:00 GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
    }

    public static function init(): bool
    {
        // We need to pass CLI argument to App::task()->run()
        if (isset($_SERVER['argv'][1])) {
            $_SERVER['DC_RC_PATH'] = $_SERVER['argv'][1];
        }

        return true;
    }

    public static function process(): bool
    {
        if (App::config()->cliMode()) {
            // In CLI mode process does the job
            App::task()->loadProcess(Cli::class);

            return true;
        }

        if (App::auth()->sessionExists()) {
            // If we have a session we launch it now
            try {
                if (!App::auth()->checkSession()) {
                    // Avoid loop caused by old cookie
                    $p    = App::session()->getCookieParameters(false, -600);
                    $p[3] = '/';
                    setcookie(...$p);   // @phpstan-ignore-line

                    App::upgrade()->url()->redirect('upgrade.auth');
                }
            } catch (Throwable) {
                throw new SessionException(__('There seems to be no Session table in your database. Is Dotclear completly installed?'));
            }

            // Fake process to logout (kill session) and return to auth page.
            if (!empty($_REQUEST['process']) && $_REQUEST['process'] == 'Logout'
                || !App::auth()->isSuperAdmin()
            ) {
                // Enable REST service if disabled, for next requests
                if (!App::rest()->serveRestRequests()) {
                    App::rest()->enableRestServer(true);
                }
                // Kill admin session
                App::upgrade()->killAdminSession();
                // Logout
                App::upgrade()->url()->redirect('upgrade.auth');
                exit;
            }

            // Check nonce from POST requests
            if ($_POST !== [] && (empty($_POST['xd_check']) || !App::nonce()->checkNonce($_POST['xd_check']))) {
                throw new PreconditionException();
            }

            // Load locales
            self::loadLocales();
        }

        // Set default menu
        App::upgrade()->menus()->setDefaultItems();

        return true;
    }

    /**
     * Loads user locales (English if not defined).
     */
    public static function loadLocales(): void
    {
        App::lang()->setLang((string) App::auth()->getInfo('user_lang'));

        if (L10n::set(App::config()->l10nRoot() . '/' . App::lang()->getLang() . '/date') === false && App::lang()->getLang() !== 'en') {
            L10n::set(App::config()->l10nRoot() . '/en/date');
        }
        L10n::set(App::config()->l10nRoot() . '/' . App::lang()->getLang() . '/main');
        L10n::set(App::config()->l10nRoot() . '/' . App::lang()->getLang() . '/public');
        L10n::set(App::config()->l10nRoot() . '/' . App::lang()->getLang() . '/plugins');

        // Set lexical lang
        App::lexical()->setLexicalLang('admin', App::lang()->getLang());

        // Get en help resources
        $helps = [];
        if (($hfiles = @scandir(implode(DIRECTORY_SEPARATOR, [App::config()->l10nRoot(), 'en', 'help']))) !== false) {
            foreach ($hfiles as $hfile) {
                if (preg_match('/^(.*)\.html$/', $hfile, $m)) {
                    $helps[$m[1]] = implode(DIRECTORY_SEPARATOR, [App::config()->l10nRoot(), 'en', 'help', $hfile]);
                }
            }
        }
        unset($hfiles);

        // Get user lang help resources
        if (App::lang()->getLang() !== 'en' && ($hfiles = @scandir(implode(DIRECTORY_SEPARATOR, [App::config()->l10nRoot(), App::lang()->getLang(), 'help']))) !== false) {
            foreach ($hfiles as $hfile) {
                if (preg_match('/^(.*)\.html$/', $hfile, $m)) {
                    $helps[$m[1]] = implode(DIRECTORY_SEPARATOR, [App::config()->l10nRoot(), App::lang()->getLang(), 'help', $hfile]);
                }
            }
        }
        unset($hfiles);

        // Set help resources
        foreach ($helps as $key => $file) {
            App::upgrade()->resources()->set('help', $key, $file);
        }
        unset($helps);

        // Contextual help flag
        App::upgrade()->resources()->context(false);
    }

    /**
     * Get upgrade Url instance.
     *
     * @return  Url     The upgrade URL handler
     */
    public function url(): Url
    {
        if (!isset($this->url)) {
            $this->url = new Url();
        }

        return $this->url;
    }

    /**
     * Get upgrade menus instance.
     *
     * @return  Menus   The menu
     */
    public function menus(): Menus
    {
        if (!isset($this->menus)) {
            $this->menus = new Menus();
        }

        return $this->menus;
    }

    /**
     * Get upgrade resources instance.
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

    /**
     * Get menus description.
     *
     * Used by sidebar menu, home dashboard and url handler.
     *
     * @return  array<int, Icon>
     */
    public function getIcons(): array
    {
        return [
            new Icon(
                name: __('Update'),
                url: 'upgrade.upgrade',
                icon: 'images/menu/update.svg',
                dark: 'images/menu/update-dark.svg',
                perm: App::auth()->isSuperAdmin() && is_readable(App::config()->digestsRoot()),
                id: 'Upgrade',
                descr: __('On this page you can update dotclear to the latest release.')
            ),
            new Icon(
                name: __('Attic'),
                url: 'upgrade.attic',
                icon: 'images/menu/attic.svg',
                dark: 'images/menu/attic-dark.svg',
                perm: App::auth()->isSuperAdmin() && is_readable(App::config()->digestsRoot()),
                id: 'Attic',
                descr: __('On this page you can update dotclear to a release between yours and latest.')
            ),
            new Icon(
                name: __('Backups'),
                url: 'upgrade.backup',
                icon: 'images/menu/backup.svg',
                dark: 'images/menu/backup-dark.svg',
                perm: App::auth()->isSuperAdmin(),
                id: 'Backup',
                descr: __('On this page you can revert your previous installation or delete theses files.')
            ),
            new Icon(
                name: __('Languages'),
                url: 'upgrade.langs',
                icon: 'images/menu/langs.svg',
                dark: 'images/menu/langs-dark.svg',
                perm: App::auth()->isSuperAdmin(),
                id: 'Langs',
                descr: __('Here you can install, upgrade or remove languages for your Dotclear installation.')
            ),
            new Icon(
                name: __('Plugins'),
                url: 'upgrade.plugins',
                icon: 'images/menu/plugins.svg',
                dark: 'images/menu/plugins-dark.svg',
                perm: App::auth()->isSuperAdmin(),
                id: 'Plugins',
                descr: __('On this page you will manage plugins.')
            ),
            new Icon(
                name: __('Cache'),
                url: 'upgrade.cache',
                icon: 'images/menu/clear-cache.svg',
                dark: 'images/menu/clear-cache-dark.svg',
                perm: App::auth()->isSuperAdmin(),
                id: 'Cache',
                descr: __('On this page, you can clear templates and repositories cache.')
            ),
            new Icon(
                name: __('Digests'),
                url: 'upgrade.digests',
                icon: 'images/menu/digests.svg',
                dark: 'images/menu/digests-dark.svg',
                perm: App::auth()->isSuperAdmin() && is_readable(App::config()->digestsRoot()),
                id: 'Digests',
                descr: __('On this page, you can bypass corrupted files or modified files in order to perform update.')
            ),
            new Icon(
                name: __('Replay'),
                url: 'upgrade.replay',
                icon: 'images/menu/replay.svg',
                dark: 'images/menu/replay-dark.svg',
                perm: App::auth()->isSuperAdmin(),
                id: 'Replay',
                descr: __('On this page, you can try to replay update action from a given version if some files remain from last update.')
            ),
        ];
    }
}
