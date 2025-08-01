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
use Dotclear\Helper\Html\Html;
use Dotclear\Helper\L10n;
use Dotclear\Helper\Network\Http;
use Dotclear\Helper\Text;
use Exception;
use form;

/**
 * @brief   Installation wizard process.
 *
 * @todo switch Helper/Html/Form/...
 */
class Wizard extends Process
{
    /**
     * Error description
     */
    private static string $err = '';

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
            self::$err = '<p>' . sprintf(__('Path <strong>%s</strong> is not writable.'), Path::real(dirname(App::config()->configPath()))) . '</p>' .
            '<p>' . sprintf(
                __('Dotclear installation wizard could not create configuration file for you. You must change folder right or create the <strong>config.php</strong> file manually, please refer to <a href="%s">the documentation</a> to learn how to do this.'),
                'https://dotclear.org/documentation/2.0/admin/install'
            ) . '</p>';
        }

        return self::status();
    }

    public static function process(): bool
    {
        if (!self::status()) {
            throw new Exception('Not found', 404);
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
                if (self::$DBDRIVER === 'sqlite' && !str_contains(self::$DBNAME, '/')) {
                    $sqlite_db_directory = dirname(App::config()->configPath()) . '/../db/';
                    Files::makeDir($sqlite_db_directory, true);

                    # Can we write sqlite_db_directory ?
                    if (!is_writable($sqlite_db_directory)) {
                        throw new Exception(sprintf(__('Cannot write "%s" directory.'), Path::real($sqlite_db_directory, false)));
                    }
                    self::$DBNAME = (string) Path::real($sqlite_db_directory . self::$DBNAME, false);
                    if (!file_exists(self::$DBNAME)) {
                        touch(self::$DBNAME);
                    }
                }

                # Tries to connect to database
                try {
                    $con = App::newConnectionFromValues(self::$DBDRIVER, self::$DBHOST, self::$DBNAME, self::$DBUSER, self::$DBPASSWORD);
                } catch (Exception $e) {
                    throw new Exception('<p>' . __($e->getMessage()) . '</p>', (int) $e->getCode(), $e);
                }

                # Checks system capabilites
                $_e = [];
                if (!Utils::check($con, $_e)) {
                    throw new Exception('<p>' . __('Dotclear cannot be installed.') . '</p><ul><li>' . implode('</li><li>', $_e) . '</li></ul>');
                }

                # Check if dotclear is already installed
                $schema = $con->schema();
                if (in_array(self::$DBPREFIX . 'version', $schema->getTables())) {
                    throw new Exception(__('Dotclear is already installed.'));
                }
                # Check master email
                if (!Text::isEmail(self::$ADMINMAILFROM)) {
                    throw new Exception(__('Master email is not valid.'));
                }

                # Does config.php.in exist?
                $config_in = App::config()->dotclearRoot() . '/inc/config.php.in';
                if (!is_file($config_in)) {
                    throw new Exception(sprintf(__('File %s does not exist.'), $config_in));
                }

                # Can we write config.php
                if (!is_writable(dirname(App::config()->configPath()))) {
                    throw new Exception(sprintf(__('Cannot write %s file.'), App::config()->configPath()));
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
                    throw new Exception(sprintf(__('Cannot write %s file.'), App::config()->configPath()));
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
<div id="content">
<?php
        echo
        '<h1>' . __('Dotclear installation wizard') . '</h1>' .
            '<div id="main">';

        if (self::$err !== '') {
            echo '<div class="error" role="alert"><p><strong>' . __('Errors:') . '</strong></p>' . self::$err . '</div>';
        } else {
            echo '<h2>' . __('Welcome') . '</h2>' .
            '<p>' . __('To complete your Dotclear installation and start writing on your blog, we just need to know how to access your database and who you are. Just fill this two steps wizard with this information and we will be done.') . '</p>' .
            '<p class="message"><strong>' . __('Attention:') . '</strong> ' .
            sprintf(
                __('this wizard may not function on every host. If it does not work for you, please refer to <a href="%s">the documentation</a> to learn how to create the <strong>config.php</strong> file manually.'),
                'https://dotclear.org/documentation/2.0/admin/install'
            ) . '</p>';
        }

        echo
        '<h2>' . __('System information') . '</h2>' .

        '<p>' . __('Please provide the following information needed to create your configuration file.') . '</p>' .

        '<form action="index.php" method="post">' .
        '<p class="form-note">' . sprintf(__('Fields preceded by %s are mandatory.'), '<span class="required">*</span>') . '</p>' .
        '<p><label class="required" for="DBDRIVER"><span>*</span> ' . __('Database type:') . '</label> ' .
        form::combo(
            'DBDRIVER',
            [
                __('MySQLi')              => 'mysqli',
                __('MySQLi (full UTF-8)') => 'mysqlimb4',
                __('PostgreSQL')          => 'pgsql',
                __('SQLite')              => 'sqlite', ],
            ['default' => self::$DBDRIVER, 'extra_html' => 'required placeholder="' . __('Driver') . '"']
        ) . '</p>' .
        '<p><label for="DBHOST">' . __('Database Host Name:') . '</label> ' .
        form::field('DBHOST', 30, 255, Html::escapeHTML(self::$DBHOST)) . '</p>' .
        '<p><label for="DBNAME">' . __('Database Name:') . '</label> ' .
        form::field('DBNAME', 30, 255, Html::escapeHTML(self::$DBNAME)) . '</p>' .
        '<p><label for="DBUSER">' . __('Database User Name:') . '</label> ' .
        form::field('DBUSER', 30, 255, Html::escapeHTML(self::$DBUSER)) . '</p>' .
        '<p><label for="DBPASSWORD">' . __('Database Password:') . '</label> ' .
        form::password('DBPASSWORD', 30, 255) . '</p>' .
        '<p><label for="DBPREFIX" class="required"><span>*</span> ' . __('Database Tables Prefix:') . '</label> ' .
        form::field('DBPREFIX', 30, 255, [
            'default'    => Html::escapeHTML(self::$DBPREFIX),
            'extra_html' => 'required placeholder="' . __('Prefix') . '"',
        ]) .
        '</p>' .
        '<p><label for="ADMINMAILFROM">' . __('Master Email: (used as sender for password recovery)') . '</label> ' .
        form::email('ADMINMAILFROM', [
            'size'         => 30,
            'default'      => Html::escapeHTML(self::$ADMINMAILFROM),
            'autocomplete' => 'email',
        ]) .
        '</p>' .

        '<p><input type="submit" value="' . __('Continue') . '"></p>' .
            '</form>';
        ?>
</div>
</div>
</body>
</html>
<?php
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
}
