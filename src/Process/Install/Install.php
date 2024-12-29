<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */

namespace Dotclear\Process\Install;

use DateTimeZone;
use Dotclear\App;
use Dotclear\Core\Backend\Page;
use Dotclear\Core\Backend\Favorites;
use Dotclear\Core\Install\Utils;
use Dotclear\Core\Process;
use Dotclear\Database\Structure;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\L10n;
use Dotclear\Helper\Network\Http;
use Dotclear\Helper\Text;
use Exception;
use form;

/**
 * @brief   Intallation process.
 */
class Install extends Process
{
    /**
     * Installation checking flag
     */
    private static bool $can_install = true;

    /**
     * Error description
     */
    private static string $err = '';

    /**
     * Installation step
     */
    private static int $step = 0;

    /**
     * Current language
     */
    private static string $dlang = 'en';

    /**
     * Dotclear root URL
     */
    private static string $root_url = '';

    /**
     * Dotclear admin URL
     */
    private static string $admin_url = '';

    /**
     * Plugin installation results
     *
     * @var        array<string, array<string, bool|string>>
     */
    private static array $plugins_install = [
        'success' => [],
        'failure' => [],
    ];

    /**
     * User email
     */
    private static ?string $u_email = '';

    /**
     * User firstname
     */
    private static ?string $u_firstname = '';

    /**
     * User lastname
     */
    private static ?string $u_name = '';

    /**
     * User login
     */
    private static ?string $u_login = '';

    /**
     * User password
     */
    private static ?string $u_pwd = '';

    /**
     * User password verification
     */
    private static ?string $u_pwd2 = '';

    public static function init(): bool
    {
        if (!self::status(App::task()->checkContext('INSTALL'))) {
            throw new Exception('Not found', 404);
        }

        # Loading locales for detected language
        self::$dlang = Http::getAcceptLanguage();
        if (self::$dlang !== 'en') {
            L10n::init(self::$dlang);
            L10n::set(App::config()->l10nRoot() . '/' . self::$dlang . '/date');
            L10n::set(App::config()->l10nRoot() . '/' . self::$dlang . '/main');
            L10n::set(App::config()->l10nRoot() . '/' . self::$dlang . '/plugins');
        }

        if (App::config()->masterKey() === '') {
            self::$can_install = false;
            self::$err         = '<p>' . __('Please set a master key (DC_MASTER_KEY) in configuration file.') . '</p>';
        }

        # Check if dotclear is already installed
        $schema = App::con()->schema();
        if (in_array(App::con()->prefix() . App::blog()::POST_TABLE_NAME, $schema->getTables())) {
            self::$can_install = false;
            self::$err         = '<p>' . __('Dotclear is already installed.') . '</p>';
        }

        # Check system capabilites
        $_e = [];
        if (!Utils::check(App::con(), $_e)) {
            self::$can_install = false;
            self::$err         = '<p>' . __('Dotclear cannot be installed.') . '</p><ul><li>' . implode('</li><li>', $_e) . '</li></ul>';
        }

        return self::status();
    }

    public static function process(): bool
    {
        if (!self::status()) {
            throw new Exception('Not found', 404);
        }

        if (self::$can_install && !empty($_POST)) {
            self::$u_email     = $_POST['u_email']     ?? null;
            self::$u_firstname = $_POST['u_firstname'] ?? null;
            self::$u_name      = $_POST['u_name']      ?? null;
            self::$u_login     = $_POST['u_login']     ?? null;
            self::$u_pwd       = $_POST['u_pwd']       ?? null;
            self::$u_pwd2      = $_POST['u_pwd2']      ?? null;

            try {
                # Check user information
                if (empty(self::$u_login)) {
                    throw new Exception(__('No user ID given'));
                }
                if (!preg_match('/^[A-Za-z0-9@._-]{2,}$/', (string) self::$u_login)) {
                    throw new Exception(__('User ID must contain at least 2 characters using letters, numbers or symbols.'));
                }
                if (self::$u_email && !Text::isEmail(self::$u_email)) {
                    throw new Exception(__('Invalid email address'));
                }

                if (empty(self::$u_pwd)) {
                    throw new Exception(__('No password given'));
                }
                if (self::$u_pwd != self::$u_pwd2) {
                    throw new Exception(__("Passwords don't match"));
                }
                if (strlen((string) self::$u_pwd) < 6) {
                    throw new Exception(__('Password must contain at least 6 characters.'));
                }

                # Try to guess timezone
                $default_tz = 'Europe/London';
                if (!empty($_POST['u_date']) && function_exists('timezone_open') && preg_match('/\((.+)\)$/', (string) $_POST['u_date'], $_tz)) {
                    $_tz = $_tz[1];
                    $_tz = @timezone_open($_tz);
                    if ($_tz instanceof DateTimeZone) {
                        $_tz = @timezone_name_get($_tz);

                        // check if timezone is valid
                        // date_default_timezone_set throw E_NOTICE and/or E_WARNING if timezone is not valid and return false
                        if (@date_default_timezone_set($_tz) && $_tz) {
                            $default_tz = $_tz;
                        }
                    }
                    unset($_tz);
                }

                # Create schema
                $_s = new Structure(App::con(), App::con()->prefix());

                # Fill database structrue
                Utils::dbSchema($_s);

                $si = new Structure(App::con(), App::con()->prefix());
                $si->synchronize($_s);

                # Create user
                $cur                 = App::con()->openCursor(App::con()->prefix() . App::auth()::USER_TABLE_NAME);
                $cur->user_id        = self::$u_login;
                $cur->user_super     = 1;
                $cur->user_pwd       = App::auth()->crypt(self::$u_pwd);
                $cur->user_name      = (string) self::$u_name;
                $cur->user_firstname = (string) self::$u_firstname;
                $cur->user_email     = (string) self::$u_email;
                $cur->user_lang      = self::$dlang;
                $cur->user_tz        = $default_tz;
                $cur->user_creadt    = date('Y-m-d H:i:s');
                $cur->user_upddt     = date('Y-m-d H:i:s');
                $cur->user_options   = serialize(App::users()->userDefaults());
                $cur->insert();

                App::auth()->checkUser(self::$u_login);

                self::$admin_url = (string) preg_replace('%install(/(index.php)?)?$%', '', (string) $_SERVER['REQUEST_URI']);
                self::$root_url  = (string) preg_replace('%/admin/install(/(index.php)?)?$%', '', (string) $_SERVER['REQUEST_URI']);

                # Create blog
                $cur            = App::blog()->openBlogCursor();
                $cur->blog_id   = 'default';
                $cur->blog_url  = Http::getHost() . self::$root_url . '/index.php?';
                $cur->blog_name = __('My first blog');
                App::blogs()->addBlog($cur);

                # Create global blog settings
                Utils::blogDefaults();

                $blog_settings = App::blogSettings()->createFromBlog('default');
                $blog_settings->system->put('blog_timezone', $default_tz);
                $blog_settings->system->put('lang', self::$dlang);
                $blog_settings->system->put('public_url', self::$root_url . '/public');
                $blog_settings->system->put('themes_url', self::$root_url . '/themes');

                # date and time formats
                $formatDate   = __('%A, %B %e %Y');
                $date_formats = ['%Y-%m-%d', '%m/%d/%Y', '%d/%m/%Y', '%Y/%m/%d', '%d.%m.%Y', '%b %e %Y', '%e %b %Y', '%Y %b %e',
                    '%a, %Y-%m-%d', '%a, %m/%d/%Y', '%a, %d/%m/%Y', '%a, %Y/%m/%d', '%B %e, %Y', '%e %B, %Y', '%Y, %B %e', '%e. %B %Y',
                    '%A, %B %e, %Y', '%A, %e %B, %Y', '%A, %Y, %B %e', '%A, %Y, %B %e', '%A, %e. %B %Y', ];
                $time_formats = ['%H:%M', '%I:%M', '%l:%M', '%Hh%M', '%Ih%M', '%lh%M'];
                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    $formatDate   = preg_replace('#(?<!%)((?:%%)*)%e#', '\1%#d', $formatDate);
                    $date_formats = array_map(
                        fn ($f): string => str_replace('%e', '%#d', $f),
                        $date_formats
                    );
                }
                $blog_settings->system->put('date_format', $formatDate);
                $blog_settings->system->put('date_formats', $date_formats, 'array', 'Date formats examples', true, true);
                $blog_settings->system->put('time_formats', $time_formats, 'array', 'Time formats examples', true, true);

                # Add repository URL for themes and plugins
                $blog_settings->system->put('store_plugin_url', 'https://update.dotaddict.org/dc2/plugins.xml', 'string', 'Plugins XML feed location', true, true);
                $blog_settings->system->put('store_theme_url', 'https://update.dotaddict.org/dc2/themes.xml', 'string', 'Themes XML feed location', true, true);

                # CSP directive (admin part)

                /* SQlite Clearbricks driver does not allow using single quote at beginning or end of a field value
                so we have to use neutral values (localhost and 127.0.0.1) for some CSP directives
                 */
                $csp_prefix = App::con()->driver() === 'sqlite' ? 'localhost ' : ''; // Hack for SQlite Clearbricks driver
                $csp_suffix = App::con()->driver() === 'sqlite' ? ' 127.0.0.1' : ''; // Hack for SQlite Clearbricks driver

                $blog_settings->system->put('csp_admin_on', true, 'boolean', 'Send CSP header (admin)', true, true);
                $blog_settings->system->put('csp_admin_report_only', false, 'boolean', 'CSP Report only violations (admin)', true, true);
                $blog_settings->system->put(
                    'csp_admin_default',
                    $csp_prefix . "'self'" . $csp_suffix,
                    'string',
                    'CSP default-src directive',
                    true,
                    true
                );
                $blog_settings->system->put(
                    'csp_admin_script',
                    $csp_prefix . "'self' 'unsafe-eval'" . $csp_suffix,
                    'string',
                    'CSP script-src directive',
                    true,
                    true
                );
                $blog_settings->system->put(
                    'csp_admin_style',
                    $csp_prefix . "'self' 'unsafe-inline'" . $csp_suffix,
                    'string',
                    'CSP style-src directive',
                    true,
                    true
                );
                $blog_settings->system->put(
                    'csp_admin_img',
                    $csp_prefix . "'self' data: https://media.dotaddict.org blob:",
                    'string',
                    'CSP img-src directive',
                    true,
                    true
                );

                # Add Dotclear version
                $cur          = App::version()->openVersionCursor();
                $cur->module  = 'core';
                $cur->version = App::config()->dotclearVersion();
                $cur->insert();

                # Create first post
                App::blog()->loadFromBlog('default');

                $cur               = App::blog()->openPostCursor();
                $cur->user_id      = self::$u_login;
                $cur->post_format  = 'xhtml';
                $cur->post_lang    = self::$dlang;
                $cur->post_title   = __('Welcome to Dotclear!');
                $cur->post_content = '<p>' . __('This is your first entry. When you\'re ready ' .
                    'to blog, log in to edit or delete it.') . '</p>';
                $cur->post_content_xhtml = $cur->post_content;
                $cur->post_status        = App::blog()::POST_PUBLISHED;
                $cur->post_open_comment  = 1;
                $cur->post_open_tb       = 0;
                $post_id                 = App::blog()->addPost($cur);

                # Add a comment to it
                $cur                  = App::blog()->openCommentCursor();
                $cur->post_id         = $post_id;
                $cur->comment_tz      = $default_tz;
                $cur->comment_author  = __('Dotclear Team');
                $cur->comment_email   = 'contact@dotclear.net';
                $cur->comment_site    = 'https://dotclear.org/';
                $cur->comment_content = __("<p>This is a comment.</p>\n<p>To delete it, log in and " .
                    "view your blog's comments. Then you might remove or edit it.</p>");
                App::blog()->addComment($cur);

                #  Plugins initialization
                App::task()->addContext('BACKEND');
                App::plugins()->loadModules(App::config()->pluginsRoot());
                self::$plugins_install = App::plugins()->installModules();

                # Add dashboard module options
                App::auth()->prefs()->dashboard->put('doclinks', true, 'boolean', '', false, true);
                App::auth()->prefs()->dashboard->put('donate', true, 'boolean', '', false, true);
                App::auth()->prefs()->dashboard->put('dcnews', true, 'boolean', '', false, true);
                App::auth()->prefs()->dashboard->put('quickentry', true, 'boolean', '', false, true);
                App::auth()->prefs()->dashboard->put('nodcupdate', false, 'boolean', '', false, true);

                # Add accessibility options
                App::auth()->prefs()->accessibility->put('nodragdrop', false, 'boolean', '', false, true);

                # Add user interface options
                App::auth()->prefs()->interface->put('enhanceduploader', true, 'boolean', '', false, true);

                # Add default favorites
                $favs      = new Favorites();
                $init_favs = ['posts', 'new_post', 'newpage', 'comments', 'categories', 'media', 'blog_theme', 'widgets', 'simpleMenu', 'prefs', 'help'];
                $favs->setFavoriteIDs($init_favs, true);

                self::$step = 1;
            } catch (Exception $e) {
                self::$err = $e->getMessage();
            }
        }

        return true;
    }

    public static function render(): void
    {
        if (!self::status()) {
            throw new Exception('Not found', 404);
        }

        header('Content-Type: text/html; charset=UTF-8');

        // Prevents Clickjacking as far as possible
        header('X-Frame-Options: SAMEORIGIN'); // FF 3.6.9+ Chrome 4.1+ IE 8+ Safari 4+ Opera 10.5+

        ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="Content-Script-Type" content="text/javascript">
  <meta http-equiv="Content-Style-Type" content="text/css">
  <meta http-equiv="Content-Language" content="en">
  <meta name="ROBOTS" content="NOARCHIVE,NOINDEX,NOFOLLOW">
  <meta name="GOOGLEBOT" content="NOSNIPPET">
  <title><?= __('Dotclear Install') ?></title>

    <link rel="stylesheet" href="../style/install.css" type="text/css" media="screen">

          <?php
          echo
            Page::jsLoad('../js/prepend.js') .
            Page::jsJson('pwstrength', [
                'min' => sprintf(__('Password strength: %s'), __('weak')),
                'avg' => sprintf(__('Password strength: %s'), __('medium')),
                'max' => sprintf(__('Password strength: %s'), __('strong')),
            ]) .
            Page::jsLoad('../js/pwstrength.js') .
            Page::jsLoad('../js/jquery/jquery.js') .
            Page::jsJson('install_show', __('show')) .
            Page::jsLoad('../js/_install.js'); ?>
</head>

<body id="dotclear-admin" class="install">
<div id="content">
        <?php
        echo
        '<h1>' . __('Dotclear installation') . '</h1>' .
            '<div id="main">';

        if (!is_writable(App::config()->cacheRoot())) {
            echo '<div class="error" role="alert"><p>' . sprintf(__('Cache directory %s is not writable.'), App::config()->cacheRoot()) . '</p></div>';
        }

        if (self::$can_install && !empty(self::$err)) {
            echo '<div class="error" role="alert"><p><strong>' . __('Errors:') . '</strong></p>' . self::$err . '</div>';
        }

        if (!empty($_GET['wiz'])) {
            echo '<p class="success" role="alert">' . __('Configuration file has been successfully created.') . '</p>';
        }

        if (self::$can_install && self::$step == 0) {
            echo
            '<h2>' . __('User information') . '</h2>' .

            '<p>' . __('Please provide the following information needed to create the first user.') . '</p>' .

            '<form action="index.php" method="post">' .
            '<fieldset><legend>' . __('User information') . '</legend>' .
            '<p><label for="u_firstname">' . __('First Name:') . '</label> ' .
            form::field('u_firstname', 30, 255, [
                'default'      => Html::escapeHTML(self::$u_firstname),
                'autocomplete' => 'given-name',
            ]) .
            '</p>' .
            '<p><label for="u_name">' . __('Last Name:') . '</label> ' .
            form::field('u_name', 30, 255, [
                'default'      => Html::escapeHTML(self::$u_name),
                'autocomplete' => 'family-name',
            ]) .
            '</p>' .
            '<p><label for="u_email">' . __('Email:') . '</label> ' .
            form::email('u_email', [
                'size'         => 30,
                'default'      => Html::escapeHTML(self::$u_email),
                'autocomplete' => 'email',
            ]) .
            '</p>' .
            '</fieldset>' .

            '<fieldset><legend>' . __('Username and password') . '</legend>' .
            '<p class="form-note">' . sprintf(__('Fields preceded by %s are mandatory.'), '<span class="required">*</span>') . '</p>' .
            '<p><label for="u_login" class="required"><span>*</span> ' . __('Username:') . ' ' .
            form::field('u_login', 30, 32, [
                'default'      => Html::escapeHTML(self::$u_login),
                'extra_html'   => 'required placeholder="' . __('Username') . '"',
                'autocomplete' => 'username',
            ]) .
            '</label></p>' .
            '<p>' .
            '<label for="u_pwd" class="required"><span>*</span> ' . __('New password:') . '</label>' .
            form::password('u_pwd', 30, 255, [
                'class'        => 'pw-strength',
                'extra_html'   => 'data-indicator="pwindicator" required placeholder="' . __('Password') . '"',
                'autocomplete' => 'new-password',
            ]) .
            '</p>' .
            '<p><label for="u_pwd2" class="required"><span>*</span> ' . __('Confirm password:') . ' ' .
            form::password('u_pwd2', 30, 255, [
                'extra_html'   => 'required placeholder="' . __('Password') . '"',
                'autocomplete' => 'new-password',
            ]) .
            '</label></p>' .
            '</fieldset>' .

            '<p><input type="submit" value="' . __('Save') . '"></p>' .
                '</form>';
        } elseif (self::$can_install && self::$step == 1) {
            # Plugins install messages
            $plugins_install_result = '';
            if (!empty(self::$plugins_install['success'])) {
                $plugins_install_result .= '<div class="static-msg">' . __('Following plugins have been installed:') . '<ul>';
                foreach (array_keys(self::$plugins_install['success']) as $k) {
                    $plugins_install_result .= '<li>' . $k . '</li>';
                }
                $plugins_install_result .= '</ul></div>';
            }
            if (!empty(self::$plugins_install['failure'])) {
                $plugins_install_result .= '<div class="error">' . __('Following plugins have not been installed:') . '<ul>';
                foreach (self::$plugins_install['failure'] as $k => $v) {
                    $plugins_install_result .= '<li>' . $k . ' (' . $v . ')</li>';
                }
                $plugins_install_result .= '</ul></div>';
            }

            echo
            '<h2>' . __('All done!') . '</h2>' .

            $plugins_install_result .

            '<p class="success" role="alert">' . __('Dotclear has been successfully installed. Here is some useful information you should keep.') . '</p>' .

            '<h3>' . __('Your account') . '</h3>' .
            '<ul>' .
            '<li>' . __('Username:') . ' <strong>' . Html::escapeHTML(self::$u_login) . '</strong></li>' .
            '<li>' . __('Password:') . ' <strong id="password">' . Html::escapeHTML(self::$u_pwd) . '</strong></li>' .
            '</ul>' .

            '<h3>' . __('Your blog') . '</h3>' .
            '<ul>' .
            '<li>' . __('Blog address:') . ' <strong>' . Html::escapeHTML(Http::getHost() . self::$root_url) . '/index.php?</strong></li>' .
            '<li>' . __('Administration interface:') . ' <strong>' . Html::escapeHTML(Http::getHost() . self::$admin_url) . '</strong></li>' .
            '</ul>' .

            '<form action="../index.php" method="post">' .
            '<p><input type="submit" value="' . __('Manage your blog now') . '">' .
            form::hidden(['user_id'], Html::escapeHTML(self::$u_login)) .
            form::hidden(['user_pwd'], Html::escapeHTML(self::$u_pwd)) .
            form::hidden(['process'], 'Auth') .
                '</p>' .
                '</form>';
        } elseif (!self::$can_install) {
            echo '<h2>' . __('Installation can not be completed') . '</h2>' .
            '<div class="error" role="alert"><p><strong>' . __('Errors:') . '</strong></p>' . self::$err . '</div>' .
            '<p>' . __('For the said reasons, Dotclear can not be installed. ' .
                'Please refer to <a href="https://dotclear.org/documentation/2.0/admin/install">' .
                'the documentation</a> to learn how to correct the problem.') . '</p>';
        }
        ?>
</div>
</div>
</body>
</html>
<?php
    }
}
