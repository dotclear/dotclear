<?php
/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core;

use Dotclear\Interface\Core\ConfigInterface;

/**
 * Config handler.
 * 
 * Transitionnal class for DC_ constants.
 *
 * Simple write once properties for 
 * unmutable configuration values.
 *
 * All methods are typed and return
 * also default values in same type.
 * 
 * Not yet in config:
 * * DC_DBHANDLER_CLASS
 * * DC_DBSCHEMA_CLASS
 * * DC_AUTH_CLASS
 * * DC_CONTEXT_ADMIN
 * * DC_CONTEXT_INSTALL
 * * DC_CONTEXT_UPGRADE
 * * DC_CONTEXT_PUBLIC
 * * DC_CONTEXT_MODULE
 * * DC_BACKUP_PATH
 * * DC_CSP_LOGFILE
 * * DC_ERRORFILE
 * * DC_BLOG_ID
 * * DC_SHOW_HIDDEN_DIRS
 * * DC_ADBLOCKER_CHECK
 * * DC_FAIRTRACKBACKS_FORCE (plugin)
 * * DC_ANTISPAM_CONF_SUPER (plugin)
 * * DC_DNSBL_SUPER (plugin)
 * * DC_AKISMET_SUPER (plugin)
 * 
 */
class Config implements ConfigInterface
{
	private float $start_time;
	private bool $cli_mode;
	private bool $debug_mode;
	private bool $dev_mode;
	private string $dotclear_root;
	private string $dotclear_version;
	private string $dotclear_name;
	private string $config_path;
	private string $digests_root;
	private string $l10n_root;
	private string $l10n_update_url;
	private string $distributed_plugins;
	private string $distributed_themes;
	private string $default_theme;
	private string $default_tplset;
	private string $default_jquery;
	private string $next_required_php;
	private string $vendor_name;
	private string $xmlrpc_url;
	private ?string $session_ttl;
	private string $session_name;
	private bool $admin_ssl;
	private string $admin_url;
	private string $admin_mailfrom;
	private string $db_driver;
	private string $db_host;
	private string $db_user;
	private string $db_password;
	private string $db_name;
	private string $db_prefix;
	private bool $db_persist;
	private string $master_key;
	private string $crypt_algo;
	private string $core_update_url;
	private string $core_update_canal;
	private bool $core_not_update;
	private bool $allow_multi_modules;
	private bool $store_not_update;
	private bool $allow_repositories;
	private bool $allow_rest_services;
	private string $cache_root;
	private string $var_root;
	private string $core_upgrade;
	private string $plugins_root;
	private int $max_upload_size;
	private int $query_timeout;

	private function set($key, $value = null)
	{
		return $this->{$key} ?? (is_null($value) ? null : $this->{$key} = $value);
	}

	public function startTime(): float
	{
		return $this->start_time ?? ($this->start_time = defined('DC_START_TIME') ? DC_START_TIME : microtime(true));
	}

	public function cliMode(): bool
	{
		return $this->cli_mode ?? ($this->cli_mode = defined('CLI_MODE') ? CLI_MODE : false);
	}

	public function debugMode(): bool
	{
		return $this->debug_mode ?? ($this->debug_mode = defined('DC_DEBUG') ? DC_DEBUG : false);
	}

	public function devMode(): bool
	{
		return $this->dev_mode ?? ($this->dev_mode = defined('DC_DEV') ? DC_DEV : false);
	}

	public function dotclearRoot(): string
	{
		return $this->dotclear_root ?? ($this->dotclear_root = defined('DC_ROOT') ? DC_ROOT : '');
	}

	public function dotclearVersion(): string
	{
		return $this->dotclear_version ?? ($this->dotclear_version = defined('DC_VERSION') ? DC_VERSION : '');
	}

	public function dotclearName(): string
	{
		return $this->dotclear_name ?? ($this->dotclear_name = defined('DC_NAME') ? DC_NAME : '');
	}

	public function configPath(): string
	{
		return $this->config_path ?? ($this->config_path = defined('DC_RC_PATH') ? DC_RC_PATH : '');
	}

	public function digestsRoot(): string
	{
		return $this->digests_root ?? ($this->digests_root = defined('DC_DIGESTS') ? DC_DIGESTS : '');
	}

	public function l10nRoot(): string
	{
		return $this->l10n_root ?? ($this->l10n_root = defined('DC_L10N_ROOT') ? DC_L10N_ROOT : '');
	}

	public function l10nUpdateUrl(): string
	{
		return $this->l10n_update_url ?? ($this->l10n_update_url = defined('DC_L10N_UPDATE_URL') ? DC_L10N_UPDATE_URL : '');
	}

	public function distributedPlugins(): string
	{
		return $this->distributed_plugins ?? ($this->distributed_plugins = defined('DC_DISTRIB_PLUGINS') ? DC_DISTRIB_PLUGINS : '');
	}

	public function distributedThemes(): string
	{
		return $this->distributed_themes ?? ($this->distributed_themes = defined('DC_DISTRIB_THEMES') ? DC_DISTRIB_THEMES : '');
	}

	public function defaultTheme(): string
	{
		return $this->default_theme ?? ($this->default_theme = defined('DC_DEFAULT_THEME') ? DC_DEFAULT_THEME : '');
	}

	public function defaultTplset(): string
	{
		return $this->default_tplset ?? ($this->default_tplset = defined('DC_DEFAULT_TPLSET') ? DC_DEFAULT_TPLSET : '');
	}

	public function defaultJQuery(): string
	{
		return $this->default_jquery ?? ($this->default_jquery = defined('DC_DEFAULT_JQUERY') ? DC_DEFAULT_JQUERY : '');
	}

	public function nextRequiredPhp(): string
	{
		return $this->next_required_php ?? ($this->next_required_php = defined('DC_NEXT_REQUIRED_PHP') ? DC_NEXT_REQUIRED_PHP : '');
	}

	public function vendorName(): string
	{
		return $this->vendor_name ?? ($this->vendor_name = defined('DC_VENDOR_NAME') ? DC_VENDOR_NAME : 'Dotclear');
	}

	public function xmlrplUrl(): string
	{
		return $this->xmlrpc_url ?? ($this->xmlrpc_url = defined('DC_XMLRPC_URL') ? DC_XMLRPC_URL : '%1$sxmlrpc/%2$s');
	}

	public function sessionTtl(): ?string
	{
		return $this->session_ttl ?? ($this->session_ttl = defined('DC_SESSION_TTL') ? DC_SESSION_TTL : null);
	}

	public function sessionName(): string
	{
		return $this->session_name ?? ($this->session_name = defined('DC_SESSION_NAME') ? DC_SESSION_NAME : 'dcxd');
	}

	public function adminSsl(): bool
	{
		return $this->admin_ssl ?? ($this->admin_ssl = defined('DC_ADMIN_SSL') ? DC_ADMIN_SSL : false);
	}

	public function adminMailfrom(): string
	{
		return $this->admin_mailfrom ?? ($this->admin_mailfrom = defined('DC_ADMIN_MAILFROM') && strpos(DC_ADMIN_MAILFROM, '@') ? DC_ADMIN_MAILFROM : 'dotclear@local');
	}

	public function adminUrl(): string
	{
		return $this->admin_url ?? ($this->admin_url = defined('DC_ADMIN_URL') ? DC_ADMIN_URL : '');
	}

	public function dbDriver(): string
	{
		return $this->db_driver ?? ($this->db_driver = defined('DC_DBDRIVER') ? DC_DBDRIVER : '');
	}

	public function dbHost(): string
	{
		return $this->db_host ?? ($this->db_host = defined('DC_DBHOST') ? DC_DBHOST : '');
	}

	public function dbUser(): string
	{
		return $this->db_user ?? ($this->db_user = defined('DC_DBUSER') ? DC_DBUSER : '');
	}

	public function dbPassword(): string
	{
		return $this->db_password ?? ($this->db_password = defined('DC_DBPASSWORD') ? DC_DBPASSWORD : '');
	}

	public function dbName(): string
	{
		return $this->db_name ?? ($this->db_name = defined('DC_DBNAME') ? DC_DBNAME : '');
	}

	public function dbPrefix(): string
	{
		return $this->db_prefix ?? ($this->db_prefix = defined('DC_DBPREFIX') ? DC_DBPREFIX : '');
	}

	public function dbPersist(): bool
	{
		return $this->db_persist ?? ($this->db_persist = defined('DC_DBPERSIST') ? DC_DBPERSIST : false);
	}

	public function masterKey(): string
	{
		return $this->master_key ?? ($this->master_key = defined('DC_MASTER_KEY') ? DC_MASTER_KEY : '');
	}

	public function cryptAlgo(): string
	{
		return $this->crypt_algo ?? ($this->crypt_algo = defined('DC_CRYPT_ALGO') ? DC_CRYPT_ALGO : 'sha512');
	}

	public function coreUpdateUrl(): string
	{
		return $this->core_update_url ?? ($this->core_update_url = defined('DC_UPDATE_URL') ? DC_UPDATE_URL : '');
	}

	public function coreUpdateCanal(): string
	{
		return $this->core_update_canal ?? ($this->core_update_canal = defined('DC_UPDATE_URL') ? DC_UPDATE_URL : 'stable');
	}

	public function coreNotUpdate(): bool
	{
		return $this->core_not_update ?? ($this->core_not_update = defined('DC_NOT_UPDATE') ? DC_NOT_UPDATE : false);
	}

	public function allowMultiModules(): bool
	{
		return $this->allow_multi_modules ?? ($this->allow_multi_modules = defined('DC_ALLOW_MULTI_MODULES') ? DC_ALLOW_MULTI_MODULES : false);
	}

	public function storeNotUpdate(): bool
	{
		return $this->sore_not_update ?? ($this->sore_not_update = defined('DC_STORE_NOT_UPDATE') ? DC_STORE_NOT_UPDATE : false);
	}

	public function allowRepositories(): bool
	{
		return $this->allow_repositories ?? ($this->allow_repositories = defined('DC_ALLOW_REPOSITORIES') ? DC_ALLOW_REPOSITORIES : true);
	}

	public function allowRestServices(): bool
	{
		return $this->allow_rest_services ?? ($this->allow_rest_services = defined('DC_REST_SERVICES') ? DC_REST_SERVICES : true);
	}

	public function cacheRoot(): string
	{
		return $this->cache_root ?? ($this->cache_root = defined('DC_TPL_CACHE') ? DC_TPL_CACHE : '');
	}

	public function varRoot(): string
	{
		return $this->var_root ?? ($this->var_root = defined('DC_VAR') ? DC_VAR : '');
	}

	public function pluginsRoot(): string
	{
		return $this->plugins_root ?? ($this->plugins_root = defined('DC_PLUGINS_ROOT') ? DC_PLUGINS_ROOT : '');
	}

	public function coreUpgrade(): string
	{
		return $this->core_upgrade ?? ($this->core_upgrade = defined('DC_UPGRADE') ? DC_UPGRADE : '');
	}

	public function maxUploadSize(): int
	{
		return $this->max_upload_size ?? ($this->max_upload_size = defined('DC_MAX_UPLOAD_SIZE') ? DC_MAX_UPLOAD_SIZE : 0);
	}

	public function queryTimeout(): int
	{
		return $this->query_timeout ?? ($this->query_timeout = defined('DC_QUERY_TIMEOUT') ? DC_QUERY_TIMEOUT : 4);
	}
}