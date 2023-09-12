<?php
/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Interface;

/**
 * Config handler.
 *
 * Simple write once container for
 * unmutable configuration values.
 *
 * All methods are typed and return
 * also default values in same type.
 */
interface ConfigInterface
{
    /**
     * Read Dotclear release config.
     *
     * This method always returns string,
     * casting int, bool, array, to string.
     *
     * @param   string  $key The release key
     *
     * @return  string  The release value
     */
    public function release(string $key): string;

    /**
     * Application start time.
     *
     * Keep only thhe first timer start.
     *
     * @return  float   The start time
     */
    public function startTime(): float;

    /**
     * Does app run in CLI mode.
     *
     * Returns false if not set.
     *
     * @return  bool    True for CLI mode
     */
    public function cliMode(): bool;

    /**
     * Does app run in debug mode.
     *
     * Returns false if not set.
     *
     * @return  bool    True for debug mode
     */
    public function debugMode(): bool;

    /**
     * Does app run in dev mode.
     *
     * Returns false if not set.
     *
     * @return  bool    True for dev mode
     */
    public function devMode(): bool;

    /**
     * Root path directory.
     *
     * Returns emtpy string if not set.
     *
     * @return  string  Dotclear root
     */
    public function dotclearRoot(): string;

    /**
     * Release version.
     *
     * Returns empty string if not set.
     *
     * @return  string  Dotclear version
     */
    public function dotclearVersion(): string;

    /**
     * Release name.
     *
     * Returns empty string if not set.
     *
     * @return  string  Dotclear name
     */
    public function dotclearName(): string;

    /**
     * Configuration file path.
     *
     * Returns empty string if not set.
     *
     * @return  string  Dotclear config file path
     */
    public function configPath(): string;

    /**
     * Digests path.
     *
     * Returns empty string if not set.
     *
     * @return  string  Dotclear digests path.
     */
    public function digestsRoot(): string;

    /**
     * Locales root path directory.
     *
     * Returns empty string if not set.
     *
     * @return  string  l10n path
     */
    public function l10nRoot(): string;

    /**
     * Locales Update URL.
     *
     * Retruns empty string if not set.
     *
     * @return  string  l10n update URL
     */
    public function l10nUpdateUrl(): string;

    /**
     * Distributed plugins list.
     *
     * Returns comma separated list of distributed plugins
     * or empty string if not set.
     *
     * @return  string  Distributed plugins
     */
    public function distributedPlugins(): string;

    /**
     * Distributed themes list.
     *
     * Returns comma separated list of distributed themes
     * or empty string if not set.
     *
     * @return  string  Distributed themes
     */
    public function distributedThemes(): string;

    /**
     * Default theme.
     *
     * Returns empty string if not set.
     *
     * @return  string  Default theme
     */
    public function defaultTheme(): string;

    /**
     * Default templates set.
     *
     * Returns empty string if not set.
     *
     * @return  string  Default templates set
     */
    public function defaultTplset(): string;

    /**
     * Default JQuery version.
     *
     * Returns empty string if not set.
     *
     * @return  string  Default JQeury version
     */
    public function defaultJQuery(): string;

    /**
     * Next release required PHP version.
     *
     * Returns empty string if not set.
     *
     * @return  string  Next required PHP version
     */
    public function nextRequiredPhp(): string;

    /**
     * Multiblog vendor name.
     *
     * Returns 'Dotclear' string if not set.
     *
     * @return  string  Vendor name
     */
    public function vendorName(): string;

    /**
     * XML RPC URL.
     *
     * Returns '%1$sxmlrpc/%2$s' string if not set.
     *
     * @return  string  Vendor name
     */
    public function xmlrplUrl(): string;

    /**
     * Session TTl (time to live).
     *
     * Returns null if not set.
     *
     * @return  null|string     Session TTL
     */
    public function sessionTtl(): ?string;

    /**
     * Session name.
     *
     * Returns 'dcxd' string if not set.
     *
     * @return  string  Session name
     */
    public function sessionName(): string;

    /**
     * Does backend use SSL.
     *
     * Returns false if not set.
     *
     * @return  bool    True for SSL mode
     */
    public function adminSsl(): bool;

    /**
     * Backend URL
     *
     * Returns empty string if not set.
     *
     * @return  string  Admin URL
     */
    public function adminUrl(): string;

    /**
     * Admin mail from address.
     *
     * Used for password recovery and such.
     *
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
     * Returns empty string if not set.
     *
     * @return  string  Database driver
     */
    public function dbDriver(): string;

    /**
     * Dotclear database handler host.
     *
     * Returns empty string if not set.
     *
     * @return  string  Database host
     */
    public function dbHost(): string;

    /**
     * Dotclear database handler user.
     *
     * Returns empty string if not set.
     *
     * @return  string  Database user
     */
    public function dbUser(): string;

    /**
     * Dotclear database handler password.
     *
     * Returns empty string if not set.
     *
     * @return  string  Database password
     */
    public function dbPassword(): string;

    /**
     * Dotclear database handler db name.
     *
     * Returns empty string if not set.
     *
     * @return  string  Database name
     */
    public function dbName(): string;

    /**
     * Dotclear database handler table prefix.
     *
     * Returns empty string if not set.
     *
     * @return  string  Database table prefix
     */
    public function dbPrefix(): string;

    /**
     * Does databse use persist connection.
     *
     * Returns false if not set.
     *
     * @return  bool    True for persisant
     */
    public function dbPersist(): bool;

    /**
     * Master key.
     *
     * Returns empty string if not set.
     *
     * @return  string  Master key
     */
    public function masterKey(): string;

    /**
     * The cryptographic algorithm.
     *
     * Returns 'sha1' string if not set.
     *
     * @return  string  Crypt algo
     */
    public function cryptAlgo(): string;

    /**
     * Dotclear update URL.
     *
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
     * Returns 'stable' string if not set.
     *
     * @return  string  Update canal
     */
    public function coreUpdateCanal(): string;

    /**
     * Disabled core udpate.
     *
     * Returns false if not set.
     *
     * @return  bool    True for not update
     */
    public function coreNotUpdate(): bool;

    /**
     * Allow mulitple modules with same ID.
     *
     * Returns false if not set.
     *
     * @return  bool    True for allow multi modules
     */
    public function allowMultiModules(): bool;

    /**
     * Disabled store udpate.
     *
     * Returns false if not set.
     *
     * @return  bool    True for not update
     */
    public function storeNotUpdate(): bool;

    /**
     * Enabled third party repositories.
     *
     * Returns true if not set.
     *
     * @return  bool    True for enabled
     */
    public function allowRepositories(): bool;

    /**
     * Enabled REST services.
     *
     * Returns true if not set.
     *
     * @return  bool    True for enabled
     */
    public function allowRestServices(): bool;

    /**
     * Cache root path directory.
     *
     * Returns empty string if not set.
     *
     * @return  string  Cache path
     */
    public function cacheRoot(): string;

    /**
     * Var root path directory.
     *
     * Returns empty string if not set.
     *
     * @return  string  Var path
     */
    public function varRoot(): string;

    /**
     * REST server watchdog file.
     *
     * Returns empty string if not set.
     *
     * @return  string  The watchdog file path
     */
    public function coreUpgrade(): string;

    /**
     * Plugins root.
     *
     * Returns empty string if not set.
     *
     * @return  string  The plugins root directories path
     */
    public function pluginsRoot(): string;

    /**
     * Max upload size.
     *
     * Returns 0 if not set.
     *
     * @return  int     The max updload size
     */
    public function maxUploadSize(): int;

    /**
     * Query timeout (in seconds).
     *
     * Returns 4 if not set.
     *
     * @return  int     The query timetout
     */
    public function queryTimeout(): int;

    /**
     * Show hidden direcotries.
     *
     * Returns false if not set.
     *
     * @return  bool    True for show
     */
    public function showHiddenDirs(): bool;
}
