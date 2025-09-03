<?php
/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */

namespace Dotclear\Process\Install;

use Dotclear\App;
use Dotclear\Core\Install\Utils;
use Dotclear\Core\Process;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\Html\Form\Div;
use Dotclear\Helper\Html\Form\Email;
use Dotclear\Helper\Html\Form\Form;
use Dotclear\Helper\Html\Form\Input;
use Dotclear\Helper\Html\Form\Label;
use Dotclear\Helper\Html\Form\Li;
use Dotclear\Helper\Html\Form\Note;
use Dotclear\Helper\Html\Form\Option;
use Dotclear\Helper\Html\Form\Para;
use Dotclear\Helper\Html\Form\Password;
use Dotclear\Helper\Html\Form\Select;
use Dotclear\Helper\Html\Form\Set;
use Dotclear\Helper\Html\Form\Span;
use Dotclear\Helper\Html\Form\Strong;
use Dotclear\Helper\Html\Form\Submit;
use Dotclear\Helper\Html\Form\Text;
use Dotclear\Helper\Html\Form\Ul;
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\L10n;
use Dotclear\Helper\Network\Http;
use Dotclear\Helper\Text as Htext;
use Exception;

/**
 * @brief   Installation wizard process.
 */
class Wizard extends Process
{
    /**
     * Error description
     */
    private static string $err = '';

    /**
     * Installation checking flag
     */
    private static bool $can_install = true;

    /**
     * Available database drivers.
     *
     * @var     list<Option>
     */
    private static array $drivers = [];

    /**
     * DB driver name
     */
    private static string $DBDRIVER = 'mysqli';

    /**
     * DB host
     */
    private static string $DBHOST = '';

    /**
     * DB name
     */
    private static string $DBNAME = '';

    /**
     * DB credentials username
     */
    private static string $DBUSER = '';

    /**
     * DB credentials password
     */
    private static string $DBPASSWORD = '';

    /**
     * DB tables prefix
     */
    private static string $DBPREFIX = 'dc_';

    /**
     * Admin email
     */
    private static string $ADMINMAILFROM = '';

    public static function init(): bool
    {
        if (!self::status(App::task()->checkContext('INSTALL') && App::config()->configPath() !== '')) {
            throw new Exception('Not found', 404);
        }

        // Loading locales for detected language
        $dlang = Http::getAcceptLanguage();
        if ($dlang !== 'en') {
            L10n::init($dlang);
            L10n::set(App::config()->dotclearRoot() . '/locales/' . $dlang . '/main');
        }

        if (!is_writable(dirname(App::config()->configPath()))) {
            self::$can_install = false;
            self::$err .= (new Set())
                ->items([
                    (new Text('p', sprintf(__('Path <strong>%s</strong> is not writable.'), Path::real(dirname(App::config()->configPath()))))),
                    (new Text('p', sprintf(
                        __('Dotclear installation wizard could not create configuration file for you. You must change folder right or create the <strong>config.php</strong> file manually, please refer to <a href="%s">the documentation</a> to learn how to do this.'),
                        'https://dotclear.org/documentation/2.0/admin/install'
                    ))),
                ])
                ->render();
        }

        foreach (App::db()->combo() as $key => $value) {
            self::$drivers[] = new Option($key, $value);
        }
        if (self::$drivers === []) {
            self::$can_install = false;
            self::$err .= (new Set())
                ->items([
                    new Text('p', __('There are no supported database driver.')),
                    (new Text('p', sprintf(
                        __('Dotclear installation wizard could not find database driver. You must change your PHP configuration to add a supported database handler please refer to <a href="%s">the documentation</a> to learn how to do this.'),
                        'https://dotclear.org/documentation/2.0/admin/install'
                    ))),
                ])
                ->render();
        }

        return self::status();
    }

    public static function process(): bool
    {
        if (!self::status()) {
            throw new Exception('Not found', 404);
        }

        if (!self::$can_install) {
            return true;
        }

        # Uses HTML form or server variables (ie docker)
        self::$DBDRIVER      = $_POST['DBDRIVER']      ?? $_SERVER['DC_DBDRIVER'] ?? 'mysqli';
        self::$DBHOST        = $_POST['DBHOST']        ?? $_SERVER['DC_DBHOST'] ?? '';
        self::$DBNAME        = $_POST['DBNAME']        ?? $_SERVER['DC_DBNAME'] ?? '';
        self::$DBUSER        = $_POST['DBUSER']        ?? $_SERVER['DC_DBUSER'] ?? '';
        self::$DBPASSWORD    = $_POST['DBPASSWORD']    ?? $_SERVER['DC_DBPASSWORD'] ?? '';
        self::$DBPREFIX      = $_POST['DBPREFIX']      ?? $_SERVER['DC_DBPREFIX'] ?? 'dc_';
        self::$ADMINMAILFROM = $_POST['ADMINMAILFROM'] ?? $_SERVER['DC_ADMINMAILFROM'] ?? '';

        if ($_POST !== [] || !empty($_SERVER['DC_DBDRIVER'])) {
            try {
                if (str_contains(self::$DBDRIVER, 'sqlite') && !str_contains(self::$DBNAME, '/')) {
                    if (self::$DBNAME === '') {
                        // create sqlite db name if not set
                        self::$DBNAME = date('YmdHi') . '.sqlite';
                    }
                    // create sqlite db dir if not set
                    $sqlite_db_directory = dirname(App::config()->configPath()) . '/../db/';
                    Files::makeDir($sqlite_db_directory, true);

                    # Can we write sqlite_db_directory ?
                    if (!is_writable($sqlite_db_directory)) {
                        self::throwString(sprintf(__('Cannot write "%s" directory.'), Path::real($sqlite_db_directory, false)));
                    }
                    self::$DBNAME = (string) Path::real($sqlite_db_directory . self::$DBNAME, false);
                    if (!file_exists(self::$DBNAME)) {
                        touch(self::$DBNAME);
                    }
                }

                # Tries to connect to database
                try {
                    $con = App::db()->con(self::$DBDRIVER, self::$DBHOST, self::$DBNAME, self::$DBUSER, self::$DBPASSWORD);
                } catch (Exception $e) {
                    self::throwString(__($e->getMessage()), (int) $e->getCode(), $e);
                }

                # Checks system capabilites
                $_e = [];
                if (!Utils::check($con, $_e)) {
                    throw new Exception(
                        (new Set())
                        ->items([
                            new Text('p', __('Dotclear cannot be installed.')),
                            (new Ul())
                                ->items(array_map(fn (string $e): Li => (new Li())->text($e), $_e)),
                        ])
                        ->render()
                    );
                }

                # Check if dotclear is already installed
                if (in_array(self::$DBPREFIX . 'version', $con->schema()->getTables())) {
                    self::throwString(__('Dotclear is already installed.'));
                }
                # Check master email
                if (!HText::isEmail(self::$ADMINMAILFROM)) {
                    self::throwString(__('Master email is not valid.'));
                }

                # Does config.php.in exist?
                $config_in = App::config()->dotclearRoot() . '/inc/config.php.in';
                if (!is_file($config_in)) {
                    self::throwString(sprintf(__('File %s does not exist.'), $config_in));
                }

                # Can we write config.php
                if (!is_writable(dirname(App::config()->configPath()))) {
                    self::throwString(sprintf(__('Cannot write %s file.'), App::config()->configPath()));
                }

                # Creates config.php file
                $admin_url   = preg_replace('%install/index.php$%', '', (string) $_SERVER['REQUEST_URI']);
                $admin_email = self::$ADMINMAILFROM ?: 'dotclear@' . $_SERVER['HTTP_HOST'];

                $full_conf = (string) file_get_contents($config_in);

                $full_conf = self::writeConfigValue('DC_DBDRIVER', self::$DBDRIVER, $full_conf);
                $full_conf = self::writeConfigValue('DC_DBHOST', self::$DBHOST, $full_conf);
                $full_conf = self::writeConfigValue('DC_DBUSER', self::$DBUSER, $full_conf);
                $full_conf = self::writeConfigValue('DC_DBPASSWORD', self::$DBPASSWORD, $full_conf);
                $full_conf = self::writeConfigValue('DC_DBNAME', self::$DBNAME, $full_conf);
                $full_conf = self::writeConfigValue('DC_DBPREFIX', self::$DBPREFIX, $full_conf);
                $full_conf = self::writeConfigValue('DC_ADMIN_URL', Http::getHost() . $admin_url, $full_conf);
                $full_conf = self::writeConfigValue('DC_ADMIN_MAILFROM', $admin_email, $full_conf);
                $full_conf = self::writeConfigValue('DC_MASTER_KEY', md5(uniqid()), $full_conf);

                # Fix path if config file has moved elsewhere and allow environment variables
                $full_conf = self::writeConfigValue('DC_PLUGINS_ROOT', App::config()->dotclearRoot() . '/plugins', $full_conf);
                if (!empty($_SERVER['DC_PLUGINS_ROOT']) && is_writable(dirname((string) $_SERVER['DC_PLUGINS_ROOT']))) {
                    $full_conf = self::writeConfigValue('DC_PLUGINS_ROOT', App::config()->dotclearRoot() . '/plugins' . PATH_SEPARATOR . $_SERVER['DC_PLUGINS_ROOT'], $full_conf);
                }
                $full_conf = self::writeConfigValue('DC_TPL_CACHE', App::config()->dotclearRoot() . '/cache', $full_conf);
                if (!empty($_SERVER['DC_TPL_CACHE']) && is_writable(dirname((string) $_SERVER['DC_TPL_CACHE']))) {
                    $full_conf = self::writeConfigValue('DC_TPL_CACHE', (string) $_SERVER['DC_TPL_CACHE'], $full_conf);
                }
                $full_conf = self::writeConfigValue('DC_VAR', App::config()->dotclearRoot() . '/var', $full_conf);
                if (!empty($_SERVER['DC_VAR']) && is_writable(dirname((string) $_SERVER['DC_VAR']))) {
                    $full_conf = self::writeConfigValue('DC_VAR', (string) $_SERVER['DC_VAR'], $full_conf);
                }

                $fp = @fopen(App::config()->configPath(), 'wb');
                if ($fp === false) {
                    self::throwString(sprintf(__('Cannot write %s file.'), App::config()->configPath()));
                }
                fwrite($fp, $full_conf);
                fclose($fp);

                if (function_exists('chmod')) {
                    try {
                        @chmod(App::config()->configPath(), 0o666);
                    } catch (Exception) {
                    }
                }

                $con->close();
                Http::redirect('index.php?wiz=1');
            } catch (Exception $e) {
                self::$err = $e->getMessage();
            }
        }

        return true;
    }

    public static function render(): void
    {
        if (App::config()->configPath() === '') {
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
  <title><?= __('Dotclear installation wizard') ?></title>
    <link rel="stylesheet" href="../style/install.css" type="text/css" media="screen">
</head>

<body id="dotclear-admin" class="install">
        <?php self::renderContent(); ?>
</body>
</html>
        <?php
    }

    /**
     * Render form content.
     */
    private static function renderContent(): void
    {
        $required = (new Span())->text('*')->render();

        if (!self::$can_install) {
            echo (new Div('content'))
                ->items([
                    (new Text('h1', __('Dotclear installation wizard'))),
                    (new Div('main'))
                        ->items([
                            (new Div())
                                ->class('error')
                                ->role('main')
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
                            new Text('p', __('Dotclear can not be installed.')),
                        ]),
                ])
                ->render();

            return;
        }

        echo (new Div('content'))
            ->items([
                (new Text('h1', __('Dotclear installation wizard'))),
                (new Div('main'))
                    ->items([...
                        (self::$err !== '' ? [
                            (new Div())
                                ->class('error')
                                ->role('main')
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
                        ] : [
                            (new Text('h2', __('Welcome'))),
                            (new Text('p', __('To complete your Dotclear installation and start writing on your blog, we just need to know how to access your database and who you are. Just fill this two steps wizard with this information and we will be done.'))),
                            (new Set())
                                ->items([
                                    (new Text('p'))
                                        ->class('message')
                                        ->items([
                                            new Strong(__('Attention:')),
                                            (new Text('', sprintf(
                                                __('this wizard may not function on every host. If it does not work for you, please refer to <a href="%s">the documentation</a> to learn how to create the <strong>config.php</strong> file manually.'),
                                                'https://dotclear.org/documentation/2.0/admin/install'
                                            ))),
                                        ]),
                                ]),
                        ]),
                        (new Text('h2', __('System information'))),
                        (new Text('p', __('Please provide the following information needed to create your configuration file.'))),
                        (new Form('install-form'))
                            ->method('post')
                            ->action('index.php')
                            ->fields([
                                (new Para())
                                    ->class('form-note')
                                    ->items([
                                        (new Note())
                                            ->text(sprintf(__('Fields preceded by %s are mandatory.'), '<span class="required">*</span>'))
                                            ->class('form-note'),
                                        (new Label($required . __('Database type:')))
                                            ->class('required')
                                            ->for('DBDRIVER'),
                                        (new Select('DBDRIVER'))
                                            ->items(self::$drivers)
                                            ->default(self::$DBDRIVER)
                                            ->extra('required placeholder="' . __('Driver') . '"'),
                                        (new Para())
                                            ->items([
                                                (new Input('DBHOST'))
                                                    ->size(30)
                                                    ->maxlength(255)
                                                    ->value(Html::escapeHTML(self::$DBHOST))
                                                    ->label(new Label(__('Database Host Name:'), Label::OUTSIDE_LABEL_BEFORE)),
                                            ]),
                                        (new Para())
                                            ->items([
                                                (new Input('DBNAME'))
                                                    ->size(30)
                                                    ->maxlength(255)
                                                    ->value(Html::escapeHTML(self::$DBNAME))
                                                    ->label(new Label(__('Database Name:'), Label::OUTSIDE_LABEL_BEFORE)),
                                            ]),
                                        (new Para())
                                            ->items([
                                                (new Input('DBUSER'))
                                                    ->size(30)
                                                    ->maxlength(255)
                                                    ->value(Html::escapeHTML(self::$DBUSER))
                                                    ->label(new Label(__('Database User Name:'), Label::OUTSIDE_LABEL_BEFORE)),
                                            ]),
                                        (new Para())
                                            ->items([
                                                (new Password('DBPASSWORD'))
                                                    ->size(30)
                                                    ->maxlength(255)
                                                    ->value('')
                                                    ->label(new Label(__('Database Password:'), Label::OUTSIDE_LABEL_BEFORE)),
                                            ]),
                                        (new Para())
                                            ->items([
                                                (new Input('DBPREFIX'))
                                                    ->size(30)
                                                    ->maxlength(255)
                                                    ->value(Html::escapeHTML(self::$DBPREFIX))
                                                    ->extra('required placeholder="' . __('Prefix') . '"')
                                                    ->label((new Label($required . __('Database Tables Prefix:'), Label::OUTSIDE_LABEL_BEFORE))->class('required')),
                                            ]),
                                        (new Para())
                                            ->items([
                                                (new Email('ADMINMAILFROM'))
                                                    ->size(30)
                                                    ->autocomplete('email')
                                                    ->value(Html::escapeHTML(self::$ADMINMAILFROM))
                                                    ->extra('required placeholder="' . __('Email') . '"')
                                                    ->label(new Label($required . __('Master Email: (used as sender for password recovery)'), Label::OUTSIDE_LABEL_BEFORE)),
                                            ]),
                                        (new Submit('submit', __('Continue'))),
                                    ]),
                            ]),
                    ]),
            ])
            ->render();
    }

    /**
     * Writes a configuration value in configuration file content.
     *
     * @param      string  $name                The name
     * @param      string  $value               The value
     * @param      string  $config_content      The configuration file content
     *
     * @return     string  The new configuration file content
     */
    private static function writeConfigValue(string $name, string $value, string $config_content): string
    {
        $value = str_replace("'", "\'", $value);

        return (string) preg_replace('/(\'' . $name . '\')(.*?)$/ms', '$1,\'' . $value . '\');', $config_content);
    }

    /**
     * Parse error message.
     *
     * @param   string          $message    The message
     * @param   int             $code       The code
     * @param   null|Exception  $error      The error
     *
     * @throws  Exception
     *
     * @return  never
     */
    private static function throwString(string $message, int $code = 0, ?Exception $error = null): void
    {
        throw new Exception((new Text('p', $message))->render(), $code, $error);
    }
}
