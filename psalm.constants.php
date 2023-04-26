<?php
/**
 * Psalm bootstrap
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

// Mock some global constants
define('DC_ADBLOCKER_CHECK', true);
define('DC_ADMIN_SSL', true);
define('DC_ADMIN_URL', '');
define('DC_AKISMET_SUPER', true);
define('DC_ALLOW_REPOSITORIES', true);
define('DC_ANTISPAM_CONF_SUPER', true);
define('DC_BACKUP_PATH', '');
define('DC_CRYPT_ALGO', 'sha256');
define('DC_CSP_LOGFILE', '');
define('DC_DBDRIVER', '');
define('DC_DBHOST', '');
define('DC_DBNAME', '');
define('DC_DBPASSWORD', '');
define('DC_DBPREFIX', '');
define('DC_DBUSER', '');
define('DC_DEBUG', false);
define('DC_DEFAULT_JQUERY', '3.6.0');
define('DC_DEFAULT_THEME', 'berlin');
define('DC_DEFAULT_TPLSET', 'currywurst');
define('DC_DEV', true);
define('DC_DIGESTS', 'inc/digest');
define('DC_DISTRIB_PLUGINS', '');
define('DC_DISTRIB_THEMES', '');
define('DC_DNSBL_SUPER', '');
define('DC_ERRORFILE', '404.html');
define('DC_FAIRTRACKBACKS_FORCE', true);
define('DC_FORCE_SCHEME_443', false);
define('DC_L10N_ROOT', __DIR__ . DIRECTORY_SEPARATOR . 'locales');
define('DC_MASTER_KEY', 'PASSWORD');
define('DC_MAX_UPLOAD_SIZE', 42);
define('DC_PLUGINS_ROOT', '');
define('DC_QUERY_TIMEOUT', 5);
define('DC_REST_SERVICES', true);
define('DC_REVERSE_PROXY', false);
define('DC_ROOT', __DIR__);
define('DC_SESSION_NAME', '');
define('DC_STORE_NOT_UPDATE', false);
define('DC_TPL_CACHE', __DIR__ . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'cbtpl');
define('DC_UPGRADE', false);
define('DC_VAR', __DIR__ . DIRECTORY_SEPARATOR . 'var');
define('DC_VENDOR_NAME', 'Dotclear');
define('DC_VERSION', '2.42');
define('HTTP_PROXY_HOST', null);
define('HTTP_PROXY_PORT', null);
