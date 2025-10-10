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
use Dotclear\Exception\NotFoundException;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Email;
use Dotclear\Helper\Html\Form\Fieldset;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Hidden;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Legend;
use Dotclear\Helper\Html\Form\Li;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Password;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Form\Span;
use Dotclear\Helper\Html\Form\Strong;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Ul;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\Network\Http;
use Dotclear\Helper\Text as Htext;
use Dotclear\Helper\Process\TraitProcess;
use Dotclear\Schema\Schema;
use Exception;

/**
 * @brief   Intallation process.
 */
class Install
{
    use TraitProcess;

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
     * @var        array<string, array<string, bool|string> >   $plugins_install
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
            throw new NotFoundException();
        }

        # Loading locales for detected language
        self::$dlang = Http::getAcceptLanguage();
        if (self::$dlang !== 'en') {
            App::lang()->init(self::$dlang);
            App::lang()->set(App::config()->l10nRoot() . '/' . self::$dlang . '/date');
            App::lang()->set(App::config()->l10nRoot() . '/' . self::$dlang . '/main');
            App::lang()->set(App::config()->l10nRoot() . '/' . self::$dlang . '/plugins');
        }

        if (App::config()->masterKey() === '') {
            self::$can_install = false;
            self::$err .= (new Text('p', __('Please set a master key (DC_MASTER_KEY) in configuration file.')))->render();
        }

        # Check if dotclear is already installed
        if (in_array(App::db()->con()->prefix() . App::blog()::POST_TABLE_NAME, App::db()->con()->schema()->getTables())) {
            self::$can_install = false;
            self::$err .= (new Text('p', __('Dotclear is already installed.')))->render();
        }

        # Check system capabilites
        $_e = [];
        if (!App::install()->utils()->check(App::db()->con(), $_e)) {
            self::$can_install = false;
            self::$err .= (new Set())
                ->items([
                    new Text('p', __('Dotclear cannot be installed.')),
                    (new Ul())
                        ->items(array_map(fn (string $v): Li => (new Li())->text($v), $_e)),
                ])
                ->render();
        }

        return self::status();
    }

    public static function process(): bool
    {
        if (!self::status()) {
            throw new NotFoundException();
        }

        if (self::$can_install && $_POST !== []) {
            self::$u_email     = $_POST['u_email']     ?? null;
            self::$u_firstname = $_POST['u_firstname'] ?? null;
            self::$u_name      = $_POST['u_name']      ?? null;
            self::$u_login     = $_POST['u_login']     ?? null;
            self::$u_pwd       = $_POST['u_pwd']       ?? null;
            self::$u_pwd2      = $_POST['u_pwd2']      ?? null;

            try {
                # Check user information
                if (!self::$u_login) {
                    throw new Exception(__('No user ID given'));
                }
                if (!preg_match('/^[A-Za-z0-9@._-]{2,}$/', self::$u_login)) {
                    throw new Exception(__('User ID must contain at least 2 characters using letters, numbers or symbols.'));
                }
                if (self::$u_email && !HText::isEmail(self::$u_email)) {
                    throw new Exception(__('Invalid email address'));
                }

                if (!self::$u_pwd) {
                    throw new Exception(__('No password given'));
                }
                if (self::$u_pwd != self::$u_pwd2) {
                    throw new Exception(__("Passwords don't match"));
                }
                if (strlen(self::$u_pwd) < 6) {
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
                $_s = App::db()->structure();

                # Fill database structure
                Schema::fillStructure($_s);

                # Update database
                App::db()->structure()->synchronize($_s);

                # Create user
                $cur                 = App::db()->con()->openCursor(App::db()->con()->prefix() . App::auth()::USER_TABLE_NAME);
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
                App::blogs()->blogDefaults();

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
                        fn (string $f): string => str_replace('%e', '%#d', $f),
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

                /* SQlite driver does not allow using single quote at beginning or end of a field value
                so we have to use neutral values (localhost and 127.0.0.1) for some CSP directives
                 */
                $csp_prefix = str_contains(App::db()->con()->driver(), 'sqlite') ? 'localhost ' : ''; // Hack for SQlite syntax
                $csp_suffix = str_contains(App::db()->con()->driver(), 'sqlite') ? ' 127.0.0.1' : ''; // Hack for SQlite syntax

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
                $cur->post_status        = App::status()->post()::PUBLISHED;
                $cur->post_open_comment  = 1;
                $cur->post_open_tb       = 0;
                $post_id                 = App::blog()->addPost($cur);

                # Add a comment to it
                $cur                  = App::blog()->openCommentCursor();
                $cur->post_id         = $post_id;
                $cur->comment_tz      = $default_tz;
                $cur->comment_author  = __('Dotclear Team');
                $cur->comment_email   = 'contact@dotclear.org';
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
                $init_favs = ['posts', 'new_post', 'newpage', 'comments', 'categories', 'media', 'blog_theme', 'widgets', 'simpleMenu', 'prefs', 'help'];
                App::install()->favorites()->setFavoriteIDs($init_favs, true);

                self::$step = 1;
            } catch (Exception $e) {
                self::$err .= (new Text('p', $e->getMessage()))->render();
            }
        }

        return true;
    }

    public static function render(): void
    {
        if (!self::status()) {
            throw new NotFoundException();
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
        <?php self::renderHeader(); ?>
</head>

<body id="dotclear-admin" class="install">
        <?php self::renderContent(); ?>
</body>
</html>
        <?php
    }

    /**
     * Render form headers.
     */
    private static function renderHeader(): void
    {
        echo
        App::install()->page()->jsLoad('../js/prepend.js') .
        App::install()->page()->jsJson('pwstrength', [
            'min' => sprintf(__('Password strength: %s'), __('weak')),
            'avg' => sprintf(__('Password strength: %s'), __('medium')),
            'max' => sprintf(__('Password strength: %s'), __('strong')),
        ]) .
        App::install()->page()->jsLoad('../js/pwstrength.js') .
        App::install()->page()->jsLoad('../js/jquery/jquery.js') .
        App::install()->page()->jsJson('install_show', __('show')) .
        App::install()->page()->jsLoad('../js/_install.js');
    }

    /**
     * Render form content.
     */
    private static function renderContent(): void
    {
        $required = (new Span())->text('*')->class('required')->render();

        $msg = [];
        if (!is_writable(App::config()->cacheRoot())) {
            $msg[] = (new Div())
                ->class('error')
                ->role('alert')
                ->items([
                    new Text('p', sprintf(__('Cache directory %s is not writable.'), App::config()->cacheRoot())),
                ]);
        }
        if (self::$can_install && self::$err !== '') {
            $msg[] = (new Div())
                ->class('error')
                ->role('alert')
                ->items([
                    (new Para())
                        ->items([
                            (new Text('p'))
                                ->items([
                                    new Strong(__('Errors:')),
                                ]),
                            new Text('', self::$err),
                        ]),
                ]);
        }
        if (!empty($_GET['wiz'])) {
            $msg[] = (new Text('p', __('Configuration file has been successfully created.')))
                ->class('success')
                ->role('alert');
        }
        if (self::$can_install && self::$step === 0) {
            $msg[] = (new Set())
                ->items([
                    new Text('h2', __('User information')),
                    new Text('p', __('Please provide the following information needed to create the first user.')),
                    (new Form('install-form'))
                        ->method('post')
                        ->action('index.php')
                        ->fields([
                            (new Fieldset())
                                ->legend(new Legend(__('User information')))
                                ->items([
                                    (new Para())
                                        ->items([
                                            (new Input('u_firstname'))
                                                ->size(30)
                                                ->maxlength(255)
                                                ->autocomplete('given-name')
                                                ->value(Html::escapeHTML(self::$u_firstname))
                                                ->label(new Label(__('First Name:'), Label::OUTSIDE_LABEL_BEFORE)),
                                        ]),
                                    (new Para())
                                        ->items([
                                            (new Input('u_name'))
                                                ->size(30)
                                                ->maxlength(255)
                                                ->autocomplete('family-name')
                                                ->value(Html::escapeHTML(self::$u_name))
                                                ->label(new Label(__('Last Name:'), Label::OUTSIDE_LABEL_BEFORE)),
                                        ]),
                                    (new Para())
                                        ->items([
                                            (new Email('u_email'))
                                                ->size(30)
                                                ->autocomplete('email')
                                                ->value(Html::escapeHTML(self::$u_email))
                                                ->label(new Label(__('Email:'), Label::OUTSIDE_LABEL_BEFORE)),
                                        ]),
                                ]),

                            (new Fieldset())
                                ->legend(new Legend(__('Username and password')))
                                ->items([
                                    (new Note())
                                        ->class('form-note')
                                        ->text(sprintf(__('Fields preceded by %s are mandatory.'), $required)),
                                    (new Para())
                                        ->items([
                                            (new Input('u_login'))
                                                ->size(30)
                                                ->maxlength(32)
                                                ->autocomplete('username')
                                                ->value(Html::escapeHTML(self::$u_login))
                                                ->label((new Label($required . __('Username:'), Label::OUTSIDE_LABEL_BEFORE))->class('required'))
                                                ->extra('required placeholder="' . __('Username') . '"'),
                                        ]),
                                    (new Para())
                                        ->items([
                                            (new Password('u_pwd'))
                                                ->class('pw-strength')
                                                ->size(30)
                                                ->maxlength(255)
                                                ->autocomplete('new-password')
                                                ->value('')
                                                ->label((new Label($required . __('New Password:'), Label::OUTSIDE_LABEL_BEFORE))->class('required'))
                                                ->extra('data-indicator="pwindicator" required placeholder="' . __('Password') . '"'),
                                        ]),
                                    (new Para())
                                        ->items([
                                            (new Password('u_pwd2'))
                                                ->size(30)
                                                ->maxlength(255)
                                                ->autocomplete('new-password')
                                                ->value('')
                                                ->label((new Label($required . __('Confirm Password:'), Label::OUTSIDE_LABEL_BEFORE))->class('required'))
                                                ->extra('required placeholder="' . __('Password') . '"'),
                                        ]),
                                ]),
                            (new Submit('install-submit', __('Save'))),
                        ]),
                ]);
        } elseif (self::$can_install && self::$step === 1) {
            # Plugins install messages
            $plugins_install_result = [];
            if (!empty(self::$plugins_install['success'])) {
                $plugins_install_result[] = (new Div())
                    ->class('static-msg')
                    ->items([
                        (new Text('', __('Following plugins have been installed:'))),
                        (new Ul())->items(array_map(fn (string $v): Li => (new Li())->text($v), array_keys(self::$plugins_install['success']))),
                    ]);
            }
            if (!empty(self::$plugins_install['failure'])) {
                $plugins_install_result[] = (new Div())
                    ->class('error')
                    ->items([
                        (new Text('', __('Following plugins have not been installed:'))),
                        (new Ul())->items(array_map(
                            fn ($k, $v): Li => (new Li())->text(sprintf('%s (%s)', $k, $v)),
                            array_keys(self::$plugins_install['failure']),
                            array_values(self::$plugins_install['failure'])
                        )),
                    ]);
            }

            $msg[] = (new Set())
                ->items([
                    (new Text('h2', __('All done!'))),
                    ... $plugins_install_result,
                    (new Text('p', __('Dotclear has been successfully installed. Here is some useful information you should keep.'))),
                    (new text('h3', __('Your account'))),
                    (new Ul())
                        ->items([
                            (new Li())
                                ->items([
                                    (new Text('', __('Username:'))),
                                    (new Strong(Html::escapeHTML(self::$u_login))),
                                ]),
                            (new Li())
                                ->items([
                                    (new Text('', __('Password:'))),
                                    ((new Strong(Html::escapeHTML(self::$u_pwd)))->id('password')),
                                ]),
                        ]),
                    (new text('h3', __('Your blog'))),
                    (new Ul())
                        ->items([
                            (new Li())
                                ->items([
                                    (new Text('', __('Blog address:'))),
                                    (new Strong(Html::escapeHTML(Http::getHost() . self::$root_url) . '/index.php?')),
                                ]),
                            (new Li())
                                ->items([
                                    (new Text('', __('Administration interface:'))),
                                    (new Strong(Html::escapeHTML(Http::getHost() . self::$admin_url))),
                                ]),
                        ]),
                    (new Form('success-form'))
                        ->method('post')
                        ->action('../index.php')
                        ->fields([
                            (new Para())
                                ->items([
                                    new Submit('success-submit', __('Manage your blog now')),
                                    new Hidden(['user_id'], Html::escapeHTML(self::$u_login)),
                                    new Hidden(['user_pwd'], Html::escapeHTML(self::$u_pwd)),
                                    new Hidden(['process'], 'Auth'),
                                ]),
                        ]),
                ]);
        } elseif (!self::$can_install) {
            $msg[] = (new Set())
                ->items([
                    (new Text('h2', __('Installation can not be completed'))),
                    (new Div())
                        ->class('error')
                        ->role('alert')
                        ->items([
                            (new Para())
                                ->items([
                                    (new Text('p'))
                                        ->items([
                                            new Strong(__('Errors:')),
                                        ]),
                                    new Text('', self::$err),
                                ]),
                        ]),
                    new Text('p', sprintf(
                        __('For the said reasons, Dotclear can not be installed. Please refer to <a href="%s">the documentation</a> to learn how to correct the problem.'),
                        'https://dotclear.org/documentation/2.0/admin/install'
                    )),
                ]);
        }

        echo (new Div('content'))
            ->items([
                new Text('h1', __('Dotclear installation')),
                (new Div('main'))
                    ->items($msg),
            ])
            ->render();
    }
}
