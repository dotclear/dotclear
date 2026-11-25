<?php

/**
 * @brief       DotclearWatch utils class.
 * @ingroup     DotclearWatch
 *
 * @author      Jean-Christian Denis
 *
 * @copyright   Jean-Christian Denis
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Plugin\DotclearWatch;

use Dotclear\App;
use Dotclear\Helper\Crypt;
use Dotclear\Helper\Network\HttpClient;
use Dotclear\Module\ModuleDefine;
use Throwable;

class Utils
{
    /**
     * The expiration delay before resend report (one week).
     *
     * @var     int     EXPIRED_DELAY
     */
    public const EXPIRED_DELAY = 604800;

    /**
     * The delay before retry a failed report (one day).
     *
     * @var     int     RETRY_DELAY
     */
    public const RETRY_DELAY = 86400;

    /**
     * The distant API connection timeout in seconds.
     *
     * @var     int     CONNECT_TIMEOUT
     */
    public const CONNECT_TIMEOUT = 5;

    /**
     * The distant API request timeout in seconds.
     *
     * @var     int     REQUEST_TIMEOUT
     */
    public const REQUEST_TIMEOUT = 20;

    /**
     * The default distant blog API URL.
     *
     * @var     string  DISTANT_API_URL
     */
    public const DISTANT_API_URL = 'https://dotclear.org/';

    /**
     * The old default distant blog API URLs, moved to dotclear.org.
     *
     * @var     array<int,string>   OLD_API_URLS
     */
    public const OLD_API_URLS = [
        'https://dotclear.watch',
        'https://dotclear.watch/api',
        'http://dotclear.watch',
        'http://dotclear.watch/api',
    ];

    /**
     * The shared statistics URL of the default distant API.
     *
     * @var     string  STATISTICS_URL
     */
    public const STATISTICS_URL = 'https://dotclear.org/statistics';

    /**
     * The default distant API URI.
     *
     * @var     string  DISTANT_API_URL
     */
    public const DISTANT_API_URI = 'api/';

    /**
     * The distant API version.
     *
     * @var     string  DISTANT_API_VERSION
     */
    public const DISTANT_API_VERSION = 'v1';

    /**
     * The hiddens modules IDs.
     *
     * @var     array<int,string>   $hiddens
     */
    private static array $hiddens = [];

    /**
     * Multiblog unique identifiant.
     */
    private static string $uid = '';

    /**
     * Get hidden modules.
     *
     * This does not check if module exists.
     *
     * @return  array<int,string>   The hiddens modules
     */
    public static function getHiddens(): array
    {
        if (self::$hiddens === [] && is_string(My::settings()->getGlobal('hidden_modules'))) {
            foreach (explode(',', My::settings()->getGlobal('hidden_modules')) as $hidden) {
                $hidden = trim($hidden);
                if (!empty($hidden)) {
                    self::$hiddens[] = trim($hidden);
                }
            }
        }

        return self::$hiddens;
    }

    /**
     * Get plugins list.
     *
     * @param   bool    $strict     take only enabled and not hidden plugins
     *
     * @return  array<string,string>    The plugins list.
     */
    public static function getPlugins(bool $strict = true): array
    {
        $modules = [];
        $hiddens = self::getHiddens();
        $defines = App::plugins()->getDefines($strict ? ['state' => ModuleDefine::STATE_ENABLED] : []);
        foreach ($defines as $define) {
            if (($strict && in_array($define->getId(), $hiddens, true)) || !is_string($define->get('version'))) {
                continue;
            }

            $modules[$define->getId()] = $define->get('version');
        }

        return $modules;
    }

    /**
     * Get disabled plugins list.
     *
     * Plugins disabled by the user or by the system (missing requirements),
     * without hidden plugins.
     *
     * @return  array<string,string>    The disabled plugins list.
     */
    public static function getDisabledPlugins(): array
    {
        $modules = [];
        $hiddens = self::getHiddens();
        foreach (App::plugins()->getDefines() as $define) {
            if ($define->get('state') === ModuleDefine::STATE_ENABLED
                || in_array($define->getId(), $hiddens, true)
                || !is_string($define->get('version'))
            ) {
                continue;
            }

            $modules[$define->getId()] = $define->get('version');
        }

        return $modules;
    }

    /**
     * Get themes list.
     *
     * @param   bool    $strict     take only enabled and not hidden themes
     *
     * @return  array<string,string>    The themes list.
     */
    public static function getThemes(bool $strict = true): array
    {
        if (App::themes()->isEmpty()) {
            App::themes()->loadModules(App::blog()->themesPath());
        }

        $modules = [];
        $hiddens = self::getHiddens();
        $defines = App::themes()->getDefines($strict ? ['state' => ModuleDefine::STATE_ENABLED] : []);
        foreach ($defines as $define) {
            if (($strict && in_array($define->getId(), $hiddens, true)) || !is_string($define->get('version'))) {
                continue;
            }

            $modules[$define->getId()] = $define->get('version');
        }

        return $modules;
    }

    /**
     * Get server software and version.
     *
     * @return  array<string,string> The server info
     */
    public static function getServer(): array
    {
        $res = [
            'name'    => 'undefined',
            'version' => 'undefined',
        ];

        if (isset($_SERVER['SERVER_SOFTWARE']) && !empty($_SERVER['SERVER_SOFTWARE']) && is_string($_SERVER['SERVER_SOFTWARE'])) {
            $exp = explode('/', $_SERVER['SERVER_SOFTWARE']);
            if (count($exp) === 2) {
                $res = [
                    'name'    => $exp[0],
                    'version' => $exp[1],
                ];
            }
        }

        return $res;
    }

    /**
     * Get loaded PHP extensions and their versions.
     *
     * @return  array<string,string> The extensions, empty version if an extension has none
     */
    public static function getPhpExtensions(): array
    {
        $res = [];
        foreach (get_loaded_extensions() as $extension) {
            $res[strtolower($extension)] = (string) phpversion($extension);
        }

        ksort($res);

        return $res;
    }

    /**
     * Get report contents.
     *
     * @return  string  The report contents as it will be sent
     */
    public static function getReport(): string
    {
        return self::check() ? self::contents() : '';
    }

    /**
     * Get client uid.
     */
    public static function getClient(): string
    {
        return self::check() ? self::uid() : '';
    }

    /**
     * Get request error.
     */
    public static function getError(): string
    {
        $rs = App::log()->getLogs([
            'log_table' => My::id() . '_error',
        ]);

        return $rs->isEmpty() ? '' : $rs->strField('log_msg');
    }

    /**
     * Clear cache directory.
     */
    public static function clearCache(): void
    {
        if (self::check()) {
            self::clear();
        }
    }

    /**
     * Send report.
     *
     * Report will be by blog to keep track of all used themes.
     * Plugins stats are by multiblogs and themes stats by blog.
     *
     * @param   bool    $force      Send report even if delay not expired
     * @param   string  $api_url    The distant API URL, if just changed (settings are not reloaded)
     */
    public static function sendReport(bool $force = false, string $api_url = ''): void
    {
        if (!self::check() || !$force && !self::expired()) {
            return;
        }

        // Build and write content
        self::write($contents = self::contents());

        // Prepare API request
        $response = '';
        $url      = sprintf(self::url($api_url), 'report');
        $path     = '';
        $agent    = My::id() . '/' . (is_string(App::plugins()->getDefine(My::id())->get('version')) ? App::plugins()->getDefine(My::id())->get('version') : '0');
        $header   = 'X-API-Version: ' . static::DISTANT_API_VERSION;
        $params   = [
            'uid'    => self::uid(),
            'key'    => self::key(),
            'report' => $contents,
            // Ask for the compatibility rules of the next Dotclear version (ignored by older servers)
            'rules' => '1',
        ];

        // A distant server not reachable is not a PHP error: keep connection warnings as report error
        $failure = '';
        set_error_handler(function (int $errno, string $errstr) use (&$failure): bool {
            $failure = $errstr;

            return true;
        }, E_WARNING | E_NOTICE);

        try {
            if (function_exists('curl_init')) {
                // Try using CURL first, it respects request timeout
                if (false !== ($client = curl_init($url))) {
                    curl_setopt($client, CURLOPT_USERAGENT, $agent);
                    curl_setopt($client, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($client, CURLOPT_POST, true);
                    curl_setopt($client, CURLOPT_HTTPHEADER, [$header]);
                    curl_setopt($client, CURLOPT_POSTFIELDS, $params);
                    curl_setopt($client, CURLOPT_CONNECTTIMEOUT, self::CONNECT_TIMEOUT);
                    curl_setopt($client, CURLOPT_TIMEOUT, self::REQUEST_TIMEOUT);

                    $response = curl_exec($client);
                    if ($response === false) {
                        $failure = curl_error($client);
                    }
                }
            } elseif (false !== ($client = HttpClient::initClient($url, $path))) {
                // Try using Dotclear HTTP client
                $client->setUserAgent($agent);
                $client->useGzip(false);
                $client->setPersistReferers(false);
                $client->setMoreHeader($header);
                $client->setTimeout(self::CONNECT_TIMEOUT);
                $client->setStreamTimeout(self::REQUEST_TIMEOUT);
                $client->post($path, $params);

                $response = $client->getContent();
            }
        } catch (Throwable $throwable) {
            $failure = $throwable->getMessage();
        } finally {
            restore_error_handler();
            unset($client);
        }

        // Parse reponse
        $rsp = json_decode((string) $response, true) ?? [];

        if (!is_array($rsp) || !isset($rsp['code']) || !is_numeric($rsp['code']) || !isset($rsp['message']) || !is_string($rsp['message'])) {
            self::error('Dotclear.watch report failed' . ($failure !== '' ? ': ' . $failure : ''));
        } elseif ((int) $rsp['code'] !== 200) {
            self::error('(' . $rsp['code'] . ') ' . $rsp['message']);
        } elseif (isset($rsp['content']) && is_array($rsp['content']) && isset($rsp['content']['rules']) && is_array($rsp['content']['rules'])) {
            Audit::saveRules($rsp['content']['rules']);
        }
    }

    /**
     * Check if report can be done.
     *
     * @return  bool    True if it can
     */
    private static function check(): bool
    {
        return true; // not yet
    }

    /**
     * Get report key.
     *
     * @return  string  The report key
     */
    private static function key(): string
    {
        return Crypt::hmac(self::uid() . My::id(), App::config()->cryptAlgo());
    }

    private static function uid(): string
    {
        if (empty(self::$uid)) {
            // Client UID setting does not exist on first report
            self::$uid = is_string($uid = My::settings()->getGlobal('client_uid')) ? $uid : '';
            if (empty(self::$uid) || strlen(self::$uid) !== 32) {
                self::$uid = md5(uniqid() . My::id() . time());
                My::settings()->put('client_uid', self::$uid, 'string', 'Client UID', false, true);
            }
        }

        return self::$uid;
    }

    /**
     * Get blog report uid.
     *
     * @return  string  The blog report uid
     */
    private static function buid(): string
    {
        return md5(self::uid() . App::blog()->uid());
    }

    /**
     * Get query URL.
     *
     * ex: https://blog.url/api/DotclearWatch/report/
     *
     * @return  string  The URL
     */
    private static function url(string $api_url = ''): string
    {
        if ($api_url === '') {
            $api_url = My::settings()->getGlobal('distant_api_url');
        }

        if (!is_string($api_url) || empty($api_url)) {
            $api_url = self::DISTANT_API_URL;
        }

        if (!str_ends_with($api_url, '/')) {
            $api_url .= '/';
        }

        // Remove old style (< 1.0) API URL
        if (str_ends_with($api_url, 'api/')) {
            $api_url = substr($api_url, 0, -4);
        }

        return $api_url . self::DISTANT_API_URI . My::id() . '/%s/';
    }

    /**
     * Clear report logs.
     *
     * @param   bool    $errors_only    Only clear errors logs (keep last report date)
     */
    private static function clear(bool $errors_only = false): void
    {
        $rs = App::log()->getLogs([
            'log_table' => $errors_only ? [My::id() . '_error'] : [
                My::id() . '_report',
                My::id() . '_error',
            ],
        ]);

        if ($rs->isEmpty()) {
            return;
        }

        $logs = [];
        while ($rs->fetch()) {
            $logs[] = $rs->intField('log_id');
        }

        App::log()->delLogs($logs);
    }

    /**
     * Log error.
     */
    private static function error(string $message): void
    {
        // Keep report log to not retry on each page load
        self::clear(true);

        $cur = App::log()->openLogCursor();
        $cur->setField('log_table', My::id() . '_error');
        $cur->setField('log_msg', $message);

        App::log()->addLog($cur);
    }

    /**
     * Write report.
     */
    private static function write(string $contents): void
    {
        self::clear();

        $cur = App::log()->openLogCursor();
        $cur->setField('log_table', My::id() . '_report');
        $cur->setField('log_msg', $contents);

        App::log()->addLog($cur);
    }

    /**
     * Check if report is expired.
     *
     * @return  bool True if expired
     */
    private static function expired(): bool
    {
        $rs = App::log()->getLogs([
            'log_table' => My::id() . '_report',
        ]);

        if ($rs->isEmpty() || $rs->strField('log_dt') === '') {
            return true;
        }

        // Failed report is retried sooner
        $delay = self::getError() === '' ? self::EXPIRED_DELAY : self::RETRY_DELAY;

        return (int) strtotime($rs->strField('log_dt')) + $delay < time();
    }

    /**
     * Get report content.
     *
     * @return  string Teh report content
     */
    private static function contents(): string
    {
        // Build json response
        return (string) json_encode([
            'uid'              => self::uid(),
            'buid'             => self::buid(),
            'plugins'          => self::getPlugins(), // enabled plugins
            'plugins_disabled' => self::getDisabledPlugins(), // disabled plugins
            'themes'           => self::getThemes(), // enabled themes
            'blog'             => [
                'lang'  => App::blog()->settings()->get('system')->getStr('lang', false),
                'theme' => App::blog()->settings()->get('system')->getStr('theme', false),
            ],
            'blogs' => [
                'count' => App::blogs()->getBlogs([], true)->cardinal(),
            ],
            'core' => [
                'version' => App::config()->dotclearVersion(),
                'modern'  => App::config()->modern() ? 1 : 0, // DC_MODERN
            ],
            'server' => self::getServer(),
            'php'    => [
                'sapi'    => PHP_SAPI,
                'version' => PHP_VERSION,
                'minor'   => PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION,
            ],
            'php_extensions' => self::getPhpExtensions(),
            'system'         => [
                'name'    => php_uname('s'),
                'version' => php_uname('r'),
            ],
            'database' => [
                'driver'  => App::db()->con()->driver(),
                'version' => App::db()->con()->version(),
            ],
        ], JSON_PRETTY_PRINT);
    }
}
