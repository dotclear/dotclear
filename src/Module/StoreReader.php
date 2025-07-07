<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Module;

use Dotclear\App;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\Network\HttpClient;
use Exception;

/**
 * @brief   Repository modules XML feed reader.
 *
 * Provides an object to parse XML feed of modules from repository.
 *
 * @since   2.6
 */
class StoreReader extends HttpClient
{
    /**
     * Read nothing .
     *
     * @var     int     READ_FROM_NONE
     */
    public const READ_FROM_NONE = -1;

    /**
     * Read from local cache file.
     *
     * @var     int     READ_FROM_CACHE
     */
    public const READ_FROM_CACHE = 0;

    /**
     * Read from repository server.
     *
     * @var     int     READ_FROM_SOURCE
     */
    public const READ_FROM_SOURCE = 1;

    /**
     * Default modules store cache sub folder.
     *
     * @var     string  CACHE_FOLDER
     */
    public const CACHE_FOLDER = 'dcrepo';

    /**
     * User agent used to query repository.
     *
     * @var     string  $user_agent
     */
    protected $user_agent = 'DotClear.org RepoBrowser/0.1';

    /**
     * HTTP Cache validators.
     *
     * @var     array<string, mixed>|null  $validators
     */
    protected $validators;

    /**
     * Cache temporary directory.
     *
     * @var     string|null     $cache_dir
     */
    protected $cache_dir;

    /**
     * Cache file prefix.
     *
     * @var     string  $cache_file_prefix
     */
    protected $cache_file_prefix = self::CACHE_FOLDER;

    /**
     * Cache TTL.
     *
     * @var     string  $cache_ttl
     */
    protected $cache_ttl = '-1440 minutes';

    /**
     * 'Cache' TTL on server failed.
     *
     * @var     bool    $cache_touch_on_fail
     */
    protected $cache_touch_on_fail = true;

    /**
     * Force query server.
     *
     * * True: query server even if cache is not expired
     * * False: query server if there's no cache or cache is expired
     * * Null: query server only if there's no cache (we don't look at ttl)
     *
     * @var     null|bool   $force
     */
    protected $force = false;

    /**
     * Last response source (from cache or repository).
     */
    private static int $read_code = self::READ_FROM_NONE;

    /**
     * Host cache
     *
     * Key = host (without protocol)
     * Values = [next timeout to use, time of last failed]
     *
     * If a fetch fails, store the host with the current timeout and the current time
     * Next try on this host, if it is in this list, use stored timeout / 2 or if 6 hours delay has expired, retry with original timeout
     * Successful fetch will remove the host from list.
     *
     * @var     array<string, array{0:int, 1:int}>  $hosts
     */
    protected static array $hosts = [];

    /**
     * Grace period for a domain in seconds
     *
     * @var        int
     */
    protected const GRACE_PERIOD = 2 * 60 * 60;   // 2 hours

    /**
     * Constructor.
     *
     * Bypass first argument of clearbricks HttpClient constructor.
     */
    public function __construct(
        protected bool $use_host_cache = true
    ) {
        parent::__construct('');
        $this->setUserAgent(sprintf('Dotclear/%s', App::config()->dotclearVersion()));
        $this->setTimeout(App::config()->queryTimeout());
        if (App::config()->queryStreamTimeout() !== null) {
            $this->setStreamTimeout(App::config()->queryStreamTimeout());
        }
    }

    /**
     * Parse modules feed.
     *
     * @param   string  $url    XML feed URL
     *
     * @return  false|StoreParser   Feed content, StoreParser instance or false
     */
    public function parse(string $url): bool|StoreParser
    {
        $this->validators = [];

        // Extract domain from url
        $host = (string) parse_url($url, PHP_URL_HOST);

        if ($this->use_host_cache && array_key_exists($host, static::$hosts)) {
            // This host has already failed at least once, check grace period
            $grace = static::$hosts[$host][1];
            if (($grace + static::GRACE_PERIOD) < time()) {
                // Remove this host from cache and continue
                unset(static::$hosts[$host]);
                $this->log(sprintf('Remove %s host from list (grace period expired)', $host));
            } else {
                // Use recorded timeout for this host
                $timeout = static::$hosts[$host][0];
                if ($timeout === 0) {
                    $this->log(sprintf('Ignore %s host request (timeout is 0, wait for end of grace period)', $host));

                    // Unecessary to try again, wait for end of grace period
                    return false;
                }
                $this->setTimeout($timeout);
            }
        }

        $result = true;
        if ($this->cache_dir) {
            $result = $this->withCache($url);
        } elseif ($this->force === null) {
            $result = false;
        } elseif (!$this->getModulesXML($url) || $this->getStatus() != '200') {
            $result = false;
        }

        if ($result === false) {
            if ($this->use_host_cache) {
                // Fetch fails
                if (array_key_exists($host, static::$hosts)) {
                    // Store next TTL
                    $timeout                 = static::$hosts[$host][0];
                    static::$hosts[$host][0] = $timeout > 1 ? (int) floor($timeout / 2) : 0;
                    $this->log(sprintf('Set next timeout of %s host to %d seconds', $host, $timeout));
                } else {
                    // Store new host with the next TTL
                    $timeout              = $this->timeout > 1 ? (int) floor($this->timeout / 2) : 0;
                    static::$hosts[$host] = [$timeout, time()];
                    $this->log(sprintf('Add the host %s with next timeout to %d seconds', $host, $timeout));
                }
            }

            return false;
        }
        if ($this->use_host_cache && array_key_exists($host, static::$hosts)) {
            // Fetch succes, remove the domain from the list
            unset(static::$hosts[$host]);
            $this->log(sprintf('Remove %s host from list', $host));
        }

        self::$read_code = static::READ_FROM_SOURCE;

        return new StoreParser($this->getContent());
    }

    protected function log(string $message): void
    {
        // Add new log
        $cur = App::log()->openLogCursor();

        $cur->log_msg   = $message;
        $cur->log_table = 'store';
        $cur->user_id   = App::auth()->userID();

        App::log()->addLog($cur);
    }

    /**
     * Quick parse modules feed.
     *
     * @param   string      $url        XML feed URL
     * @param   string      $cache_dir  Cache directoy or null for no cache
     * @param   null|bool   $force      Force query repository. null to use cache without ttl
     *
     * @return  false|StoreParser   StoreParser instance or false
     */
    public static function quickParse(string $url, ?string $cache_dir = null, ?bool $force = false, bool $use_host_cache = true): bool|StoreParser
    {
        $parser = new self($use_host_cache);
        if ($cache_dir) {
            $parser->setCacheDir($cache_dir);
        }

        $parser->setForce($force);

        return $parser->parse($url);
    }

    /**
     * Reset the host cache list
     */
    public static function resetHostsList(): void
    {
        static::$hosts = [];
    }

    /**
     * Get last parsed reponse from.
     *
     * @return  int     The code
     */
    public static function readCode(): int
    {
        return self::$read_code;
    }

    /**
     * Set cache directory.
     *
     * @param   string  $dir    Cache directory
     *
     * @return  bool    True if cache dierctory is useable
     */
    public function setCacheDir(string $dir): bool
    {
        $this->cache_dir = null;

        if ($dir !== '' && is_dir($dir) && is_writable($dir)) {
            $this->cache_dir = $dir;

            return true;
        }

        return false;
    }

    /**
     * Set cache TTL.
     *
     * @param   string  $str    Cache TTL
     */
    public function setCacheTTL(string $str): void
    {
        $str = trim($str);

        if ($str !== '') {
            $this->cache_ttl = str_starts_with($str, '-') ? $str : '-' . $str;
        }
    }

    /**
     * Set force query repository.
     *
     * @param   null|bool   $force  True to force query
     */
    public function setForce(?bool $force): void
    {
        $this->force = $force;
    }

    /**
     * Request repository XML feed.
     *
     * @todo    Log StoreReader error when repository query fail
     *
     * @throws  Exception
     *
     * @param   string  $url    XML feed URL
     *
     * @return  bool    True on success, else false
     */
    protected function getModulesXML(string $url): bool
    {
        $ssl  = false;
        $host = '';
        $port = 0;
        $path = '';
        $user = '';
        $pass = '';

        if (!self::readURL($url, $ssl, $host, $port, $path, $user, $pass)) {
            return false;
        }
        $this->setHost($host, $port);
        $this->useSSL($ssl);
        $this->setAuthorization($user, $pass);

        try {
            return $this->get($path);
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Get repository modules list using cache.
     *
     * @param   string  $url    XML feed URL
     *
     * @return  StoreParser|false   Feed content or False on fail
     */
    protected function withCache(string $url): bool|StoreParser
    {
        $url_md5     = md5($url);
        $cached_file = sprintf(
            '%s/%s/%s/%s/%s.ser',
            $this->cache_dir,
            $this->cache_file_prefix,
            substr($url_md5, 0, 2),
            substr($url_md5, 2, 2),
            $url_md5
        );

        $may_use_cached = false;

        # Use cache file ?
        if (@file_exists($cached_file) && $this->force !== true) {
            $may_use_cached = true;
            $ts             = @filemtime($cached_file);
            if ($ts > strtotime($this->cache_ttl) || $this->force === null) {
                # Direct cache
                self::$read_code = static::READ_FROM_CACHE;

                /**
                 * @var StoreParser
                 */
                $ret = unserialize((string) file_get_contents($cached_file));

                return $ret;
            }
            $this->setValidator('IfModifiedSince', $ts);
        }

        # Query repository
        if (!$this->getModulesXML($url)) {
            if ($may_use_cached) {
                # Touch cache TTL even if query failed ?
                if ($this->cache_touch_on_fail) {
                    @Files::touch($cached_file);
                }
                # Connection failed - fetched from cache
                self::$read_code = static::READ_FROM_CACHE;

                /**
                 * @var StoreParser
                 */
                $ret = unserialize((string) file_get_contents($cached_file));

                return $ret;
            }

            return false;
        }

        # Parse response
        switch ($this->getStatus()) {
            case '304':
                # Not modified, use cache
                @Files::touch($cached_file);

                self::$read_code = static::READ_FROM_CACHE;

                /**
                 * @var StoreParser
                 */
                $ret = unserialize((string) file_get_contents($cached_file));

                return $ret;
            case '200':
                # Ok, parse feed
                $modules         = new StoreParser($this->getContent());
                self::$read_code = static::READ_FROM_SOURCE;

                try {
                    Files::makeDir(dirname($cached_file), true);
                } catch (Exception) {
                    return $modules;
                }

                if ($fp = @fopen($cached_file, 'wb')) {
                    fwrite($fp, serialize($modules));
                    fclose($fp);
                    Files::inheritChmod($cached_file);
                }

                return $modules;
        }

        return false;
    }

    /**
     * Prepare query.
     *
     * @return  array<int,string>   Query headers
     */
    protected function buildRequest(): array
    {
        $headers = parent::buildRequest();

        # Cache validators
        if (!empty($this->validators)) {
            if (isset($this->validators['IfModifiedSince'])) {
                $headers[] = 'If-Modified-Since: ' . $this->validators['IfModifiedSince'];
            }
            if (isset($this->validators['IfNoneMatch'])) {
                $headers[] = '';
            }
        }

        return $headers;
    }

    /**
     * Tweak query cache validator.
     *
     * @param   string   $key    Validator key
     * @param   int|bool $value  Validator value
     */
    private function setValidator(string $key, int|bool $value): void
    {
        if ($key === 'IfModifiedSince') {
            $value = gmdate('D, d M Y H:i:s', is_numeric($value) ? $value : null) . ' GMT';
        }

        $this->validators[$key] = $value;
    }
}
