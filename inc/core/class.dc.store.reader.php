<?php
/**
 * @brief Repository modules XML feed reader
 *
 * Provides an object to parse XML feed of modules from repository.
 *
 * @package Dotclear
 * @subpackage Core
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 *
 * @since 2.6
 */

use Dotclear\Helper\File\Files;

class dcStoreReader extends netHttp
{
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
    protected $cache_file_prefix = 'dcrepo';

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
     * @var    bool
     */
    protected $force = false;

    /**
     * Constructor.
     *
     * Bypass first argument of clearbricks netHttp constructor.
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
     * @return   mixed     Feed content, dcStoreParser instance or false
     */
    public function parse(string $url)
    {
        $this->validators = [];

        if ($this->cache_dir) {
            return $this->withCache($url);
        } elseif (!$this->getModulesXML($url) || $this->getStatus() != '200') {
            return false;
        }

        return new dcStoreParser($this->getContent());
    }

    /**
     * Quick parse modules feed.
     *
     * @param    string    $url          XML feed URL
     * @param    string    $cache_dir    Cache directoy or null for no cache
     * @param    bool      $force        Force query repository
     *
     * @return   mixed     Feed content, dcStoreParser instance or false
     */
    public static function quickParse(string $url, ?string $cache_dir = null, bool $force = false)
    {
        $parser = new self();
        if ($cache_dir) {
            $parser->setCacheDir($cache_dir);
        }
        if ($force) {
            $parser->setForce($force);
        }

        return $parser->parse($url);
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
     * @param    bool    $force    True to force query
     */
    public function setForce(bool $force): void
    {
        $this->force = $force;
    }

    /**
     * Request repository XML feed.
     *
     * @param    string    $url        XML feed URL
     *
     * @return   bool      True on success, else false
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
            // @todo Log error when repository query fail
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
        if (@file_exists($cached_file) && !$this->force) {
            $may_use_cached = true;
            $ts             = @filemtime($cached_file);
            if ($ts > strtotime($this->cache_ttl)) {
                # Direct cache
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
                return unserialize(file_get_contents($cached_file));
            }

            return false;
        }

        # Parse response
        switch ($this->getStatus()) {
            # Not modified, use cache
            case '304':
                @Files::touch($cached_file);

                return unserialize(file_get_contents($cached_file));
                # Ok, parse feed
            case '200':
                $modules = new dcStoreParser($this->getContent());

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
