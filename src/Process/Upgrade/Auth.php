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
use Dotclear\Helper\Html\Form\Checkbox;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Fieldset;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Legend;
use Dotclear\Helper\Html\Form\Link;
use Dotclear\Helper\Html\Form\Password;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\L10n;
use Dotclear\Helper\Network\Http;
use Dotclear\Helper\Process\TraitProcess;
use Exception;

/**
 * @brief   Upgrade process authentication page
 *
 * @since   2.29
 */
class Auth
{
    use TraitProcess;

    private static string $dlang;

    private static ?string $user_id  = null;
    private static ?string $user_pwd = null;
    private static ?string $user_key = null;
    private static ?string $err      = null;
    private static ?string $msg      = null;

    private static bool $verify_code   = false;
    private static bool $require_2fa   = false;
    private static ?string $login_data = null;

    public static function init(): bool
    {
        // If we have a session cookie, go to index.php
        if (App::session()->get('sess_user_id') != '') {
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
        if ((count($_GET) === 1 && $_POST === [])) {
            try {
                if (($changes = App::upgrade()->upgrade()->dotclearUpgrade()) !== false) {
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

        // 2fa verification
        self::$verify_code = App::upgrade()->otp() !== null && isset($_POST['user_code']) && isset($_POST['login_data']);

        return self::status(true);
    }

    public static function process(): bool
    {
        if (App::upgrade()->otp() !== null && self::$verify_code) {
            //Check 2fa code

            $tmp_data = explode('/', (string) $_POST['login_data']);
            if (count($tmp_data) != 3) {
                throw new Exception();
            }
            $data = [
                'user_id'       => base64_decode($tmp_data[0], true),
                'cookie_admin'  => $tmp_data[1],
                'user_remember' => $tmp_data[2] === '1',
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
                    $user_id    = trim($data['user_id']);
                    $user_key   = substr($data['cookie_admin'], 0, 40);
                    $check_user = App::auth()->checkUser($user_id, null, $user_key);
                } else {
                    $user_id = trim((string) $user_id);
                }
                self::$user_id = $user_id;
            }

            // Check user permissions
            $check_perms = $check_user  && App::auth()->isSuperAdmin();
            $check_code  = $check_perms && App::upgrade()->otp()->setUser((string) self::$user_id)->verifyCode($_POST['user_code']);

            if ($check_code) {
                App::session()->set('sess_user_id', self::$user_id);
                App::session()->set('sess_browser_uid', Http::browserUID(App::config()->masterKey()));

                if ($data['user_remember']) {
                    setcookie(App::upgrade()::COOKIE_NAME, $data['cookie_admin'], ['expires' => strtotime('+15 days'), 'path' => '', 'domain' => '', 'secure' => App::config()->adminSsl()]);
                }

                App::upgrade()->url()->redirect('upgrade.home');
            } else {
                // User cannot login

                if ($check_user) {
                    // Code verification failed

                    self::$err = __('Code verification failed');
                } elseif (!$check_perms) {
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
        } elseif (self::$user_id !== null && (self::$user_pwd !== null || self::$user_key !== null)) {
            // Try to log

            // We check the user
            $check_user = App::auth()->checkUser(
                self::$user_id,
                self::$user_pwd,
                self::$user_key,
                false
            );

            // Check user permissions
            $check_perms = $check_user && App::auth()->isSuperAdmin();

            $cookie_admin = Http::browserUID(App::config()->masterKey() . self::$user_id . App::auth()->cryptLegacy(self::$user_id)) . bin2hex(pack('a32', self::$user_id));

            if ($check_perms) {
                // User may log-in

                // Check if user need 2fa
                self::$require_2fa = App::upgrade()->otp() !== null && App::upgrade()->otp()->setUser(self::$user_id)->isVerified();

                if (self::$require_2fa) {
                    // Required 2fa authentication. Skip normal login and go to 2fa form

                    self::$login_data = implode('/', [
                        base64_encode(self::$user_id),
                        $cookie_admin,
                        empty($_POST['user_remember']) ? '0' : '1',
                    ]);
                } else {
                    App::session()->set('sess_user_id', self::$user_id);
                    App::session()->set('sess_browser_uid', Http::browserUID(App::config()->masterKey()));

                    if (!empty($_POST['blog'])) {
                        App::session()->set('sess_blog_id', $_POST['blog']);
                    }

                    if (!empty($_POST['user_remember'])) {
                        setcookie(App::upgrade()::COOKIE_NAME, $cookie_admin, ['expires' => strtotime('+15 days'), 'path' => '', 'domain' => '', 'secure' => App::config()->adminSsl()]);
                    }

                    App::upgrade()->url()->redirect('upgrade.home');
                }
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

        $fields = [
            (new Text('h1', Html::escapeHTML(App::config()->vendorName())))
                ->role('banner'),
            (new Text('h2', __('Upgrade'))),
        ];

        if (self::$err) {
            $fields[] = (new Div())
                ->class('error')
                ->role('alert')
                ->items([
                    new Text('', self::$err),
                ]);
        }
        if (self::$msg) {
            $fields[] = (new Text('p', self::$msg))
                ->class('success')
                ->role('alert');
        }

        // Authentication
        $user_defined_auth_form = null;
        if (is_callable([App::auth(), 'authForm'], false, $user_defined_auth_form)) {
            // User-defined authentication form

            $fields[] = new Text('', $user_defined_auth_form(self::$user_id));
        } elseif (App::upgrade()->otp() !== null && self::$require_2fa) {
            // 2FA verification
            $fields[] = (new Set())
                ->items([
                    (new Fieldset())
                        ->role('main')
                        ->legend((new Legend(__('Two factors authentication'))))
                        ->fields([
                            (new Para())
                                ->items([
                                    (new Input('user_code'))
                                        ->label((new Label(__('Enter code:'), Label::IL_TF)))
                                        ->size(20)
                                        ->maxlength(App::upgrade()->otp()->getDigits())
                                        ->default('')
                                        ->translate(false)
                                        ->autocomplete('one-time-code')
                                        ->autofocus(true),
                                ]),
                            (new Para())
                                ->items([
                                    (new Submit('verify_sbumit', __('Verify'))),
                                    (new Hidden('login_data', (string) self::$login_data)),
                                ]),
                            (new Para())
                                ->items([
                                    (new Link())
                                        ->href(App::upgrade()->url()->get('upgrade.auth'))
                                        ->text(__('Back to login screen')),
                                ]),
                        ]),
                ]);
        } else {
            // Standard authentication form
            $fields[] = (new Set())
                ->items([
                    (new Fieldset())
                        ->role('main')
                        ->items([
                            (new Para())
                                ->items([
                                    (new Input('user_id'))
                                        ->label((new Label(__('Username:'), Label::IL_TF)))
                                        ->size(20)
                                        ->maxlength(32)
                                        ->default(Html::escapeHTML(self::$user_id))
                                        ->translate(false)
                                        ->autocomplete('username'),
                                ]),
                            (new Para())
                                ->items([
                                    (new Password('user_pwd'))
                                        ->label((new Label(__('Password:'), Label::IL_TF)))
                                        ->size(20)
                                        ->maxlength(255)
                                        ->translate(false)
                                        ->autocomplete('current-password'),
                                ]),
                            (new Para())
                                ->items([
                                    (new Checkbox('user_remember'))
                                        ->value(1)
                                        ->label((new Label(__('Remember my ID on this device'), Label::IL_FT))),
                                ]),
                            (new Para())
                                ->items([
                                    (new Submit('login', __('log in')))
                                        ->class('login'),
                                ]),

                        ]),
                    (new Text('p', __('You must accept cookies in order to use the private area.')))
                        ->id('cookie_help')
                        ->class('error'),
                    (new Para())
                        ->items([
                            (new Link())
                                ->href(App::upgrade()->url()->get('admin.home'))
                                ->text(__('Back to normal dashboard')),
                        ]),
                ]);
        }

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

        App::upgrade()->page()->jsCommon() .
        App::upgrade()->page()->jsJson('pwstrength', [
            'min' => sprintf(__('Password strength: %s'), __('weak')),
            'avg' => sprintf(__('Password strength: %s'), __('medium')),
            'max' => sprintf(__('Password strength: %s'), __('strong')),
        ]) .
        App::upgrade()->page()->jsLoad('js/pwstrength.js') .
        App::upgrade()->page()->jsLoad('js/_auth.js') .

        '</head>' . "\n" .
        '<body id="dotclear-admin" class="auth">' . "\n" .
        (new Form('login-screen'))
            ->method('post')
            ->action(App::upgrade()->url()->get('upgrade.auth'))
            ->fields($fields)
            ->render() .
        self::html_end();
    }

    private static function html_end(): string
    {
        // Tricky code to avoid xgettext bug on indented end heredoc identifier (see https://savannah.gnu.org/bugs/?62158)
        // Warning: don't use <<< if there is some __() l10n calls after as xgettext will not find them
        return <<<HTML_END
            </body>
            </html>
            HTML_END;
    }
}
