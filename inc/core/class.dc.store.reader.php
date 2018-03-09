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

if (!defined('DC_RC_PATH')) {return;}

class dcStoreReader extends netHttp
{
    /** @var    string    User agent used to query repository */
    protected $user_agent = 'DotClear.org RepoBrowser/0.1';
    /** @var    integer    User agent used to query repository */
    protected $timeout = 5;
    /** @var    array     HTTP Cache validators */
    protected $validators = null;
    /** @var    string    Cache temporary directory */
    protected $cache_dir = null;
    /** @var    string    Cache file prefix */
    protected $cache_file_prefix = 'dcrepo';
    /** @var    integer    Cache TTL */
    protected $cache_ttl = '-30 minutes';
    /** @var    boolean    'Cache' TTL on server failed */
    protected $cache_touch_on_fail = true;
    /** @var    boolean    Force query server */
    protected $force = false;

    /**
     * Constructor.
     *
     * Bypass first argument of clearbricks netHttp constructor.
     */
    public function __construct()
    {
        parent::__construct('');
        $this->setUserAgent(sprintf('Dotclear/%s)', DC_VERSION));
    }

    /**
     * Parse modules feed.
     *
     * @param    string    $url        XML feed URL
     * @return    object    dcStore instance
     */
    public function parse($url)
    {
        $this->validators = array();

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
     * @param    string    $url        XML feed URL
     * @param    string    $cache_dir    Cache directoy or null for no cache
     * @param    boolean    $force        Force query repository
     * @return    object    Self instance
     */
    public static function quickParse($url, $cache_dir = null, $force = false)
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
     * @return    boolean    True if cache dierctory is useable
     */
    public function setCacheDir($dir)
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
    public function setCacheTTL($str)
    {
        $str = trim($str);

        if (!empty($str)) {
            $this->cache_ttl = substr($str, 0, 1) == '-' ? $str : '-' . $str;
        }
    }

    /**
     * Set force query repository.
     *
     * @param    boolean    $force    True to force query
     */
    public function setForce($force)
    {
        $this->force = $force;
    }

    /**
     * Get repository XML feed URL content.
     *
     * @param    string    $url        XML feed URL
     * @return    string    Feed content
     */
    protected function getModulesXML($url)
    {
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
     * @return    array    Feed content or False on fail
     */
    protected function withCache($url)
    {
        $url_md5     = md5($url);
        $cached_file = sprintf('%s/%s/%s/%s/%s.ser',
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
                    @files::touch($cached_file);
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
                @files::touch($cached_file);
                return unserialize(file_get_contents($cached_file));
            # Ok, parse feed
            case '200':
                if ($modules = new dcStoreParser($this->getContent())) {
                    try {
                        files::makeDir(dirname($cached_file), true);
                    } catch (Exception $e) {
                        return $modules;
                    }

                    if (($fp = @fopen($cached_file, 'wb'))) {
                        fwrite($fp, serialize($modules));
                        fclose($fp);
                        files::inheritChmod($cached_file);
                    }
                    return $modules;
                }
        }

        return false;
    }

    /**
     * Prepare query.
     *
     * @return    array    Query headers
     */
    protected function buildRequest()
    {
        $headers = parent::buildRequest();

        # Cache validators
        if (!empty($this->validators)) {
            if (isset($this->validators['IfModifiedSince'])) {
                $headers[] = 'If-Modified-Since: ' . $this->validators['IfModifiedSince'];
            }
            if (isset($this->validators['IfNoneMatch'])) {
                if (is_array($this->validators['IfNoneMatch'])) {
                    $etags = implode(',', $this->validators['IfNoneMatch']);
                } else {
                    $etags = $this->validators['IfNoneMatch'];
                }
                $headers[] = '';
            }
        }

        return $headers;
    }

    /**
     * Tweak query cache validator.
     *
     * @param    string    $key        Validator key
     * @param    string    $value        Validator value
     */
    private function setValidator($key, $value)
    {
        if ($key == 'IfModifiedSince') {
            $value = gmdate('D, d M Y H:i:s', $value) . ' GMT';
        }

        $this->validators[$key] = $value;
    }
}
