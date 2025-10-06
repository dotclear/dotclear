<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Process\Install;

use Dotclear\App;
use Dotclear\Exception\ProcessException;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\Process\TraitProcess;
use Dotclear\Helper\Text;
use Dotclear\Schema\Schema;
use Exception;
use Throwable;

/**
 * @brief   CLI install process.
 *
 * There are additonnals queries 
 * as some server http var are not availables in CLI mode.
 *
 * Usage from Dotclear root, use shell: php admin/install/index.php --options...
 * * To list available options, use -h option
 * * To use non-interactive script, use -n option
 *
 * @since   2.36
 */
class Cli
{
    use TraitProcess;

    /**
     * @var     array<string, string>   $options
     */
    private static array $options = [];

    private static bool $interactive = false;

    public static function init(): bool
    {
        if (!self::status(App::task()->checkContext('INSTALL') && App::config()->cliMode())) {
            throw new ProcessException('Application is not in CLI mode', 550);
        }

        return self::status();
    }

    /**
     * @return  array<string, string>
     */
    public static function getArguments(): array
    {
        return [
            'dbdriver' => __('The database driver'),
            'dbhost' => __('The database host'),
            'dbname' => __('The database name'),
            'dbuser' => __('The database user'),
            'dbpassword' => __('The database password'),
            'dbprefix' => __('The database table prefix, can be empty for deault to _dc'),
            'adminemail' => __('The administration mail from'),
            'ufirstname' => __('The super administrator first name, can be empty'),
            'ulastname' => __('The super administrator last name, can be empty'),
            'uemail' => __('The super administrator email'),
            'ulogin' => __('The super administrator login'),
            'upassword' => __('The super administrator password'),
            'adminurl' => __('The admoin dashboard URL'),
            'blogurl' => __('The default blog URL'),
        ];
    }

    public static function render(): void
    {
        if (!self::status()) {
            return;
        }

        // Need to have output buffer for interactive commands
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        // Get options from arguments
        $options = getopt('h::n::', array_map(fn ($v): string => $v . '::', array_keys(self::getArguments())));
        if (is_array($options)) {
            self::$options = array_map(fn ($v): string => is_string($v) ? $v : '', $options);
        }

        // Set (non)intercative mode
        self::$interactive = !isset($options['n']) || $options['n'] !== false;

        // Show help
        if (isset($options['h']) && $options['h'] === false) {
            echo __('Command line options are:') . "\n" .
                '-h => ' . __('This help') . "\n" .
                '-n => ' . __('Disable interactive mode') . "\n" .
                implode('', array_map(fn ($k, $v): string => '--' . $k . ' => ' . $v . "\n", array_keys(self::getArguments()), array_values(self::getArguments()))) . "\n";
        }

        if (!App::config()->hasConfig()) {
            // First step
            if (self::$interactive) {
                echo __('Starting first step of Dotclear installation process.'). "\n";
            }

            // Parse configuration
            $dbdriver = self::parseDbDriver();
            if (str_contains($dbdriver, 'sqlite')) {
                $dbhost = $dbuser = $dbpassword = '';
                $dbname = self::parseDbPath();
            } else {
                $dbhost     = self::parseDbHost();
                $dbname     = self::parseDbName();
                $dbuser     = self::parseDbUser();
                $dbpassword = self::parseDbPassword();
            } 
            $dbprefix   = self::parseDbPrefix();
            $adminemail = self::parseAdminEmail();
            $adminurl   = self::parseAdminUrl();

            // Check configuration
            try {
                // Try to connect to database
                $con = App::db()->newCon($dbdriver, $dbhost, $dbname, $dbuser, $dbpassword);

                // Check system capabilites
                $_e = [];
                if (!App::install()->utils()->check($con, $_e)) {
                    throw new Exception(implode(', ', $_e));
                }

                // Check if dotclear is already installed
                if (in_array($dbprefix . 'version', $con->schema()->getTables())) {
                    throw new Exception(__('Dotclear is already installed.'));
                }

                // Does config.php.in exist?
                $config_in = App::config()->dotclearRoot() . '/inc/config.php.in';
                if (!is_file($config_in)) {
                    throw new Exception(sprintf(__('File %s does not exist.'), $config_in));
                }

                // Can we write config.php
                if (!is_writable(dirname(App::config()->configPath()))) {
                    throw new Exception(sprintf(__('Cannot write %s file.'), App::config()->configPath()));
                }

                // Write config file
                $full_conf = (string) file_get_contents($config_in);

                $full_conf = self::writeConfigValue('DC_DBDRIVER', $dbdriver, $full_conf);
                $full_conf = self::writeConfigValue('DC_DBHOST', $dbhost, $full_conf);
                $full_conf = self::writeConfigValue('DC_DBUSER', $dbuser, $full_conf);
                $full_conf = self::writeConfigValue('DC_DBPASSWORD', $dbpassword, $full_conf);
                $full_conf = self::writeConfigValue('DC_DBNAME', $dbname, $full_conf);
                $full_conf = self::writeConfigValue('DC_DBPREFIX', $dbprefix, $full_conf);
                $full_conf = self::writeConfigValue('DC_ADMIN_URL', $adminurl, $full_conf);
                $full_conf = self::writeConfigValue('DC_ADMIN_MAILFROM', $adminemail, $full_conf);
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

                // Success message
                if (self::$interactive) {
                    echo __('First step of Dotclear installation succeed.'). "\n" .
                        __('Re-run same script to process second step of Dotclear installation.'). "\n";
                }
            } catch (Exception $e) {
                throw $e;
            }
        } else {
            // Second step
            if (self::$interactive) {
                echo __('Starting second step of Dotclear installation process.'). "\n";
            }

            // Check current installation state
            if (App::config()->masterKey() === '') {
                throw new Exception(__('Please set a master key (DC_MASTER_KEY) in configuration file.'));
            }

            // Check if dotclear is already installed
            if (in_array(App::db()->con()->prefix() . App::blog()::POST_TABLE_NAME, App::db()->con()->schema()->getTables())) {
                throw new Exception(__('Dotclear is already installed.'));
            }

            // Check system capabilites
            $_e = [];
            if (!App::install()->utils()->check(App::db()->con(), $_e)) {
                throw new Exception(implode(', ', $_e));
            }

            // Parse configuration
            $ufirstname = self::parseUserFirstname();
            $ulastname  = self::parseUserLastname();
            $uemail     = self::parseUserEmail();
            $ulogin     = self::parseUserLogin();
            $upassword  = self::parseUserPassword();
            $ulang      = 'en';
            $utz        = 'Europe/London';
            $blogurl    = self::parseBlogUrl();

            try {
                // Create schema
                $_s = App::db()->structure();

                // Fill database structure
                Schema::fillStructure($_s);

                // Update database
                App::db()->structure()->synchronize($_s);

                // Create user
                $cur                 = App::db()->con()->openCursor(App::db()->con()->prefix() . App::auth()::USER_TABLE_NAME);
                $cur->user_id        = $ulogin;
                $cur->user_super     = 1;
                $cur->user_pwd       = App::auth()->crypt($upassword);
                $cur->user_name      = $ulastname;
                $cur->user_firstname = $ufirstname;
                $cur->user_email     = $uemail;
                $cur->user_lang      = $ulang;
                $cur->user_tz        = $utz;
                $cur->user_creadt    = date('Y-m-d H:i:s');
                $cur->user_upddt     = date('Y-m-d H:i:s');
                $cur->user_options   = serialize(App::users()->userDefaults());
                $cur->insert();

                App::auth()->checkUser($ulogin);

                // Create blog
                $cur            = App::blog()->openBlogCursor();
                $cur->blog_id   = 'default';
                $cur->blog_url  = $blogurl . '/index.php?';
                $cur->blog_name = __('My first blog');
                App::blogs()->addBlog($cur);

                
                // Create global blog settings
                App::blogs()->blogDefaults();

                $blog_settings = App::blogSettings()->createFromBlog('default');
                $blog_settings->system->put('blog_timezone', $utz);
                $blog_settings->system->put('lang', $ulang);
                $blog_settings->system->put('public_url', $blogurl . '/public');
                $blog_settings->system->put('themes_url', $blogurl . '/themes');

                // date and time formats
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

                // CSP directive (admin part)

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

                // Add Dotclear version
                $cur          = App::version()->openVersionCursor();
                $cur->module  = 'core';
                $cur->version = App::config()->dotclearVersion();
                $cur->insert();

                // Create first post
                App::blog()->loadFromBlog('default');

                $cur               = App::blog()->openPostCursor();
                $cur->user_id      = $ulogin;
                $cur->post_format  = 'xhtml';
                $cur->post_lang    = $ulang;
                $cur->post_title   = __('Welcome to Dotclear!');
                $cur->post_content = '<p>' . __('This is your first entry. When you\'re ready ' .
                    'to blog, log in to edit or delete it.') . '</p>';
                $cur->post_content_xhtml = $cur->post_content;
                $cur->post_status        = App::status()->post()::PUBLISHED;
                $cur->post_open_comment  = 1;
                $cur->post_open_tb       = 0;
                $post_id                 = App::blog()->addPost($cur);

                // Add a comment to it
                $cur                  = App::blog()->openCommentCursor();
                $cur->post_id         = $post_id;
                $cur->comment_tz      = $utz;
                $cur->comment_author  = __('Dotclear Team');
                $cur->comment_email   = 'contact@dotclear.org';
                $cur->comment_site    = 'https://dotclear.org/';
                $cur->comment_content = __("<p>This is a comment.</p>\n<p>To delete it, log in and " .
                    "view your blog's comments. Then you might remove or edit it.</p>");
                App::blog()->addComment($cur);

                // Plugins initialization
                App::task()->addContext('BACKEND');
                App::plugins()->loadModules(App::config()->pluginsRoot());
                $plugins_install = App::plugins()->installModules();


                // Add dashboard module options
                App::auth()->prefs()->dashboard->put('doclinks', true, 'boolean', '', false, true);
                App::auth()->prefs()->dashboard->put('donate', true, 'boolean', '', false, true);
                App::auth()->prefs()->dashboard->put('dcnews', true, 'boolean', '', false, true);
                App::auth()->prefs()->dashboard->put('quickentry', true, 'boolean', '', false, true);
                App::auth()->prefs()->dashboard->put('nodcupdate', false, 'boolean', '', false, true);

                // Add accessibility options
                App::auth()->prefs()->accessibility->put('nodragdrop', false, 'boolean', '', false, true);

                // Add user interface options
                App::auth()->prefs()->interface->put('enhanceduploader', true, 'boolean', '', false, true);

                // Add default favorites
                $init_favs = ['posts', 'new_post', 'newpage', 'comments', 'categories', 'media', 'blog_theme', 'widgets', 'simpleMenu', 'prefs', 'help'];
                App::install()->favorites()->setFavoriteIDs($init_favs, true);

                // Success message
                if (self::$interactive) {
                    echo __('Second step of Dotclear installation succeed.'). "\n" .
                        sprintf(__('Go to visit "%s" to manage your blog.'), App::config()->adminUrl()) . "\n";

                    if (!empty($plugins_install['failure'])) {
                        echo __('Following plugins have not been installed:') . ' ' . implode(', ', array_keys($plugins_install['failure']));
                    }
                }
            } catch (Exception $e) {
                throw $e;
            }
        }

        if (!self::$interactive) {
            echo '1';
        }
    }

    private static function parseDbDriver(): string
    {
        $in = self::cleanString(empty(self::$options['dbdriver']) ?
            self::inLine(sprintf(__('Configure the database driver (%s):'), implode(",", App::db()->combo()))) :
            self::$options['dbdriver']
        );

        if (empty($in)) {
            self::koLine(__('No database driver.'));
        } elseif (!in_array($in, App::db()->combo())) {
            self::koLine(__('Invalid database driver.'));
        } else {
            self::okLine(sprintf(__('Database driver is set to "%s".'), $in));
            self::$options['dbdriver'] = '';
            
            return $in;
        }

        return self::parseDbDriver();
    }

    private static function parseDbPath(): string
    {
        $in = self::cleanString(!isset(self::$options['dbname']) ?
            self::inLine(__('Configure the database path:')) :
            self::$options['dbname']
        );

        if (!str_contains($in, '/')) {
            if ($in === '') {
                // create sqlite db name if not set
                $in = date('YmdHi') . '.sqlite';
            }
            // Create sqlite db dir if not set
            $sqlite_db_directory = dirname(App::config()->configPath()) . '/../db/';
            Files::makeDir($sqlite_db_directory, true);

            // Can we write sqlite_db_directory ?
            if (!is_writable($sqlite_db_directory)) {
                throw new Exception(sprintf(__('Cannot write "%s" directory.'), Path::real($sqlite_db_directory, false)));
            }
            $in = (string) Path::real($sqlite_db_directory . $in, false);
            if (!file_exists($in)) {
                touch($in);
            }

            // Try to fix file right
            if (function_exists('chmod')) {
                try {
                    @chmod($in, 0o666);
                } catch (Exception) {
                }
            }
        }

        self::okLine(sprintf(__('Database path is set to "%s".'), $in));
        self::$options['dbname'] = '';
            
        return $in;
    }

    private static function parseDbHost(bool $loop = false): string
    {
        $in = self::cleanString($loop || empty(self::$options['dbhost']) ?
            self::inLine(__('Configure the database host:')) :
            self::$options['dbhost']
        );

        if (empty($in)) {
            self::koLine(__('No database host.'));
        } else {
            self::okLine(sprintf(__('Database host is set to "%s".'), $in));
            
            return $in;
        }

        return self::parseDbHost(true);
    }

    private static function parseDbName(bool $loop = false): string
    {
        $in = self::cleanString($loop || empty(self::$options['dbname']) ?
            self::inLine(__('Configure the database name:')) :
            self::$options['dbname']
        );

        if (empty($in)) {
            self::koLine(__('No database name.'));
        } else {
            self::okLine(sprintf(__('Database name is set to "%s".'), $in));
            
            return $in;
        }

        return self::parseDbName(true);
    }

    private static function parseDbUser(bool $loop = false): string
    {
        $in = self::cleanString($loop || empty(self::$options['dbuser']) ?
            self::inLine(__('Configure the database user: ')) :
            self::$options['dbuser']
        );

        if (empty($in)) {
            self::koLine(__('No database user.'));
        } else {
            self::okLine(sprintf(__('Database user is set to "%s"'), $in));
            
            return $in;
        }

        return self::parseDbUser(true);
    }

    private static function parseDbPassword(bool $loop = false): string
    {
        $in = self::cleanString($loop || empty(self::$options['dbpassword']) ?
            self::inLine(__('Configure the database password:')) :
            self::$options['dbpassword']
        );

        if (empty($in)) {
            self::koLine(__('No database password.'));
        } else {
            self::okLine(__('Database password is set.'));
            
            return $in;
        }

        return self::parseDbPassword(true);
    }

    private static function parseDbPrefix(bool $loop = false): string
    {
        $in = self::cleanString($loop || !isset(self::$options['dbprefix']) ?
            self::inLine(__('Configure the database table prefix:')) :
            self::$options['dbprefix']
        );

        if (empty($in)) {
            $in = 'dc_';
        }

        if (!preg_match('/^[A-Za-z0-9@._-]{2,}$/', $in)) {
            self::koLine(__('Invalid database prefix.'));
        } else {
            self::okLine(sprintf(__('Database table prefix is set to "%s".'), $in));
                
            return $in;
        }

        return self::parseDbPrefix(true);
    }

    private static function parseAdminEmail(bool $loop = false): string
    {
        $in = self::cleanString($loop || empty(self::$options['adminemail']) ?
            self::inLine(__('Configure the administration mail from:')) :
            self::$options['adminemail']
        );

        if (empty($in)) {
            self::koLine(__('No administration mail from.'));
        } elseif(!Text::isEmail($in)) {
            self::koLine(__('Invalid administration mail from.'));
        } else {
            self::okLine(sprintf(__('Administration mail from is set to "%s".'), $in));
            
            return $in;
        }

        return self::parseAdminEmail(true);
    }

    private static function parseAdminUrl(): string
    {
        $in = self::cleanString(empty(self::$options['adminurl']) ?
            self::inLine(__('Configure the administration full URL:')) :
            self::$options['adminurl']
        );

        if (empty($in)) {
            self::koLine(__('No administration URL.'));
        } elseif (!preg_match('#^http(s)?://#', $in)) {
            self::koLine(__('Invalid administration URL.'));
        } else {
            $in = (string) preg_replace('%/(index.php)?$%', '', $in);
            self::okLine(sprintf(__('Administration URL is set to "%s".'), $in));
            self::$options['adminurl'] = '';

            return $in;
        }

        return self::parseAdminUrl();
    }

    private static function parseUserFirstname(): string
    {
        $in = self::cleanString(!isset(self::$options['ufirstname']) ?
            self::inLine(__('Super administrator first name (optionnal):')) :
            self::$options['ufirstname']
        );

        self::okLine(sprintf(__('Super administrator first name is set to "%s".'), $in));

        return $in;
    }

    private static function parseUserLastname(): string
    {
        $in = self::cleanString(!isset(self::$options['ulastname']) ?
            self::inLine(__('Super administrator last name (optionnal):')) :
            self::$options['ulastname']
        );

        self::okLine(sprintf(__('Super administrator last name is set to "%s".'), $in));

        return $in;
    }

    private static function parseUserEmail(bool $loop = false): string
    {
        $in = self::cleanString($loop || empty(self::$options['uemail']) ?
            self::inLine(__('Configure the super administrator mail:')) :
            self::$options['uemail']
        );

        if (empty($in)) {
            self::koLine(__('No super administrator mail.'));
        } elseif(!Text::isEmail($in)) {
            self::koLine(__('Invalid super administrator mail.'));
        } else {
            self::okLine(sprintf(__('Super administrator mail is set to "%s".'), $in));

            return $in;
        }

        return self::parseUserEmail(true);
    }

    private static function parseUserLogin(bool $loop = false): string
    {
        $in = self::cleanString($loop || !isset(self::$options['ulogin']) ?
            self::inLine(__('Configure the super administrator login:')) :
            self::$options['ulogin']
        );

        if (empty($in)) {
            self::koLine(__('No super administrator login.'));
        } elseif (!preg_match('/^[A-Za-z0-9@._-]{2,}$/', $in)) {
            self::koLine(__('Super administrator login must contain at least 2 characters using letters, numbers or symbols.'));
        } else {
            self::okLine(sprintf(__('Super administrator login is set to "%s".'), $in));

            return $in;
        }

        return self::parseUserLogin(true);
    }

    private static function parseUserPassword(bool $loop = false): string
    {
        $in = self::cleanString($loop || empty(self::$options['upassword']) ?
            self::inLine(__('Configure the super administrator password: ')) :
            self::$options['upassword']
        );

        if (empty($in)) {
            self::koLine(__('No super administrator password.'));
        } elseif (strlen($in) < 6) {
            self::koLine(__('Password must contain at least 6 characters.'));
        } else {
            $retype = $in;
            if (empty(self::$options['upassword'])) { // Do not confirm password form command arguments
                $retype = self::inLine(__('Confirm the super administrator password:'));
                $retype = self::cleanString($retype);
            }
            if ($retype !== $in) {
                self::koLine(__("Passwords don't match"));
            } else {
                self::okLine(__('Super administrator password is set.'));

                return $in;
            }
        }

        return self::parseUserPassword(true);
    }

    private static function parseBlogUrl(): string
    {
        $in = self::cleanString(empty(self::$options['blogurl']) ?
            self::inLine(__('Configure the blog full URL:')) :
            self::$options['blogurl']
        );

        if (empty($in)) {
            self::koLine(__('No blog URL.'));
        } elseif (!preg_match('#^http(s)?://#', $in)) {
            self::koLine(__('Invalid blog URL.'));
        } else {
            $in  = (string) preg_replace('%/(index.php)?$%', '', $in);
            self::okLine(sprintf(__('Blog URL is set to "%s".'), $in));
            self::$options['blogurl'] = '';

            return $in;
        }

        return self::parseBlogUrl();
    }

    /**
     * Clean CLI response.
     */
    private static function cleanString(mixed $in): string
    {
        return trim((string) $in);
    }

    /**
     * CLI error line.
     *
     * @param   string  $text   The error text
     */
    private static function koLine(string $text): void
    {
        if (self::$interactive) {
            echo "[\033[31mKO\033[0m] " . $text . "\n";

            return;
        }

        throw new Exception($text);
    }

    /**
     * CLI success line.
     *
     * @param   string  $text   The success text
     */
    private static function okLine(string $text): void
    {
        if (self::$interactive) {
            echo "[\033[32mOK\033[0m] " . $text . "\n";
        }
    }

    /**
     * CLI query line.
     *
     * @param   string  $text   The query
     *
     * @return  mixed   The user response
     */
    private static function inLine(string $text): mixed
    {
        if (self::$interactive) {
            echo "[\033[33mIN\033[0m] " . $text . " ";

            return  fgets(STDIN);
        }

        return '';
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
