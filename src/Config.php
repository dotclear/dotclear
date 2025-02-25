<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear;

use Dotclear\Helper\Container\Factories;
use Dotclear\Helper\Crypt;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\File\Path;
use Dotclear\Helper\L10n;
use Dotclear\Helper\Network\Http;
use Dotclear\Exception\ConfigException;
use Dotclear\Interface\ConfigInterface;
use Dotclear\Interface\Core\AuthInterface;
use Dotclear\Interface\Core\ConnectionInterface;

/**
 * @brief   The helper to parse configuration values.
 *
 * Transitionnal class for DC_ constants.
 *
 * Simple write once properties for
 * unmutable configuration values.
 *
 * Methods are typed and return
 * also default values in same type.
 *
 * Class properties are readonly to prevent
 * modification from config file.
 *
 * Not yet in config:
 * * DC_FAIRTRACKBACKS_FORCE (plugin)
 * * DC_ANTISPAM_CONF_SUPER (plugin)
 * * DC_DNSBL_SUPER (plugin)
 * * DC_AKISMET_SUPER (plugin)
 * * HTTP_PROXY_HOST (Helper)
 * * HTTP_PROXY_PORT (Helper)
 * * SOCKET_VERIFY_PEER (Helper)
 *
 * @since   2.28
 */
class Config implements ConfigInterface
{
    /**
     * Dotclear default release config file name.
     *
     * @var    string   RELEASE_FILE
     */
    public const CONFIG_FILE = 'config.php';
    /**
     * Dotclear default release config file name.
     *
     * @var    string   RELEASE_FILE
     */
    public const RELEASE_FILE = 'release.json';
    /**
     * Dotclear default release config file name.
     *
     * @var    string   RELEASE_FILE
     */
    public const CSP_REPORT_FILE = 'csp_report.json';

    /**
     * Dotclear default release config.
     *
     * @var    array<string,mixed>  $release
     */
    private readonly array $release;

    // Configuration values, see ConfigInterface for details
    private readonly float $start_time;
    private readonly bool $cli_mode;
    private readonly bool $debug_mode;
    private readonly bool $dev_mode;
    private readonly string $error_file;
    private readonly string $blog_id;
    private readonly string $dotclear_version;
    private readonly string $dotclear_name;
    private readonly string $config_path;
    private readonly string $digests_root;
    private readonly string $l10n_root;
    private readonly string $l10n_update_url;
    private readonly string $distributed_plugins;
    private readonly string $distributed_themes;
    private readonly string $default_theme;
    private readonly string $default_tplset;
    private readonly string $default_jquery;
    private readonly string $min_required_php;
    private readonly string $min_required_mysql;
    private readonly string $min_required_pgsql;
    private readonly string $next_required_php;
    private readonly string $vendor_name;
    private readonly string $session_ttl;
    private readonly string $session_name;
    private readonly bool $admin_ssl;
    private readonly string $admin_url;
    private readonly string $admin_mailfrom;
    private readonly string $db_driver;
    private readonly string $db_host;
    private readonly string $db_user;
    private readonly string $db_password;
    private readonly string $db_name;
    private readonly string $db_prefix;
    private readonly bool $db_persist;
    private readonly string $master_key;
    private readonly string $crypt_algo;
    private readonly string $core_attic_url;
    private readonly string $core_update_url;
    private readonly string $core_update_canal;
    private readonly bool $core_not_update;
    private readonly bool $allow_multi_modules;
    private readonly bool $store_not_update;
    private readonly bool $allow_repositories;
    private readonly bool $allow_rest_services;
    private readonly string $cache_root;
    private readonly string $var_root;
    private readonly string $backup_root;
    private readonly string $core_upgrade;
    private readonly string $plugins_root;
    private readonly int $max_upload_size;
    private readonly int $query_timeout;
    private readonly ?int $query_stream_timeout;
    private readonly bool $show_hidden_dirs;
    private readonly bool $http_scheme_443;
    private readonly bool $http_revers_proxy;
    private readonly bool $check_ads_blocker;
    private readonly string $csp_report_file;
    private readonly bool $has_config;
    private readonly bool $dotclear_migrate;

    /**
     * Constructor.
     *
     * The constructor grabs all required Dotclear configuration values
     * from differents places like:
     * index files, config file, release file, PHP config, etc...
     *
     * @throws  ConfigException
     *
     * @param   string  $dotclear_root  Dotclear root directory path
     */
    public function __construct(
        private readonly string $dotclear_root
    ) {
        // From php
        $this->cli_mode = PHP_SAPI === 'cli';

        // From index file
        if (!defined('DC_BLOG_ID')) {
            define('DC_BLOG_ID', '');
        }

        $this->blog_id = (string) DC_BLOG_ID;

        // From release file
        $file    = $this->dotclearRoot() . DIRECTORY_SEPARATOR . self::RELEASE_FILE;
        $release = is_file($file) && is_readable($file) ? json_decode((string) file_get_contents($file), true) : null;

        $this->release = is_array($release) ? $release : [];

        $this->dotclear_version    = $this->release('release_version');
        $this->dotclear_name       = $this->release('release_name');
        $this->digests_root        = Path::reduce([$this->dotclearRoot(), 'inc', 'digests']);
        $this->l10n_root           = Path::reduce([$this->dotclearRoot(), 'locales']);
        $this->l10n_update_url     = $this->release('l10n_update_url');
        $this->distributed_plugins = $this->release('distributed_plugins');
        $this->distributed_themes  = $this->release('distributed_themes');
        $this->default_theme       = $this->release('default_theme');
        $this->default_tplset      = $this->release('default_tplset');
        $this->default_jquery      = $this->release('default_jquery');
        $this->min_required_php    = $this->release('php_min');
        $this->min_required_mysql  = $this->release('mysql_min');
        $this->min_required_pgsql  = $this->release('pgsql_min');

        // From config file
        $this->config_path = match (true) {
            isset($_SERVER['DC_RC_PATH'])          => $_SERVER['DC_RC_PATH'],
            isset($_SERVER['REDIRECT_DC_RC_PATH']) => $_SERVER['REDIRECT_DC_RC_PATH'],
            default                                => implode(DIRECTORY_SEPARATOR, [$this->dotclearRoot(), 'inc', self::CONFIG_FILE]),
        };

        $this->has_config = is_file($this->configPath());

        // Store upload_max_filesize in bytes
        $u_max_size = Files::str2bytes((string) ini_get('upload_max_filesize'));
        $p_max_size = Files::str2bytes((string) ini_get('post_max_size'));
        if ($p_max_size < $u_max_size) {
            $u_max_size = $p_max_size;
        }
        $this->max_upload_size = (int) $u_max_size;
        unset($u_max_size, $p_max_size);

        // We need l10n __() function and a default language if possible (for pre Utility exceptions)
        L10n::bootstrap();
        $detected_languages = Http::getAcceptLanguages();
        foreach ($detected_languages as $language) {
            if ($language === 'en' || L10n::set(implode(DIRECTORY_SEPARATOR, [$this->l10nRoot(), $language, 'exception']))) {
                L10n::lang($language);

                break;
            }
        }
        unset($detected_languages, $language);

        // Constants that can be used in config.php file
        define('DC_ROOT', $this->dotclearRoot());
        define('CLI_MODE', $this->cliMode());
        define('DC_VERSION', $this->dotclearVersion());
        define('DC_NAME', $this->dotclearName());
        define('DC_RC_PATH', $this->configPath());
        define('DC_DIGESTS', $this->digestsRoot());
        define('DC_L10N_ROOT', $this->l10nRoot());
        define('DC_L10N_UPDATE_URL', $this->l10nUpdateUrl());
        define('DC_DISTRIB_PLUGINS', $this->distributedPlugins());
        define('DC_DISTRIB_THEMES', $this->distributedThemes());
        define('DC_DEFAULT_THEME', $this->defaultTheme());
        define('DC_DEFAULT_TPLSET', $this->defaultTplset());
        define('DC_DEFAULT_JQUERY', $this->defaultJQuery());
        define('DC_MAX_UPLOAD_SIZE', $this->maxUploadSize());

        // Load config file
        if ($this->hasConfig()) {
            // path::real() may be used in inc/config.php
            if (!class_exists('path')) {
                class_alias(Path::class, 'path');
            }

            require $this->configPath();
        }

        // Constants that can be set in config.php file

        if (!defined('DC_DEBUG')) {
            define('DC_DEBUG', false);
        } elseif (DC_DEBUG) {
            ini_set('display_errors', '1');
            error_reporting(E_ALL);
        }

        if (!defined('DC_DEV')) {
            define('DC_DEV', false);
        }

        if (!defined('DC_ERRORFILE')) {
            define('DC_ERRORFILE', '');
        }

        if (!defined('DC_MASTER_KEY')) {
            define('DC_MASTER_KEY', '');
        }

        if (!defined('DC_NEXT_REQUIRED_PHP')) {
            define('DC_NEXT_REQUIRED_PHP', $this->release('next_php'));
        }

        if (!defined('DC_VENDOR_NAME')) {
            define('DC_VENDOR_NAME', 'Dotclear');
        }

        if (!defined('DC_SESSION_TTL')) {
            define('DC_SESSION_TTL', null);
        }

        if (!defined('DC_SESSION_NAME')) {
            define('DC_SESSION_NAME', 'dcxd');
        }

        if (!defined('DC_ADMIN_SSL')) {
            define('DC_ADMIN_SSL', false);
        }

        if (!defined('DC_ADMIN_URL')) {
            define('DC_ADMIN_URL', '');
        }

        if (!defined('DC_ADMIN_MAILFROM')) {
            define('DC_ADMIN_MAILFROM', 'dotclear@local');
        }

        if (!defined('DC_FORCE_SCHEME_443')) {
            define('DC_FORCE_SCHEME_443', false);
        }

        if (!defined('DC_REVERSE_PROXY')) {
            define('DC_REVERSE_PROXY', false);
        }

        if (!defined('DC_DBDRIVER')) {
            define('DC_DBDRIVER', '');
        }

        if (!defined('DC_DBHOST')) {
            define('DC_DBHOST', '');
        }

        if (!defined('DC_DBUSER')) {
            define('DC_DBUSER', '');
        }

        if (!defined('DC_DBPASSWORD')) {
            define('DC_DBPASSWORD', '');
        }

        if (!defined('DC_DBNAME')) {
            define('DC_DBNAME', '');
        }

        if (!defined('DC_DBPREFIX')) {
            define('DC_DBPREFIX', '');
        }

        if (!defined('DC_DBPERSIST')) {
            define('DC_DBPERSIST', false);
        }

        if (!defined('DC_PLUGINS_ROOT')) {
            define('DC_PLUGINS_ROOT', '');
        }

        if (!defined('DC_UPDATE_URL')) {
            define('DC_UPDATE_URL', $this->release('release_update_url'));
        }

        if (!defined('DC_ATTIC_URL')) {
            define('DC_ATTIC_URL', $this->release('release_previous_versions'));
        }

        if (!defined('DC_UPDATE_VERSION')) {
            define('DC_UPDATE_VERSION', $this->release('release_update_canal'));
        }

        if (!defined('DC_NOT_UPDATE')) {
            define('DC_NOT_UPDATE', false);
        }

        if (!defined('DC_ALLOW_MULTI_MODULES')) {
            define('DC_ALLOW_MULTI_MODULES', false);
        }

        if (!defined('DC_STORE_NOT_UPDATE')) {
            define('DC_STORE_NOT_UPDATE', false);
        }

        if (!defined('DC_REST_SERVICES')) {
            define('DC_REST_SERVICES', true);
        }

        if (!defined('DC_ALLOW_REPOSITORIES')) {
            define('DC_ALLOW_REPOSITORIES', true);
        }

        if (!defined('DC_QUERY_TIMEOUT')) {
            define('DC_QUERY_TIMEOUT', 4);
        }

        if (!defined('DC_QUERY_STREAM_TIMEOUT')) {
            define('DC_QUERY_STREAM_TIMEOUT', null);
        }

        if (!defined('DC_SHOW_HIDDEN_DIRS')) {
            define('DC_SHOW_HIDDEN_DIRS', false);
        }

        if (!defined('DC_CRYPT_ALGO')) {
            define('DC_CRYPT_ALGO', 'sha1'); // As in Dotclear 2.9 and previous
        }

        if (!defined('DC_TPL_CACHE')) {
            define('DC_TPL_CACHE', Path::reduce([$this->dotclearRoot(), 'cache']));
        }

        if (!defined('DC_VAR')) {
            define('DC_VAR', Path::reduce([$this->dotclearRoot(), 'var']));
        }

        if (!defined('DC_UPGRADE')) {
            define('DC_UPGRADE', Path::reduce([$this->dotclearRoot(), 'inc', 'upgrade']));
        }

        if (!defined('DC_BACKUP_PATH')) {
            define('DC_BACKUP_PATH', $this->dotclearRoot());
        }

        if (!defined('DC_START_TIME')) {
            define('DC_START_TIME', microtime(true));
        }

        if (!defined('DC_ADBLOCKER_CHECK')) {
            define('DC_ADBLOCKER_CHECK', true);
        }

        if (!defined('DC_MIGRATE')) {
            define('DC_MIGRATE', $this->release('dotclear_migrate'));
        }

        $this->debug_mode           = DC_DEBUG;
        $this->dev_mode             = DC_DEV;
        $this->error_file           = (string) DC_ERRORFILE;
        $this->master_key           = (string) DC_MASTER_KEY;
        $this->next_required_php    = (string) DC_NEXT_REQUIRED_PHP;
        $this->vendor_name          = (string) DC_VENDOR_NAME;
        $this->session_ttl          = (string) (DC_SESSION_TTL ?? '-120 minutes');
        $this->session_name         = (string) DC_SESSION_NAME;
        $this->admin_ssl            = DC_ADMIN_SSL;
        $this->admin_url            = (string) DC_ADMIN_URL;
        $this->admin_mailfrom       = (string) DC_ADMIN_MAILFROM;
        $this->db_driver            = (string) DC_DBDRIVER;
        $this->db_host              = (string) DC_DBHOST;
        $this->db_user              = (string) DC_DBUSER;
        $this->db_password          = (string) DC_DBPASSWORD;
        $this->db_name              = (string) DC_DBNAME;
        $this->db_prefix            = (string) DC_DBPREFIX;
        $this->db_persist           = DC_DBPERSIST;
        $this->plugins_root         = (string) DC_PLUGINS_ROOT;
        $this->core_attic_url       = (string) DC_ATTIC_URL;
        $this->core_update_url      = (string) DC_UPDATE_URL;
        $this->core_update_canal    = (string) DC_UPDATE_VERSION;
        $this->core_not_update      = (bool) DC_NOT_UPDATE;
        $this->allow_multi_modules  = (bool) DC_ALLOW_MULTI_MODULES;
        $this->store_not_update     = (bool) DC_STORE_NOT_UPDATE;
        $this->allow_rest_services  = DC_REST_SERVICES;
        $this->allow_repositories   = DC_ALLOW_REPOSITORIES;
        $this->query_timeout        = DC_QUERY_TIMEOUT;
        $this->query_stream_timeout = DC_QUERY_STREAM_TIMEOUT ?? null;
        $this->show_hidden_dirs     = DC_SHOW_HIDDEN_DIRS;
        $this->crypt_algo           = (string) DC_CRYPT_ALGO;
        $this->cache_root           = DC_TPL_CACHE;
        $this->var_root             = DC_VAR;
        $this->backup_root          = (string) DC_BACKUP_PATH;
        $this->core_upgrade         = (string) DC_UPGRADE;
        $this->start_time           = DC_START_TIME;
        $this->http_scheme_443      = (bool) DC_FORCE_SCHEME_443;
        $this->http_revers_proxy    = (bool) DC_REVERSE_PROXY;
        $this->check_ads_blocker    = (bool) DC_ADBLOCKER_CHECK;
        $this->dotclear_migrate     = (bool) DC_MIGRATE;

        // Various
        if (!defined('DC_CSP_LOGFILE')) {
            define('DC_CSP_LOGFILE', Path::reduce([$this->varRoot(), 'csp', self::CSP_REPORT_FILE]));
        }
        $this->csp_report_file = (string) DC_CSP_LOGFILE;

        // Set config for Exception handler
        Fault::$config = $this;

        // No release file
        if ($this->dotclearVersion() === '') {
            throw new ConfigException(__('Dotclear release file is not readable'));
        }

        // Deprecated since 2.28, for backward compatibility, override core authentication class with third party class
        if (defined('DC_AUTH_CLASS') && is_string(DC_AUTH_CLASS) && is_subclass_of(DC_AUTH_CLASS, AuthInterface::class)) {
            Factories::addService('core', AuthInterface::class, DC_AUTH_CLASS);
        }

        // Deprecated since 2.28, for backward compatibility, override core connection class with third party class
        if (defined('DC_DBHANDLER_CLASS') && is_string(DC_DBHANDLER_CLASS) && is_subclass_of(DC_DBHANDLER_CLASS, ConnectionInterface::class)) {
            Factories::addService('core', ConnectionInterface::class, DC_DBHANDLER_CLASS);
        }

        // Deprecated since 2.28, DC_DBSCHEMA_CLASS is no more used, database Schema class MUST be provided by Connection class method schema()
        //if (defined('DC_DBSCHEMA_CLASS')) {
        //    throw new ConfigException('Database Schema class MUST be provided by Connection class method schema().');
        //}

        // No config file and not in install process
        if (!$this->hasConfig()) {
            // Do not process install on CLI mode
            if ($this->cliMode()) {
                throw new ConfigException(__('Dotclear is not installed or failed to load config file.'));
            }

            // Stop configuration here on install wizard (App class takes the rest)
            return;
        }

        // Check length of cryptographic algorithm result and exit if less than 40 characters long
        if (strlen(Crypt::hmac($this->masterKey(), $this->vendorName(), $this->cryptAlgo())) < 40) {
            throw new ConfigException(sprintf(__('%s cryptographic algorithm configured is not strong enough, please change it.'), $this->cryptAlgo()));
        }

        // Check existence of cache directory
        if (!is_dir($this->cacheRoot())) {
            // Try to create it
            @Files::makeDir($this->cacheRoot());
            if (!is_dir($this->cacheRoot())) {
                throw new ConfigException(sprintf(__('%s directory does not exist. Please create it.'), $this->cacheRoot()));
            }
        }

        // Check existence of var directory
        if (!is_dir($this->varRoot())) {
            // Try to create it
            @Files::makeDir($this->varRoot());
            if (!is_dir($this->varRoot())) {
                throw new ConfigException(sprintf(__('%s directory does not exist. Please create it.'), $this->varRoot()));
            }
        }
    }

    public function release(string $key): string
    {
        // Release key not found
        if (!array_key_exists($key, $this->release)) {
            throw new ConfigException(sprintf(__('Dotclear release key %s was not found'), $key));
        }

        // Return casted release key value
        return is_array($this->release[$key]) ? implode(',', $this->release[$key]) : (string) $this->release[$key];
    }

    public function startTime(): float
    {
        return $this->start_time;
    }

    public function cliMode(): bool
    {
        return $this->cli_mode;
    }

    public function debugMode(): bool
    {
        return $this->debug_mode;
    }

    public function devMode(): bool
    {
        return $this->dev_mode;
    }

    public function errorFile(): string
    {
        return $this->error_file;
    }

    public function blogId(): string
    {
        return $this->blog_id;
    }

    public function dotclearRoot(): string
    {
        return $this->dotclear_root;
    }

    public function dotclearVersion(): string
    {
        return $this->dotclear_version;
    }

    public function dotclearName(): string
    {
        return $this->dotclear_name;
    }

    public function hasConfig(): bool
    {
        return $this->has_config;
    }

    public function configPath(): string
    {
        return $this->config_path;
    }

    public function digestsRoot(): string
    {
        return $this->digests_root;
    }

    public function l10nRoot(): string
    {
        return $this->l10n_root;
    }

    public function l10nUpdateUrl(): string
    {
        return $this->l10n_update_url;
    }

    public function distributedPlugins(): string
    {
        return $this->distributed_plugins;
    }

    public function distributedThemes(): string
    {
        return $this->distributed_themes;
    }

    public function defaultTheme(): string
    {
        return $this->default_theme;
    }

    public function defaultTplset(): string
    {
        return $this->default_tplset;
    }

    public function defaultJQuery(): string
    {
        return $this->default_jquery;
    }

    public function minRequiredPhp(): string
    {
        return $this->min_required_php;
    }

    public function minRequiredMysql(): string
    {
        return $this->min_required_mysql;
    }

    public function minRequiredPgsql(): string
    {
        return $this->min_required_pgsql;
    }

    public function nextRequiredPhp(): string
    {
        return $this->next_required_php;
    }

    public function vendorName(): string
    {
        return $this->vendor_name;
    }

    public function sessionTtl(): ?string
    {
        return $this->session_ttl;
    }

    public function sessionName(): string
    {
        return $this->session_name;
    }

    public function adminSsl(): bool
    {
        return $this->admin_ssl;
    }

    public function adminMailfrom(): string
    {
        return $this->admin_mailfrom;
    }

    public function adminUrl(): string
    {
        return $this->admin_url;
    }

    public function dbDriver(): string
    {
        return $this->db_driver;
    }

    public function dbHost(): string
    {
        return $this->db_host;
    }

    public function dbUser(): string
    {
        return $this->db_user;
    }

    public function dbPassword(): string
    {
        return $this->db_password;
    }

    public function dbName(): string
    {
        return $this->db_name;
    }

    public function dbPrefix(): string
    {
        return $this->db_prefix;
    }

    public function dbPersist(): bool
    {
        return $this->db_persist;
    }

    public function masterKey(): string
    {
        return $this->master_key;
    }

    public function cryptAlgo(): string
    {
        return $this->crypt_algo;
    }

    public function coreAtticUrl(): string
    {
        return $this->core_attic_url;
    }

    public function coreUpdateUrl(): string
    {
        return $this->core_update_url;
    }

    public function coreUpdateCanal(): string
    {
        return $this->core_update_canal;
    }

    public function coreNotUpdate(): bool
    {
        return $this->core_not_update;
    }

    public function allowMultiModules(): bool
    {
        return $this->allow_multi_modules;
    }

    public function storeNotUpdate(): bool
    {
        return $this->store_not_update;
    }

    public function allowRepositories(): bool
    {
        return $this->allow_repositories;
    }

    public function allowRestServices(): bool
    {
        return $this->allow_rest_services;
    }

    public function cacheRoot(): string
    {
        return $this->cache_root;
    }

    public function varRoot(): string
    {
        return $this->var_root;
    }

    public function backupRoot(): string
    {
        return $this->backup_root;
    }

    public function pluginsRoot(): string
    {
        return $this->plugins_root;
    }

    public function coreUpgrade(): string
    {
        return $this->core_upgrade;
    }

    public function maxUploadSize(): int
    {
        return $this->max_upload_size;
    }

    public function queryTimeout(): int
    {
        return $this->query_timeout;
    }

    public function queryStreamTimeout(): ?int
    {
        return $this->query_stream_timeout;
    }

    public function showHiddenDirs(): bool
    {
        return $this->show_hidden_dirs;
    }

    public function httpScheme443(): bool
    {
        return $this->http_scheme_443;
    }

    public function httpReverseProxy(): bool
    {
        return $this->http_revers_proxy;
    }

    public function checkAdsBlocker(): bool
    {
        return $this->check_ads_blocker;
    }

    public function cspReportFile(): string
    {
        return $this->csp_report_file;
    }

    public function dotclearMigrate(): bool
    {
        return $this->dotclear_migrate;
    }
}
