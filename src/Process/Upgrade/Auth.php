<?php

/**
 * @package     Dotclear
 * @subpackage  Upgrade
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Process\Upgrade;

use Dotclear\App;
use Dotclear\Core\Upgrade\Page;
use Dotclear\Core\Process;
use Dotclear\Core\Upgrade\Upgrade;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\L10n;
use Dotclear\Helper\Network\Http;
use Exception;
use form;

/**
 * @brief   Upgrade process authentication page
 *
 * @since   2.29
 */
class Auth extends Process
{
    private static string $dlang;

    private static ?string $user_id  = null;
    private static ?string $user_pwd = null;
    private static ?string $user_key = null;
    private static ?string $err      = null;
    private static ?string $msg      = null;

    public static function init(): bool
    {
        // If we have a session cookie, go to index.php
        if (isset($_SESSION['sess_user_id'])) {
            App::upgrade()->url()->redirect('upgrade.home');
        }

        // Loading locales for detected language
        // That's a tricky hack but it works ;)
        self::$dlang = Http::getAcceptLanguage();
        self::$dlang = (self::$dlang === '' ? 'en' : self::$dlang);
        if (self::$dlang !== 'en' && preg_match('/^[a-z]{2}(-[a-z]{2})?$/', self::$dlang)) {
            L10n::lang(self::$dlang);
            L10n::set(App::config()->l10nRoot() . '/' . self::$dlang . '/main');
        }

        if (!empty($_POST['user_id']) && !empty($_POST['user_pwd'])) {
            // If we have POST login informations, go throug auth process

            self::$user_id  = $_POST['user_id'];
            self::$user_pwd = $_POST['user_pwd'];
        } elseif (isset($_COOKIE[App::upgrade()::COOKIE_NAME]) && strlen((string) $_COOKIE[App::upgrade()::COOKIE_NAME]) == 104) {
            // If we have a remember cookie, go through auth process with user_key

            $user_id = substr((string) $_COOKIE[App::upgrade()::COOKIE_NAME], 40);
            $user_id = @unpack('a32', @pack('H*', $user_id));
            if (is_array($user_id)) {
                $user_id        = trim((string) $user_id[1]);
                self::$user_key = substr((string) $_COOKIE[App::upgrade()::COOKIE_NAME], 0, 40);
                self::$user_pwd = null;
            } else {
                $user_id = null;
            }
            self::$user_id = $user_id;
        }

        // Auto upgrade
        if ((count($_GET) == 1 && empty($_POST))) {
            try {
                if (($changes = Upgrade::dotclearUpgrade()) !== false) {
                    self::$msg = __('Dotclear has been upgraded.') . '<!-- ' . $changes . ' -->';
                }
            } catch (Exception $e) {
                self::$err = $e->getMessage();
            }
        }

        // Enable REST service if disabled
        if (!App::rest()->serveRestRequests()) {
            App::rest()->enableRestServer(true);
        }

        return self::status(true);
    }

    public static function process(): bool
    {
        $headers = [];
        if (self::$user_id !== null && (self::$user_pwd !== null || self::$user_key !== null)) {
            // Try to log

            // We check the user
            $check_user = App::auth()->checkUser(
                self::$user_id,
                self::$user_pwd,
                self::$user_key,
                false
            ) === true;

            if ($check_user) {
                // Check user permissions
                $check_perms = App::auth()->isSuperAdmin();
            } else {
                $check_perms = false;
            }

            $cookie_admin = Http::browserUID(App::config()->masterKey() . self::$user_id . App::auth()->cryptLegacy(self::$user_id)) . bin2hex(pack('a32', self::$user_id));

            if ($check_perms) {
                // User may log-in

                App::session()->start();
                $_SESSION['sess_user_id']     = self::$user_id;
                $_SESSION['sess_browser_uid'] = Http::browserUID(App::config()->masterKey());

                if (!empty($_POST['blog'])) {
                    $_SESSION['sess_blog_id'] = $_POST['blog'];
                }

                if (!empty($_POST['user_remember'])) {
                    setcookie(App::upgrade()::COOKIE_NAME, $cookie_admin, ['expires' => strtotime('+15 days'), 'path' => '', 'domain' => '', 'secure' => App::config()->adminSsl()]);
                }

                App::upgrade()->url()->redirect('upgrade.home');
            } else {
                // User cannot login

                if ($check_user) {
                    // Insufficient permissions

                    self::$err = __('Insufficient permissions');
                } else {
                    // Session expired

                    self::$err = isset($_COOKIE[App::upgrade()::COOKIE_NAME]) ? __('Administration session expired') : __('Wrong username or password');
                }
                if (isset($_COOKIE[App::upgrade()::COOKIE_NAME])) {
                    unset($_COOKIE[App::upgrade()::COOKIE_NAME]);
                    setcookie(App::upgrade()::COOKIE_NAME, '', ['expires' => -600, 'path' => '', 'domain' => '', 'secure' => App::config()->adminSsl()]);
                }
            }
        }

        if (isset($_GET['user'])) {
            self::$user_id = $_GET['user'];
        }

        return true;
    }

    public static function render(): void
    {
        // nullsafe before header sent
        if (!App::task()->checkContext('UPGRADE')) {
            throw new Exception('Application is not in upgrade context.', 500);
        }

        header('Content-Type: text/html; charset=UTF-8');
        header('X-Frame-Options: SAMEORIGIN');  // Prevents Clickjacking as far as possible

        echo
     '<!DOCTYPE html>' . "\n" .
        '<html lang="' . self::$dlang . '">' . "\n" .
        '<head>' . "\n" .
        '  <meta charset="UTF-8">' . "\n" .
        '  <meta http-equiv="Content-Script-Type" content="text/javascript">' . "\n" .
        '  <meta http-equiv="Content-Style-Type" content="text/css">' . "\n" .
        '  <meta http-equiv="Content-Language" content="' . self::$dlang . '">' . "\n" .
        '  <meta name="ROBOTS" content="NOARCHIVE,NOINDEX,NOFOLLOW">' . "\n" .
        '  <meta name="GOOGLEBOT" content="NOSNIPPET">' . "\n" .
        '  <meta name="viewport" content="width=device-width, initial-scale=1.0">' . "\n" .
        '  <title>' . Html::escapeHTML(App::config()->vendorName()) . ' - ' . __('Upgrade') . '</title>' . "\n" .
        '  <link rel="icon" type="image/png" href="images/favicon96-logout.png">' . "\n" .
        '  <link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon">' . "\n" .
        '  <link rel="stylesheet" href="style/default.css" type="text/css" media="screen">' .

        Page::jsCommon() .
        Page::jsLoad('js/_auth.js') .

        '</head>' . "\n" .
        '<body id="dotclear-admin" class="auth">' . "\n" .
        '<form action="' . App::upgrade()->url()->get('upgrade.auth') . '" method="post" id="login-screen">' . "\n" .
        '<h1 role="banner">' . Html::escapeHTML(App::config()->vendorName()) . '</h1>' .
        '<h2>' . __('Upgrade') . '</h2>';

        if (self::$err) {
            echo
            '<div class="error" role="alert">' . self::$err . '</div>';
        }
        if (self::$msg) {
            echo
            '<p class="success" role="alert">' . self::$msg . '</p>';
        }

        // Authentication

        if (is_callable([App::auth(), 'authForm'])) {
            // User-defined authentication form

            echo App::auth()->authForm(self::$user_id);
        } else {
            // Standard authentication form

            echo
            '<fieldset role="main">' .
            '<p><label for="user_id">' . __('Username:') . '</label> ' .
            form::field(
                'user_id',
                20,
                32,
                [
                    'default'      => Html::escapeHTML(self::$user_id),
                    'autocomplete' => 'username',
                ]
            ) . '</p>' .

            '<p><label for="user_pwd">' . __('Password:') . '</label> ' .
            form::password(
                'user_pwd',
                20,
                255,
                [
                    'autocomplete' => 'current-password',
                ]
            ) . '</p>' .
            '<p>' . form::checkbox('user_remember', 1) . '<label for="user_remember" class="classic">' .
            __('Remember my ID on this device') . '</label></p>' .
            '<p><input type="submit" value="' . __('log in') . '" class="login"></p>' .
            '</fieldset>' .
            '<p id="cookie_help" class="error">' . __('You must accept cookies in order to use the private area.') . '</p>' .
            '<p><a href="' . App::upgrade()->url()->get('admin.home') . '">' . __('Back to normal dashboard') . '</a><p>';
        }

        echo self::html_end();
    }

    private static function html_end(): string
    {
        // Tricky code to avoid xgettext bug on indented end heredoc identifier (see https://savannah.gnu.org/bugs/?62158)
        // Warning: don't use <<< if there is some __() l10n calls after as xgettext will not find them
        return <<<HTML_END
            </form>
            </body>
            </html>
            HTML_END;
    }
}
