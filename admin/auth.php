<?php
/**
 * @package Dotclear
 * @subpackage Backend
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
require __DIR__ . '/../inc/admin/prepend.php';

class adminAuth
{
    /**
     * Initializes the page.
     */
    public static function init()
    {
        // If we have a session cookie, go to index.php
        if (isset($_SESSION['sess_user_id'])) {
            dcCore::app()->adminurl->redirect('admin.home');
        }

        // Loading locales for detected language
        // That's a tricky hack but it works ;)
        dcCore::app()->admin->dlang = http::getAcceptLanguage();
        dcCore::app()->admin->dlang = (dcCore::app()->admin->dlang === '' ? 'en' : dcCore::app()->admin->dlang);
        if (dcCore::app()->admin->dlang !== 'en' && preg_match('/^[a-z]{2}(-[a-z]{2})?$/', dcCore::app()->admin->dlang)) {
            l10n::lang(dcCore::app()->admin->dlang);
            l10n::set(DC_L10N_ROOT . '/' . dcCore::app()->admin->dlang . '/main');
        }

        if (defined('DC_ADMIN_URL')) {
            dcCore::app()->admin->page_url = DC_ADMIN_URL . dcCore::app()->adminurl->get('admin.auth');
        } else {
            dcCore::app()->admin->page_url = http::getHost() . $_SERVER['REQUEST_URI'];
        }

        dcCore::app()->admin->change_pwd = dcCore::app()->auth->allowPassChange() && isset($_POST['new_pwd']) && isset($_POST['new_pwd_c']) && isset($_POST['login_data']);

        dcCore::app()->admin->login_data = !empty($_POST['login_data']) ? html::escapeHTML($_POST['login_data']) : null;

        dcCore::app()->admin->recover = dcCore::app()->auth->allowPassChange() && !empty($_REQUEST['recover']);
        dcCore::app()->admin->akey    = dcCore::app()->auth->allowPassChange() && !empty($_GET['akey']) ? $_GET['akey'] : null;

        dcCore::app()->admin->safe_mode = !empty($_REQUEST['safe_mode']);

        dcCore::app()->admin->user_id    = null;
        dcCore::app()->admin->user_pwd   = null;
        dcCore::app()->admin->user_key   = null;
        dcCore::app()->admin->user_email = null;
        dcCore::app()->admin->err        = null;
        dcCore::app()->admin->msg        = null;

        // Auto upgrade
        if (empty($_GET) && empty($_POST)) {
            require __DIR__ . '/../inc/dbschema/upgrade.php';

            try {
                if (($changes = dcUpgrade::dotclearUpgrade()) !== false) {
                    dcCore::app()->admin->msg = __('Dotclear has been upgraded.') . '<!-- ' . $changes . ' -->';
                }
            } catch (Exception $e) {
                dcCore::app()->admin->err = $e->getMessage();
            }
        }

        if (!empty($_POST['user_id']) && !empty($_POST['user_pwd'])) {
            // If we have POST login informations, go throug auth process

            dcCore::app()->admin->user_id  = !empty($_POST['user_id']) ? $_POST['user_id'] : null;
            dcCore::app()->admin->user_pwd = !empty($_POST['user_pwd']) ? $_POST['user_pwd'] : null;
        } elseif (isset($_COOKIE['dc_admin']) && strlen($_COOKIE['dc_admin']) == 104) {
            // If we have a remember cookie, go through auth process with user_key

            $user_id = substr($_COOKIE['dc_admin'], 40);
            $user_id = @unpack('a32', @pack('H*', $user_id));
            if (is_array($user_id)) {
                $user_id                       = trim((string) $user_id[1]);
                dcCore::app()->admin->user_key = substr($_COOKIE['dc_admin'], 0, 40);
                dcCore::app()->admin->user_pwd = null;
            } else {
                $user_id = null;
            }
            dcCore::app()->admin->user_id = $user_id;
        }
    }

    /**
     * Processes the request(s).
     */
    public static function process()
    {
        if (dcCore::app()->admin->recover && !empty($_POST['user_id']) && !empty($_POST['user_email'])) {
            dcCore::app()->admin->user_id    = $_POST['user_id'];
            dcCore::app()->admin->user_email = html::escapeHTML($_POST['user_email']);

            // Recover password

            try {
                $recover_key = dcCore::app()->auth->setRecoverKey(dcCore::app()->admin->user_id, dcCore::app()->admin->user_email);

                $subject = mail::B64Header('Dotclear ' . __('Password reset'));
                $message = __('Someone has requested to reset the password for the following site and username.') . "\n\n" . dcCore::app()->admin->page_url . "\n" . __('Username:') . ' ' . dcCore::app()->admin->user_id . "\n\n" . __('To reset your password visit the following address, otherwise just ignore this email and nothing will happen.') . "\n" . dcCore::app()->admin->page_url . '?akey=' . $recover_key;

                $headers[] = 'From: ' . (defined('DC_ADMIN_MAILFROM') && DC_ADMIN_MAILFROM ? DC_ADMIN_MAILFROM : 'dotclear@local');
                $headers[] = 'Content-Type: text/plain; charset=UTF-8;';

                mail::sendMail(dcCore::app()->admin->user_email, $subject, $message, $headers);
                dcCore::app()->admin->msg = sprintf(__('The e-mail was sent successfully to %s.'), dcCore::app()->admin->user_email);
            } catch (Exception $e) {
                dcCore::app()->admin->err = $e->getMessage();
            }
        } elseif (dcCore::app()->admin->akey) {
            // Send new password

            try {
                $recover_res = dcCore::app()->auth->recoverUserPassword(dcCore::app()->admin->akey);

                $subject   = mb_encode_mimeheader('Dotclear ' . __('Your new password'), 'UTF-8', 'B');
                $message   = __('Username:') . ' ' . $recover_res['user_id'] . "\n" . __('Password:') . ' ' . $recover_res['new_pass'] . "\n\n" . preg_replace('/\?(.*)$/', '', dcCore::app()->admin->page_url);
                $headers[] = 'From: ' . (defined('DC_ADMIN_MAILFROM') && DC_ADMIN_MAILFROM ? DC_ADMIN_MAILFROM : 'dotclear@local');
                $headers[] = 'Content-Type: text/plain; charset=UTF-8;';

                mail::sendMail($recover_res['user_email'], $subject, $message, $headers);
                dcCore::app()->admin->msg = __('Your new password is in your mailbox.');
            } catch (Exception $e) {
                dcCore::app()->admin->err = $e->getMessage();
            }
        } elseif (dcCore::app()->admin->change_pwd) {
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
                        $user_id                       = trim((string) $data['user_id']);
                        dcCore::app()->admin->user_key = substr($data['cookie_admin'], 0, 40);
                        $check_user                    = dcCore::app()->auth->checkUser($user_id, null, dcCore::app()->admin->user_key) === true;
                    } else {
                        $user_id = trim((string) $user_id);
                    }
                    dcCore::app()->admin->user_id = $user_id;
                }

                if (!dcCore::app()->auth->allowPassChange() || !$check_user) {
                    dcCore::app()->admin->change_pwd = false;

                    throw new Exception();
                }

                if ($_POST['new_pwd'] != $_POST['new_pwd_c']) {
                    throw new Exception(__("Passwords don't match"));
                }

                if (dcCore::app()->auth->checkUser(dcCore::app()->admin->user_id, $_POST['new_pwd']) === true) {
                    throw new Exception(__("You didn't change your password."));
                }

                $cur                  = dcCore::app()->con->openCursor(dcCore::app()->prefix . dcAuth::USER_TABLE_NAME);
                $cur->user_change_pwd = 0;
                $cur->user_pwd        = $_POST['new_pwd'];
                dcCore::app()->updUser(dcCore::app()->auth->userID(), $cur);

                dcCore::app()->session->start();
                $_SESSION['sess_user_id']     = dcCore::app()->admin->user_id;
                $_SESSION['sess_browser_uid'] = http::browserUID(DC_MASTER_KEY);

                if ($data['user_remember']) {
                    setcookie('dc_admin', $data['cookie_admin'], strtotime('+15 days'), '', '', DC_ADMIN_SSL);
                }

                dcCore::app()->adminurl->redirect('admin.home');
            } catch (Exception $e) {
                dcCore::app()->admin->err = $e->getMessage();
            }
        } elseif (dcCore::app()->admin->user_id !== null && (dcCore::app()->admin->user_pwd !== null || dcCore::app()->admin->user_key !== null)) {
            // Try to log

            // We check the user
            $check_user = dcCore::app()->auth->checkUser(
                dcCore::app()->admin->user_id,
                dcCore::app()->admin->user_pwd,
                dcCore::app()->admin->user_key,
                false
            ) === true;

            if ($check_user) {
                // Check user permissions
                $check_perms = dcCore::app()->auth->findUserBlog() !== false;
            } else {
                $check_perms = false;
            }

            $cookie_admin = http::browserUID(DC_MASTER_KEY . dcCore::app()->admin->user_id . dcCore::app()->auth->cryptLegacy(dcCore::app()->admin->user_id)) . bin2hex(pack('a32', dcCore::app()->admin->user_id));

            if ($check_perms && dcCore::app()->auth->mustChangePassword()) {
                // User need to change password

                dcCore::app()->admin->login_data = join('/', [
                    base64_encode(dcCore::app()->admin->user_id),
                    $cookie_admin,
                    empty($_POST['user_remember']) ? '0' : '1',
                ]);

                if (!dcCore::app()->auth->allowPassChange()) {
                    dcCore::app()->admin->err = __('You have to change your password before you can login.');
                } else {
                    dcCore::app()->admin->err        = __('In order to login, you have to change your password now.');
                    dcCore::app()->admin->change_pwd = true;
                }
            } elseif ($check_perms && dcCore::app()->admin->safe_mode && !dcCore::app()->auth->isSuperAdmin()) {
                // Non super-admin user cannot use safe mode

                dcCore::app()->admin->err = __('Safe Mode can only be used for super administrators.');
            } elseif ($check_perms) {
                // User may log-in

                dcCore::app()->session->start();
                $_SESSION['sess_user_id']     = dcCore::app()->admin->user_id;
                $_SESSION['sess_browser_uid'] = http::browserUID(DC_MASTER_KEY);

                if (!empty($_POST['blog'])) {
                    $_SESSION['sess_blog_id'] = $_POST['blog'];
                }

                if (dcCore::app()->admin->safe_mode && dcCore::app()->auth->isSuperAdmin()) {
                    $_SESSION['sess_safe_mode'] = true;
                }

                if (!empty($_POST['user_remember'])) {
                    setcookie('dc_admin', $cookie_admin, strtotime('+15 days'), '', '', DC_ADMIN_SSL);
                }

                dcCore::app()->adminurl->redirect('admin.home');
            } else {
                // User cannot login

                if ($check_user) {
                    // Insufficient permissions

                    dcCore::app()->admin->err = __('Insufficient permissions');
                } else {
                    // Session expired

                    dcCore::app()->admin->err = isset($_COOKIE['dc_admin']) ? __('Administration session expired') : __('Wrong username or password');
                }
                if (isset($_COOKIE['dc_admin'])) {
                    unset($_COOKIE['dc_admin']);
                    setcookie('dc_admin', '', -600, '', '', DC_ADMIN_SSL);
                }
            }
        }

        if (isset($_GET['user'])) {
            dcCore::app()->admin->user_id = $_GET['user'];
        }
    }

    /**
     * Renders the page.
     */
    public static function render()
    {
        header('Content-Type: text/html; charset=UTF-8');
        header('X-Frame-Options: SAMEORIGIN');  // Prevents Clickjacking as far as possible

        $dlang  = dcCore::app()->admin->dlang;
        $vendor = html::escapeHTML(DC_VENDOR_NAME);
        $buffer = <<<HTML_BEGIN
            <!DOCTYPE html>
            <html lang="$dlang">
            <head>
              <meta charset="UTF-8" />
              <meta http-equiv="Content-Script-Type" content="text/javascript" />
              <meta http-equiv="Content-Style-Type" content="text/css" />
              <meta http-equiv="Content-Language" content="$dlang" />
              <meta name="ROBOTS" content="NOARCHIVE,NOINDEX,NOFOLLOW" />
              <meta name="GOOGLEBOT" content="NOSNIPPET" />
              <meta name="viewport" content="width=device-width, initial-scale=1.0" />
              <title>$vendor</title>
              <link rel="icon" type="image/png" href="images/favicon96-logout.png" />
              <link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon" />
              <link rel="stylesheet" href="style/default.css" type="text/css" media="screen" />
            HTML_BEGIN;

        echo
        $buffer . dcPage::jsCommon();

        # --BEHAVIOR-- loginPageHTMLHead
        dcCore::app()->callBehavior('loginPageHTMLHead');

        echo
        dcPage::jsJson('pwstrength', [
            'min' => sprintf(__('Password strength: %s'), __('weak')),
            'avg' => sprintf(__('Password strength: %s'), __('medium')),
            'max' => sprintf(__('Password strength: %s'), __('strong')),
        ]) .
        dcPage::jsLoad('js/pwstrength.js') .
        dcPage::jsLoad('js/_auth.js');

        $action = dcCore::app()->adminurl->get('admin.auth');
        $banner = html::escapeHTML(DC_VENDOR_NAME);
        $buffer = <<<HTML_BODY
            </head>
            <body id="dotclear-admin" class="auth">
            <form action="$action" method="post" id="login-screen">
            <h1 role="banner">$banner</h1>
            HTML_BODY;

        echo
        $buffer;

        if (dcCore::app()->admin->err) {
            echo
            '<div class="' . (dcCore::app()->admin->change_pwd ? 'info' : 'error') . '" role="alert">' . dcCore::app()->admin->err . '</div>';
        }
        if (dcCore::app()->admin->msg) {
            echo
            '<p class="success" role="alert">' . dcCore::app()->admin->msg . '</p>';
        }

        if (dcCore::app()->admin->akey) {
            // Recovery key has been sent

            echo
            '<p><a href="' . dcCore::app()->adminurl->get('admin.auth') . '">' . __('Back to login screen') . '</a></p>';
        } elseif (dcCore::app()->admin->recover) {
            // User request a new password

            echo
            '<div class="fieldset" role="main"><h2>' . __('Request a new password') . '</h2>' .
            '<p><label for="user_id">' . __('Username:') . '</label> ' .
            form::field(
                'user_id',
                20,
                32,
                [
                    'default'      => html::escapeHTML(dcCore::app()->admin->user_id),
                    'autocomplete' => 'username',
                ]
            ) .
            '</p>' .

            '<p><label for="user_email">' . __('Email:') . '</label> ' .
            form::email(
                'user_email',
                [
                    'default'      => html::escapeHTML(dcCore::app()->admin->user_email),
                    'autocomplete' => 'email',
                ]
            ) .
            '</p>' .

            '<p><input type="submit" value="' . __('recover') . '" />' .
            form::hidden('recover', 1) . '</p>' .
            '</div>' .

            '<details open id="issue">' . "\n" .
            '<summary>' . __('Other option') . '</summary>' . "\n" .
            '<p><a href="' . dcCore::app()->adminurl->get('admin.auth') . '">' . __('Back to login screen') . '</a></p>' .
            '</details>';
        } elseif (dcCore::app()->admin->change_pwd) {
            // User need to change password

            echo
            '<div class="fieldset"><h2>' . __('Change your password') . '</h2>' .
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
            form::hidden('login_data', dcCore::app()->admin->login_data) . '</p>' .
            '</div>';
        } else {
            // Authentication

            if (is_callable([dcCore::app()->auth, 'authForm'])) {
                // User-defined authentication form

                echo dcCore::app()->auth->authForm(dcCore::app()->admin->user_id);
            } else {
                // Standard authentication form

                if (dcCore::app()->admin->safe_mode) {
                    echo
                    '<div class="fieldset" role="main">' .
                    '<h2>' . __('Safe mode login') . '</h2>' .
                    '<p class="form-note">' .
                    __('This mode allows you to login without activating any of your plugins. This may be useful to solve compatibility problems') . '&nbsp;</p>' .
                    '<p class="form-note">' . __('Update, disable or delete any plugin suspected to cause trouble, then log out and log back in normally.') .
                    '</p>';
                } else {
                    echo
                    '<div class="fieldset" role="main">';
                }

                echo
                '<p><label for="user_id">' . __('Username:') . '</label> ' .
                form::field(
                    'user_id',
                    20,
                    32,
                    [
                        'default'      => html::escapeHTML(dcCore::app()->admin->user_id),
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
                    form::hidden('blog', html::escapeHTML($_REQUEST['blog']));
                }
                if (dcCore::app()->admin->safe_mode) {
                    echo
                    form::hidden('safe_mode', 1) .
                    '</div>';
                } else {
                    echo
                    '</div>';
                }
                echo
                '<p id="cookie_help" class="error">' . __('You must accept cookies in order to use the private area.') . '</p>';

                echo
                '<details ' . (dcCore::app()->admin->safe_mode ? 'open ' : '') . 'id="issue">' . "\n";
                if (dcCore::app()->admin->safe_mode) {
                    echo
                    '<summary>' . __('Other option') . '</summary>' . "\n" .
                    '<p><a href="' . dcCore::app()->adminurl->get('admin.auth') . '" id="normal_mode_link">' . __('Get back to normal authentication') . '</a></p>';
                } else {
                    echo
                    '<summary>' . __('Connection issue?') . '</summary>' . "\n";
                    if (dcCore::app()->auth->allowPassChange()) {
                        echo
                        '<p><a href="' . dcCore::app()->adminurl->get('admin.auth', ['recover' => 1]) . '">' . __('I forgot my password') . '</a></p>';
                    }
                    echo
                    '<p><a href="' . dcCore::app()->adminurl->get('admin.auth', ['safe_mode' => 1]) . '" id="safe_mode_link">' . __('I want to log in in safe mode') . '</a></p>';
                }
                echo
                '</details>';
            }
        }

        $buffer = <<<HTML_END
            </form>
            </body>
            </html>
            HTML_END;

        echo $buffer;
    }
}

adminAuth::init();
adminAuth::process();
adminAuth::render();
