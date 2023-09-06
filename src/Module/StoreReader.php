<?php
/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Module;

use Dotclear\Helper\File\Files;
use Dotclear\Helper\Network\HttpClient;
use Exception;

/**
 * Repository modules XML feed reader.
 *
 * Provides an object to parse XML feed of modules from repository.
 *
 * @since 2.6
 */
class StoreReader extends HttpClient
{
    /** @var    int  Read nothing */
    public const READ_FROM_NONE = -1;

    /** @var    int  Read from local cache file */
    public const READ_FROM_CACHE = 0;

    /** @var    int  Read from repository server */
    public const READ_FROM_SOURCE = 1;

    /** @var string     Default modules store cache sub folder */
    public const CACHE_FOLDER = 'dcrepo';

    /**
     * User agent used to query repository
     *
     * @var    string
     */
    protected $user_agent = 'DotClear.org RepoBrowser/0.1';

    /**
     * HTTP Cache validators
     *
     * @var    array|null
     */
    protected $validators = null;

    /**
     * Cache temporary directory
     *
     * @var    string|null
     */
    protected $cache_dir = null;

    /**
     * Cache file prefix
     *
     * @var    string
     */
    protected $cache_file_prefix = self::CACHE_FOLDER;

    /**
     * Cache TTL
     *
     * @var    string
     */
    protected $cache_ttl = '-1440 minutes';

    /**
     * 'Cache' TTL on server failed
     *
     * @var    bool     */
    protected $cache_touch_on_fail = true;

    /**
     * Force query server
     *
     * True: query server even if cache is not expired
     * False: query server if there's no cache or cache is expired
     * Null: query server only if there's no cache (we don't look at ttl)
     *
     * @var    null|bool
     */
    protected $force = false;

    /**
     * Last response source (from cache or repository).
     *
     * @var     int
     */
    private static int $read_code = self::READ_FROM_NONE;

    /**
     * Constructor.
     *
     * Bypass first argument of clearbricks HttpClient constructor.
     */
    public function __construct()
    {
        parent::__construct('');
        $this->setUserAgent(sprintf('Dotclear/%s', DC_VERSION));
        $this->setTimeout(DC_QUERY_TIMEOUT);
    }

    /**
     * Parse modules feed.
     *
     * @param    string    $url        XML feed URL
     *
     * @return   false|StoreParser  Feed content, StoreParser instance or false
     */
    public function parse(string $url): bool|StoreParser
    {
        $this->validators = [];

        if ($this->cache_dir) {
            return $this->withCache($url);
        } elseif ($this->force === null) {
            return false;
        } elseif (!$this->getModulesXML($url) || $this->getStatus() != '200') {
            return false;
        }

        self::$read_code = static::READ_FROM_SOURCE;

        return new StoreParser($this->getContent());
    }

    /**
     * Quick parse modules feed.
     *
     * @param    string     $url          XML feed URL
     * @param    string     $cache_dir    Cache directoy or null for no cache
     * @param    null|bool  $force        Force query repository. null to use cache without ttl
     *
     * @return   mixed     Feed content, StoreParser instance or false
     */
    public static function quickParse(string $url, ?string $cache_dir = null, ?bool $force = false)
    {
        $parser = new self();
        if ($cache_dir) {
            $parser->setCacheDir($cache_dir);
        }

        $parser->setForce($force);

        return $parser->parse($url);
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
     * @param    string    $dir        Cache directory
     *
     * @return    bool    True if cache dierctory is useable
     */
    public function setCacheDir(string $dir): bool
    {
        $this->cache_dir = null;

        if (!empty($dir) && is_dir($dir) && is_writeable($dir)) {
            $this->cache_dir = $dir;

            return true;
        }

        return false;
    }

    /**
     * Set cache TTL.
     *
     * @param    string    $str        Cache TTL
     */
    public function setCacheTTL(string $str): void
    {
        $str = trim($str);

        if (!empty($str)) {
            $this->cache_ttl = substr($str, 0, 1) == '-' ? $str : '-' . $str;
        }
    }

    /**
     * Set force query repository.
     *
     * @param    null|bool    $force    True to force query
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
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get repository modules list using cache.
     *
     * @param    string    $url        XML feed URL
     *
     * @return   mixed     Feed content or False on fail
     */
    protected function withCache(string $url)
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

                return unserialize(file_get_contents($cached_file));
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

                return unserialize(file_get_contents($cached_file));
            }

            return false;
        }

        # Parse response
        switch ($this->getStatus()) {
            # Not modified, use cache
            case '304':
                @Files::touch($cached_file);

                self::$read_code = static::READ_FROM_CACHE;

                return unserialize(file_get_contents($cached_file));
                # Ok, parse feed
            case '200':
                $modules         = new StoreParser($this->getContent());
                self::$read_code = static::READ_FROM_SOURCE;

                try {
                    Files::makeDir(dirname($cached_file), true);
                } catch (Exception $e) {
                    return $modules;
                }

                if (($fp = @fopen($cached_file, 'wb'))) {
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
     * @return    array    Query headers
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
     * @param    string    $key        Validator key
     * @param    mixed     $value      Validator value
     */
    private function setValidator(string $key, $value): void
    {
        if ($key == 'IfModifiedSince') {
            $value = gmdate('D, d M Y H:i:s', $value) . ' GMT';
        }

        $this->validators[$key] = $value;
    }
}
