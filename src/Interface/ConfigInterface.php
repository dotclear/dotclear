<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

/**
 * @namespace   Dotclear.Interface
 * @brief       Dotclear application interfaces
 */

namespace Dotclear\Interface;

/**
 * @brief   Application Config handler interface.
 *
 * Simple write once container for
 * unmutable configuration values.
 *
 * All methods are typed and return
 * also default values in same type.
 *
 * @since   2.28
 */
interface ConfigInterface
{
    /**
     * Read Dotclear release config value.
     *
     * This method always returns string,
     * casting int, bool, array, to string.
     *
     * For default values from release file, use this method
     * else use App::config()->xxx() for values maybe
     * modified by config file.
     *
     * @param   string  $key The release key
     *
     * @return  string  The release value
     */
    public function release(string $key): string;

    /**
     * Application start time.
     *
     * From App.
     * Keep only the first timer start.
     *
     * @return  float   The start time
     */
    public function startTime(): float;

    /**
     * Does app run in CLI mode.
     *
     * From PHP.
     * Returns false if not set.
     *
     * @return  bool    True for CLI mode
     */
    public function cliMode(): bool;

    /**
     * Does app run in debug mode.
     *
     * From config file or canal.
     * Returns false if not set.
     *
     * @return  bool    True for debug mode
     */
    public function debugMode(): bool;

    /**
     * Does app run in dev mode.
     *
     * From config file.
     * Returns false if not set.
     *
     * @return  bool    True for dev mode
     */
    public function devMode(): bool;

    /**
     * Custom error file.
     *
     * This file is loaded before appllication
     * exception handler render the HTTP error page.
     *
     * @return  string  The custom error file path
     */
    public function errorFile(): string;

    /**
     * Blog ID requested from file.
     *
     * From index file.
     * Returns emtpy string if not set.
     *
     * @return  string  Blog ID
     */
    public function blogId(): string;

    /**
     * Root path directory.
     *
     * From App.
     * Returns emtpy string if not set.
     *
     * @return  string  Dotclear root
     */
    public function dotclearRoot(): string;

    /**
     * Release version.
     *
     * From release file.
     * Returns empty string if not set.
     *
     * @return  string  Dotclear version
     */
    public function dotclearVersion(): string;

    /**
     * Release name.
     *
     * From release file.
     * Returns empty string if not set.
     *
     * @return  string  Dotclear name
     */
    public function dotclearName(): string;

    /**
     * Check if an appalication config file exists
     *
     * @return  bool    True if exists
     */
    public function hasConfig(): bool;

    /**
     * Configuration file path.
     *
     * From server vars or construct in place.
     * Returns empty string if not set.
     *
     * @return  string  Dotclear config file path
     */
    public function configPath(): string;

    /**
     * Digests path.
     *
     * Construct in place.
     * Returns empty string if not set.
     *
     * @return  string  Dotclear digests path.
     */
    public function digestsRoot(): string;

    /**
     * Locales root path directory.
     *
     * Construct in place.
     * Returns empty string if not set.
     *
     * @return  string  l10n path
     */
    public function l10nRoot(): string;

    /**
     * Locales Update URL.
     *
     * From release file.
     * Retruns empty string if not set.
     *
     * @return  string  l10n update URL
     */
    public function l10nUpdateUrl(): string;

    /**
     * Distributed plugins list.
     *
     * From release file.
     * Returns comma separated list of distributed plugins
     * or empty string if not set.
     *
     * @return  string  Distributed plugins
     */
    public function distributedPlugins(): string;

    /**
     * Distributed themes list.
     *
     * From release file.
     * Returns comma separated list of distributed themes
     * or empty string if not set.
     *
     * @return  string  Distributed themes
     */
    public function distributedThemes(): string;

    /**
     * Default theme.
     *
     * From release file.
     * Returns empty string if not set.
     *
     * @return  string  Default theme
     */
    public function defaultTheme(): string;

    /**
     * Default templates set.
     *
     * From release file.
     * Returns empty string if not set.
     *
     * @return  string  Default templates set
     */
    public function defaultTplset(): string;

    /**
     * Default JQuery version.
     *
     * From release file.
     * Returns empty string if not set.
     *
     * @return  string  Default JQeury version
     */
    public function defaultJQuery(): string;

    /**
     * Minimum required PHP version.
     *
     * From release file.
     * Returns empty string if not set.
     *
     * @return  string  PHP min version
     */
    public function minRequiredPhp(): string;

    /**
     * Minimum required MySQL version.
     *
     * From release file.
     * Returns empty string if not set.
     *
     * @return  string  MySQL min version
     */
    public function minRequiredMysql(): string;

    /**
     * Minimum required PgSQL version.
     *
     * From release file.
     * Returns empty string if not set.
     *
     * @return  string  PgSQL min version
     */
    public function minRequiredPgsql(): string;

    /**
     * Next release required PHP version.
     *
     * From release file or anywhere.
     * Returns empty string if not set.
     *
     * @return  string  Next required PHP version
     */
    public function nextRequiredPhp(): string;

    /**
     * Multiblog vendor name.
     *
     * From config file or release file.
     * Returns 'Dotclear' string if not set.
     *
     * @return  string  Vendor name
     */
    public function vendorName(): string;

    /**
     * Session TTl (time to live).
     *
     * From config file.
     * Returns null if not set.
     *
     * @return  null|string     Session TTL
     */
    public function sessionTtl(): ?string;

    /**
     * Session name.
     *
     * From config file or construct in place.
     * Returns 'dcxd' string if not set.
     *
     * @return  string  Session name
     */
    public function sessionName(): string;

    /**
     * Does backend use SSL.
     *
     * From config file.
     * Returns false if not set.
     *
     * @return  bool    True for SSL mode
     */
    public function adminSsl(): bool;

    /**
     * Backend URL
     *
     * From config file.
     * Returns empty string if not set.
     *
     * @return  string  Admin URL
     */
    public function adminUrl(): string;

    /**
     * Admin mail from address.
     *
     * Used for password recovery and such.
     * From config file.
     * Returns 'dotclear@local' string if not set.
     *
     * @return  string  Mailfrom address
     */
    public function adminMailfrom(): string;

    /**
     * Dotclear database handler driver.
     *
     * Default Dotclear database handler driver can be:
     * mysqli, mysqlimb4 (full UTF-8), pgsql, sqlite
     *
     * From config file.
     * Returns empty string if not set.
     *
     * @return  string  Database driver
     */
    public function dbDriver(): string;

    /**
     * Dotclear database handler host.
     *
     * From config file.
     * Returns empty string if not set.
     *
     * @return  string  Database host
     */
    public function dbHost(): string;

    /**
     * Dotclear database handler user.
     *
     * From config file.
     * Returns empty string if not set.
     *
     * @return  string  Database user
     */
    public function dbUser(): string;

    /**
     * Dotclear database handler password.
     *
     * From config file.
     * Returns empty string if not set.
     *
     * @return  string  Database password
     */
    public function dbPassword(): string;

    /**
     * Dotclear database handler db name.
     *
     * From config file.
     * Returns empty string if not set.
     *
     * @return  string  Database name
     */
    public function dbName(): string;

    /**
     * Dotclear database handler table prefix.
     *
     * From config file.
     * Returns empty string if not set.
     *
     * @return  string  Database table prefix
     */
    public function dbPrefix(): string;

    /**
     * Does database use persist connection.
     *
     * From config file.
     * Returns false if not set.
     *
     * @return  bool    True for persisant
     */
    public function dbPersist(): bool;

    /**
     * Master key.
     *
     * From config file.
     * Returns empty string if not set.
     *
     * @return  string  Master key
     */
    public function masterKey(): string;

    /**
     * The cryptographic algorithm.
     *
     * From config file.
     * Returns 'sha1' string if not set.
     *
     * @return  string  Crypt algo
     */
    public function cryptAlgo(): string;

    /**
     * Dotclear attic URL.
     *
     * From config file or release file.
     * Returns empty string if not set.
     *
     * @return  string  Update URL
     */
    public function coreAtticUrl(): string;

    /**
     * Dotclear update URL.
     *
     * From config file or release file.
     * Returns empty string if not set.
     *
     * @return  string  Update URL
     */
    public function coreUpdateUrl(): string;

    /**
     * Dotclear update canal.
     *
     * Canal can be:
     * stable, testing, unstable
     *
     * From config file or release file.
     * Returns 'stable' string if not set.
     *
     * @return  string  Update canal
     */
    public function coreUpdateCanal(): string;

    /**
     * Disabled core udpate.
     *
     * From config file.
     * Returns false if not set.
     *
     * @return  bool    True for not update
     */
    public function coreNotUpdate(): bool;

    /**
     * Allow mulitple modules with same ID.
     *
     * From config file.
     * Returns false if not set.
     *
     * @return  bool    True for allow multi modules
     */
    public function allowMultiModules(): bool;

    /**
     * Disabled store udpate.
     *
     * From config file.
     * Returns false if not set.
     *
     * @return  bool    True for not update
     */
    public function storeNotUpdate(): bool;

    /**
     * Enabled third party repositories.
     *
     * From config file.
     * Returns true if not set.
     *
     * @return  bool    True for enabled
     */
    public function allowRepositories(): bool;

    /**
     * Enabled REST services.
     *
     * From config file.
     * Returns true if not set.
     *
     * @return  bool    True for enabled
     */
    public function allowRestServices(): bool;

    /**
     * Cache root path directory.
     *
     * From config file or construct in place.
     * Returns empty string if not set.
     *
     * @return  string  Cache path
     */
    public function cacheRoot(): string;

    /**
     * Var root path directory.
     *
     * From config file or construct in place.
     * Returns empty string if not set.
     *
     * @return  string  Var path
     */
    public function varRoot(): string;

    /**
     * Upgrade backup root path directory.
     *
     * From config file or construct in place.
     * Returns empty string if not set.
     *
     * @return  string  Backup root
     */
    public function backupRoot(): string;

    /**
     * REST server watchdog file.
     *
     * From anywhere or construct in place.
     * Returns empty string if not set.
     *
     * @return  string  The watchdog file path
     */
    public function coreUpgrade(): string;

    /**
     * Plugins root.
     *
     * From config file.
     * Returns empty string if not set.
     *
     * @return  string  The plugins root directories path
     */
    public function pluginsRoot(): string;

    /**
     * Max upload size.
     *
     * Construct in place.
     * Returns 0 if not set.
     *
     * @return  int     The max updload size
     */
    public function maxUploadSize(): int;

    /**
     * Query timeout (in seconds).
     *
     * From config file or construct in place.
     * Returns 4 if not set.
     *
     * @return  int     The query timetout
     */
    public function queryTimeout(): int;

    /**
     * Query stream timeout (in seconds).
     *
     * From config file or construct in place.
     * Returns 4 if not set.
     *
     * @return  null|int     The query stream timetout
     */
    public function queryStreamTimeout(): ?int;

    /**
     * Show hidden directories.
     *
     * From config file.
     * Returns false if not set.
     *
     * @return  bool    True for show
     */
    public function showHiddenDirs(): bool;

    /**
     * Force HTTP scheme on 443.
     *
     * From config file.
     * Returns false if not set.
     *
     * @return  bool    True for force http
     */
    public function httpScheme443(): bool;

    /**
     * Use http reverse proxy.
     *
     * From config file.
     * Returns false if not set.
     *
     * @return  bool    True for use
     */
    public function httpReverseProxy(): bool;

    /**
     * Check ads blocker use.
     *
     * From config file.
     * Returns true if not set.
     *
     * @return  bool    True for check
     */
    public function checkAdsBlocker(): bool;

    /**
     * CSP report file.
     *
     * From config file or construct in place.
     * Returns default file if not set.
     *
     * @return  string  CSP report file
     */
    public function cspReportFile(): string;

    /**
     * Load legacy JS.
     *
     * From release file.
     * Returns true if not set.
     *
     * @return  bool    True for check
     */
    public function dotclearMigrate(): bool;

    /**
     * Maximum number of media to add in DB in not in it.
     * Used when getting directory contents in media manager.
     *
     * @return     int   Maximum number of media to add in DB in not in it
     */
    public function mediaUpdateDBLimit(): int;
}
