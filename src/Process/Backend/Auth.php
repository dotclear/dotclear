<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Process\Backend;

use Dotclear\Core\Backend\Page;
use Dotclear\App;
use Dotclear\Core\Process;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\L10n;
use Dotclear\Helper\Network\Http;
use Dotclear\Helper\Network\Mail\Mail;
use Dotclear\Core\Upgrade\Upgrade;
use Exception;
use form;

/**
 * @since 2.27 Before as admin/auth.php
 */
class Auth extends Process
{
    public static function init(): bool
    {
        // If we have a session cookie, go to index.php
        if (isset($_SESSION['sess_user_id'])) {
            App::backend()->url->redirect('admin.home');
        }

        // Loading locales for detected language
        // That's a tricky hack but it works ;)
        App::backend()->dlang = Http::getAcceptLanguage();
        App::backend()->dlang = (App::backend()->dlang === '' ? 'en' : App::backend()->dlang);
        if (App::backend()->dlang !== 'en' && preg_match('/^[a-z]{2}(-[a-z]{2})?$/', App::backend()->dlang)) {
            L10n::lang(App::backend()->dlang);
            L10n::set(App::config()->l10nRoot() . '/' . App::backend()->dlang . '/main');
        }

        if (App::config()->adminUrl() != '') {
            App::backend()->page_url = App::config()->adminUrl() . App::backend()->url->get('admin.auth');
        } else {
            App::backend()->page_url = Http::getHost() . $_SERVER['REQUEST_URI'];
        }

        App::backend()->change_pwd = App::auth()->allowPassChange() && isset($_POST['new_pwd']) && isset($_POST['new_pwd_c']) && isset($_POST['login_data']);

        App::backend()->login_data = !empty($_POST['login_data']) ? Html::escapeHTML($_POST['login_data']) : null;

        App::backend()->recover = App::auth()->allowPassChange() && !empty($_REQUEST['recover']);
        App::backend()->akey    = App::auth()->allowPassChange() && !empty($_GET['akey']) ? $_GET['akey'] : null;

        App::backend()->safe_mode = !empty($_REQUEST['safe_mode']);

        App::backend()->user_id    = null;
        App::backend()->user_pwd   = null;
        App::backend()->user_key   = null;
        App::backend()->user_email = null;
        App::backend()->err        = null;
        App::backend()->msg        = null;

        // Auto upgrade
        if ((count($_GET) == 1 && empty($_POST)) || App::backend()->safe_mode) {
            try {
                if (($changes = Upgrade::dotclearUpgrade()) !== false) {
                    App::backend()->msg = __('Dotclear has been upgraded.') . '<!-- ' . $changes . ' -->';
                }
            } catch (Exception $e) {
                App::backend()->err = $e->getMessage();
            }
        }

        if (!empty($_POST['user_id']) && !empty($_POST['user_pwd'])) {
            // If we have POST login informations, go throug auth process

            App::backend()->user_id  = $_POST['user_id'];
            App::backend()->user_pwd = $_POST['user_pwd'];
        } elseif (isset($_COOKIE['dc_admin']) && strlen($_COOKIE['dc_admin']) == 104) {
            // If we have a remember cookie, go through auth process with user_key

            $user_id = substr($_COOKIE['dc_admin'], 40);
            $user_id = @unpack('a32', @pack('H*', $user_id));
            if (is_array($user_id)) {
                $user_id                 = trim((string) $user_id[1]);
                App::backend()->user_key = substr($_COOKIE['dc_admin'], 0, 40);
                App::backend()->user_pwd = null;
            } else {
                $user_id = null;
            }
            App::backend()->user_id = $user_id;
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
        if (App::backend()->recover && !empty($_POST['user_id']) && !empty($_POST['user_email'])) {
            App::backend()->user_id    = $_POST['user_id'];
            App::backend()->user_email = Html::escapeHTML($_POST['user_email']);

            // Recover password

            try {
                $recover_key = App::auth()->setRecoverKey(App::backend()->user_id, App::backend()->user_email);

                $subject = Mail::B64Header('Dotclear ' . __('Password reset'));
                $message = __('Someone has requested to reset the password for the following site and username.') . "\n\n" . App::backend()->page_url . "\n" . __('Username:') . ' ' . App::backend()->user_id . "\n\n" . __('To reset your password visit the following address, otherwise just ignore this email and nothing will happen.') . "\n" . App::backend()->page_url . '&akey=' . $recover_key;

                $headers[] = 'From: ' . App::config()->adminMailfrom();
                $headers[] = 'Content-Type: text/plain; charset=UTF-8;';

                Mail::sendMail(App::backend()->user_email, $subject, $message, $headers);
                App::backend()->msg = sprintf(__('The e-mail was sent successfully to %s.'), App::backend()->user_email);
            } catch (Exception $e) {
                App::backend()->err = $e->getMessage();
            }
        } elseif (App::backend()->akey) {
            // Send new password

            try {
                $recover_res = App::auth()->recoverUserPassword(App::backend()->akey);

                $subject   = mb_encode_mimeheader('Dotclear ' . __('Your new password'), 'UTF-8', 'B');
                $message   = __('Username:') . ' ' . $recover_res['user_id'] . "\n" . __('Password:') . ' ' . $recover_res['new_pass'] . "\n\n" . preg_replace('/\?(.*)$/', '', (string) App::backend()->page_url);
                $headers[] = 'From: ' . App::config()->adminMailfrom();
                $headers[] = 'Content-Type: text/plain; charset=UTF-8;';

                Mail::sendMail($recover_res['user_email'], $subject, $message, $headers);
                App::backend()->msg = __('Your new password is in your mailbox.');
            } catch (Exception $e) {
                App::backend()->err = $e->getMessage();
            }
        } elseif (App::backend()->change_pwd) {
            // Change password and retry to log

            try {
                $tmp_data = explode('/', $_POST['login_data']);
                if (count($tmp_data) != 3) {
                    throw new Exception();
                }
                $data = [
                    'user_id'       => base64_decode($tmp_data[0], true),
                    'cookie_admin'  => $tmp_data[1],
                    'user_remember' => $tmp_data[2] == '1',
                ];
                if ($data['user_id'] === false) {
                    throw new Exception();
                }

                // Check login informations
                $check_user = false;
                if (strlen($data['cookie_admin']) == 104) {
                    $user_id = substr($data['cookie_admin'], 40);
                    $user_id = @unpack('a32', @pack('H*', $user_id));
                    if (is_array($user_id)) {
                        $user_id                 = trim((string) $data['user_id']);
                        App::backend()->user_key = substr($data['cookie_admin'], 0, 40);
                        $check_user              = App::auth()->checkUser($user_id, null, App::backend()->user_key) === true;
                    } else {
                        $user_id = trim((string) $user_id);
                    }
                    App::backend()->user_id = $user_id;
                }

                if (!App::auth()->allowPassChange() || !$check_user) {
                    App::backend()->change_pwd = false;

                    throw new Exception();
                }

                if ($_POST['new_pwd'] != $_POST['new_pwd_c']) {
                    throw new Exception(__("Passwords don't match"));
                }

                if (App::auth()->checkUser(App::backend()->user_id, $_POST['new_pwd']) === true) {
                    throw new Exception(__("You didn't change your password."));
                }

                $cur                  = App::auth()->openUserCursor();
                $cur->user_change_pwd = 0;
                $cur->user_pwd        = $_POST['new_pwd'];
                App::users()->updUser(App::auth()->userID(), $cur);

                App::session()->start();
                $_SESSION['sess_user_id']     = App::backend()->user_id;
                $_SESSION['sess_browser_uid'] = Http::browserUID(App::config()->masterKey());

                if ($data['user_remember']) {
                    setcookie('dc_admin', $data['cookie_admin'], ['expires' => strtotime('+15 days'), 'path' => '', 'domain' => '', 'secure' => App::config()->adminSsl()]);
                }

                App::backend()->url->redirect('admin.home');
            } catch (Exception $e) {
                App::backend()->err = $e->getMessage();
            }
        } elseif (App::backend()->user_id !== null && (App::backend()->user_pwd !== null || App::backend()->user_key !== null)) {
            // Try to log

            // We check the user
            $check_user = App::auth()->checkUser(
                App::backend()->user_id,
                App::backend()->user_pwd,
                App::backend()->user_key,
                false
            ) === true;

            if ($check_user) {
                // Check user permissions
                $check_perms = App::auth()->findUserBlog() !== false;
            } else {
                $check_perms = false;
            }

            $cookie_admin = Http::browserUID(App::config()->masterKey() . App::backend()->user_id . App::auth()->cryptLegacy(App::backend()->user_id)) . bin2hex(pack('a32', App::backend()->user_id));

            if ($check_perms && App::auth()->mustChangePassword()) {
                // User need to change password

                App::backend()->login_data = join('/', [
                    base64_encode(App::backend()->user_id),
                    $cookie_admin,
                    empty($_POST['user_remember']) ? '0' : '1',
                ]);

                if (!App::auth()->allowPassChange()) {
                    App::backend()->err = __('You have to change your password before you can login.');
                } else {
                    App::backend()->err        = __('In order to login, you have to change your password now.');
                    App::backend()->change_pwd = true;
                }
            } elseif ($check_perms && App::backend()->safe_mode && !App::auth()->isSuperAdmin()) {
                // Non super-admin user cannot use safe mode

                App::backend()->err = __('Safe Mode can only be used for super administrators.');
            } elseif ($check_perms) {
                // User may log-in

                App::session()->start();
                $_SESSION['sess_user_id']     = App::backend()->user_id;
                $_SESSION['sess_browser_uid'] = Http::browserUID(App::config()->masterKey());

                if (!empty($_POST['blog'])) {
                    $_SESSION['sess_blog_id'] = $_POST['blog'];
                }

                if (App::backend()->safe_mode && App::auth()->isSuperAdmin()) {
                    $_SESSION['sess_safe_mode'] = true;
                }

                if (!empty($_POST['user_remember'])) {
                    setcookie('dc_admin', $cookie_admin, ['expires' => strtotime('+15 days'), 'path' => '', 'domain' => '', 'secure' => App::config()->adminSsl()]);
                }

                App::backend()->url->redirect('admin.home');
            } else {
                // User cannot login

                if ($check_user) {
                    // Insufficient permissions

                    App::backend()->err = __('Insufficient permissions');
                } else {
                    // Session expired

                    App::backend()->err = isset($_COOKIE['dc_admin']) ? __('Administration session expired') : __('Wrong username or password');
                }
                if (isset($_COOKIE['dc_admin'])) {
                    unset($_COOKIE['dc_admin']);
                    setcookie('dc_admin', '', ['expires' => -600, 'path' => '', 'domain' => '', 'secure' => App::config()->adminSsl()]);
                }
            }
        }

        if (isset($_GET['user'])) {
            App::backend()->user_id = $_GET['user'];
        }

        return true;
    }

    public static function render(): void
    {
        // nullsafe before header sent
        if (!App::task()->checkContext('BACKEND')) {
            throw new Exception('Application is not in administrative context.', 500);
        }

        header('Content-Type: text/html; charset=UTF-8');
        header('X-Frame-Options: SAMEORIGIN');  // Prevents Clickjacking as far as possible

        $dlang  = App::backend()->dlang;
        $vendor = Html::escapeHTML(App::config()->vendorName());
        $buffer = '<!DOCTYPE html>' . "\n" .
            '<html lang="' . $dlang . '">' . "\n" .
            '<head>' . "\n" .
            '  <meta charset="UTF-8" />' . "\n" .
            '  <meta http-equiv="Content-Script-Type" content="text/javascript" />' . "\n" .
            '  <meta http-equiv="Content-Style-Type" content="text/css" />' . "\n" .
            '  <meta http-equiv="Content-Language" content="$dlang" />' . "\n" .
            '  <meta name="ROBOTS" content="NOARCHIVE,NOINDEX,NOFOLLOW" />' . "\n" .
            '  <meta name="GOOGLEBOT" content="NOSNIPPET" />' . "\n" .
            '  <meta name="viewport" content="width=device-width, initial-scale=1.0" />' . "\n" .
            '  <title>' . $vendor . '</title>' . "\n" .
            '  <link rel="icon" type="image/png" href="images/favicon96-logout.png" />' . "\n" .
            '  <link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon" />' . "\n" .
            '  <link rel="stylesheet" href="style/default.css" type="text/css" media="screen" />';

        echo
        $buffer . Page::jsCommon();

        # --BEHAVIOR-- loginPageHTMLHead --
        App::behavior()->callBehavior('loginPageHTMLHead');

        echo
        Page::jsJson('pwstrength', [
            'min' => sprintf(__('Password strength: %s'), __('weak')),
            'avg' => sprintf(__('Password strength: %s'), __('medium')),
            'max' => sprintf(__('Password strength: %s'), __('strong')),
        ]) .
        Page::jsLoad('js/pwstrength.js') .
        Page::jsLoad('js/_auth.js');

        $action = App::backend()->url->get('admin.auth');
        $banner = Html::escapeHTML(App::config()->vendorName());
        $buffer = '</head>' . "\n" .
            '<body id="dotclear-admin" class="auth">' . "\n" .
            '<form action="' . $action . '" method="post" id="login-screen">' . "\n" .
            '<h1 role="banner">' . $banner . '</h1>';

        echo
        $buffer;

        if (App::backend()->err) {
            echo
            '<div class="' . (App::backend()->change_pwd ? 'info' : 'error') . '" role="alert">' . App::backend()->err . '</div>';
        }
        if (App::backend()->msg) {
            echo
            '<p class="success" role="alert">' . App::backend()->msg . '</p>';
        }

        if (App::backend()->akey) {
            // Recovery key has been sent

            echo
            '<p><a href="' . App::backend()->url->get('admin.auth') . '">' . __('Back to login screen') . '</a></p>';
        } elseif (App::backend()->recover) {
            // User request a new password

            echo
            '<fieldset role="main"><h2>' . __('Request a new password') . '</h2>' .
            '<p><label for="user_id">' . __('Username:') . '</label> ' .
            form::field(
                'user_id',
                20,
                32,
                [
                    'default'      => Html::escapeHTML(App::backend()->user_id),
                    'autocomplete' => 'username',
                ]
            ) .
            '</p>' .

            '<p><label for="user_email">' . __('Email:') . '</label> ' .
            form::email(
                'user_email',
                [
                    'default'      => Html::escapeHTML(App::backend()->user_email),
                    'autocomplete' => 'email',
                ]
            ) .
            '</p>' .

            '<p><input type="submit" value="' . __('recover') . '" />' .
            form::hidden('recover', 1) . '</p>' .
            '</fieldset>' .

            '<details open id="issue">' . "\n" .
            '<summary>' . __('Other option') . '</summary>' . "\n" .
            '<p><a href="' . App::backend()->url->get('admin.auth') . '">' . __('Back to login screen') . '</a></p>' .
            '</details>';
        } elseif (App::backend()->change_pwd) {
            // User need to change password

            echo
            '<fieldset><h2>' . __('Change your password') . '</h2>' .
            '<p><label for="new_pwd">' . __('New password:') . '</label> ' .
            form::password(
                'new_pwd',
                20,
                255,
                [
                    'autocomplete' => 'new-password',
                    'class'        => 'pw-strength',
                ]
            ) . '</p>' .

            '<p><label for="new_pwd_c">' . __('Confirm password:') . '</label> ' .
            form::password(
                'new_pwd_c',
                20,
                255,
                [
                    'autocomplete' => 'new-password',
                ]
            ) . '</p>' .
            '<p><input type="submit" value="' . __('change') . '" />' .
            form::hidden('login_data', App::backend()->login_data) . '</p>' .
            '</fieldset>';
        } else {
            // Authentication

            if (is_callable([App::auth(), 'authForm'])) {
                // User-defined authentication form

                echo App::auth()->authForm(App::backend()->user_id);
            } else {
                // Standard authentication form

                if (App::backend()->safe_mode) {
                    echo
                    '<fieldset role="main">' .
                    '<h2>' . __('Safe mode login') . '</h2>' .
                    '<p class="form-note">' .
                    __('This mode allows you to login without activating any of your plugins. This may be useful to solve compatibility problems') . '&nbsp;</p>' .
                    '<p class="form-note">' . __('Update, disable or delete any plugin suspected to cause trouble, then log out and log back in normally.') .
                    '</p>';
                } else {
                    echo
                    '<fieldset role="main">';
                }

                echo
                '<p><label for="user_id">' . __('Username:') . '</label> ' .
                form::field(
                    'user_id',
                    20,
                    32,
                    [
                        'default'      => Html::escapeHTML(App::backend()->user_id),
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
                '<p><input type="submit" value="' . __('log in') . '" class="login" /></p>';

                if (!empty($_REQUEST['blog'])) {
                    echo
                    form::hidden('blog', Html::escapeHTML($_REQUEST['blog']));
                }
                if (App::backend()->safe_mode) {
                    echo
                    form::hidden('safe_mode', 1) .
                    '</fieldset>';
                } else {
                    echo
                    '</fieldset>';
                }
                echo
                '<p id="cookie_help" class="error">' . __('You must accept cookies in order to use the private area.') . '</p>';

                echo
                '<details ' . (App::backend()->safe_mode ? 'open ' : '') . 'id="issue">' . "\n";
                if (App::backend()->safe_mode) {
                    echo
                    '<summary>' . __('Other option') . '</summary>' . "\n" .
                    '<p><a href="' . App::backend()->url->get('admin.auth') . '" id="normal_mode_link">' . __('Get back to normal authentication') . '</a></p>';
                } else {
                    echo
                    '<summary>' . __('Connection issue?') . '</summary>' . "\n";
                    if (App::auth()->allowPassChange()) {
                        echo
                        '<p><a href="' . App::backend()->url->get('admin.auth', ['recover' => 1]) . '">' . __('I forgot my password') . '</a></p>';
                    }
                    echo
                    '<p><a href="' . App::backend()->url->get('admin.auth', ['safe_mode' => 1]) . '" id="safe_mode_link">' . __('I want to log in in safe mode') . '</a></p>';
                }
                echo
                '</details>';
            }
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
