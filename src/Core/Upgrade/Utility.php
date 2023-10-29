<?php
/**
 * @package     Dotclear
 * @subpackage  Upgrade
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

/**
 * @namespace   Dotclear.Core.Upgrade
 * @brief       Dotclear application upgrade utilities.
 */

namespace Dotclear\Core\Upgrade;

use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Exception\ContextException;
use Dotclear\Exception\PreconditionException;
use Dotclear\Exception\SessionException;
use Dotclear\Helper\L10n;
use Dotclear\Helper\Network\Http;
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
     *
     * @var     Url     $url
     */
    private Url $url;

    /**
     * Backend (admin) Menus handler instance.
     *
     * @var     Menus   $menus
     */
    private Menus $menus;

    /**
     * Constructs a new instance.
     *
     * @throws     ContextException  (if not admin context)
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
            if (!empty($_POST) && (empty($_POST['xd_check']) || !App::nonce()->checkNonce($_POST['xd_check']))) {
                throw new PreconditionException();
            }

            // Load locales
            self::loadLocales();

            // Load modules in safe mode
            App::plugins()->safeMode(true);

            try {
                App::plugins()->loadModules(App::config()->pluginsRoot(), 'upgrade', App::lang()->getLang());
            } catch(Throwable) {
                App::error()->add(__('Some plugins could not be loaded.'));
            }
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

        if (L10n::set(App::config()->l10nRoot() . '/' . App::lang()->getLang() . '/date') === false && App::lang()->getLang() != 'en') {
            L10n::set(App::config()->l10nRoot() . '/en/date');
        }
        L10n::set(App::config()->l10nRoot() . '/' . App::lang()->getLang() . '/main');
        L10n::set(App::config()->l10nRoot() . '/' . App::lang()->getLang() . '/public');
        L10n::set(App::config()->l10nRoot() . '/' . App::lang()->getLang() . '/plugins');

        // Set lexical lang
        App::lexical()->setLexicalLang('admin', App::lang()->getLang());
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
     * Kill admin session helper
     */
    public function killAdminSession(): void
    {
        // Kill session
        App::session()->destroy();

        // Unset cookie if necessary
        if (isset($_COOKIE[self::COOKIE_NAME])) {
            unset($_COOKIE[self::COOKIE_NAME]);
            setcookie(self::COOKIE_NAME, '', -600, '', '', App::config()->adminSsl());
        }
    }
}
