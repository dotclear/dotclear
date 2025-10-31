<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\Network\Feed;

use Dotclear\Helper\File\Files;
use Dotclear\Helper\Network\HttpClient;
use Exception;

/**
 * @class Parser
 *
 * Features:
 *
 * - Reads RSS 1.0 (rdf), RSS 2.0 and Atom feeds.
 * - HTTP cache negociation support
 * - Cache TTL.
 */
class Reader extends HttpClient
{
    /**
     * Cache file folder
     */
    public const CACHE_FOLDER = 'cbfeed';

    /**
     * User agent
     *
     * @var        string   $user_agent
     */
    protected $user_agent = 'Dotclear Feed Reader/0.2';

    /**
     * Connection timeout (in seconds)
     *
     * @var        int      $timeout
     */
    protected $timeout = 5;

    /**
     * HTTP Cache validators
     *
     * @var        array<string, string>|null   $validators
     */
    protected $validators;

    /**
     * Cache directory path
     */
    protected ?string $cache_dir = null;

    /**
     * Cache TTL (must be a negative string value as "-30 minutes")
     */
    protected string $cache_ttl = '-30 minutes';

    /**
     * Constructor.
     *
     * Does nothing. See {@link parse()} method for URL handling.
     */
    public function __construct()
    {
        parent::__construct('');
    }

    /**
     * Parse Feed
     *
     * Returns a new Parser instance for given URL or false if source URL is
     * not a valid feed.
     *
     * @uses Parser
     *
     * @param string    $url            Feed URL
     *
     * @return Parser|false
     */
    public function parse(string $url): Parser|bool
    {
        $this->validators = [];
        if ($this->cache_dir) {
            return $this->withCache($url);
        }
        if (!$this->getFeed($url)) {
            return false;
        }

        if ($this->getStatus() != '200') {
            return false;
        }

        return new Parser($this->getContent());
    }

    /**
     * Quick Parse
     *
     * This static method returns a new {@link Parser} instance for given URL. If a
     * <var>$cache_dir</var> is specified, cache will be activated.
     *
     * @param string    $url            Feed URL
     * @param string    $cache_dir      Cache directory
     *
     * @return Parser|false
     */
    public static function quickParse(string $url, ?string $cache_dir = null): Parser|bool
    {
        $parser = new self();
        if ($cache_dir) {
            $parser->setCacheDir($cache_dir);
        }

        return $parser->parse($url);
    }

    /**
     * Set Cache Directory
     *
     * Returns true and sets {@link $cache_dir} property if <var>$dir</var> is
     * a writable directory. Otherwise, returns false.
     *
     * @param string    $dir            Cache directory
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
     * Set Cache TTL
     *
     * Sets cache TTL. <var>$str</var> is a interval readable by strtotime
     * (-3 minutes, -2 hours, etc.)
     *
     * @param string    $str            TTL
     */
    public function setCacheTTL(string $str): void
    {
        $str = trim($str);
        if ($str !== '') {
            if (!str_starts_with($str, '-')) {
                $str = '-' . $str;
            }
            $this->cache_ttl = $str;
        }
    }

    /**
     * Feed Content
     *
     * Returns feed content for given URL.
     *
     * @param string    $url            Feed URL
     */
    protected function getFeed(string $url): bool
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

        return $this->get($path);
    }

    /**
     * Cache content
     *
     * Returns Parser object from cache if present or write it to cache and
     * returns result.
     *
     * @param string    $url            Feed URL
     *
     * @return Parser|false
     */
    protected function withCache(string $url): Parser|bool
    {
        $url_md5     = md5($url);
        $cached_file = sprintf(
            '%s/%s/%s/%s/%s.xml',
            $this->cache_dir,
            self::CACHE_FOLDER,
            substr($url_md5, 0, 2),
            substr($url_md5, 2, 2),
            $url_md5
        );

        $may_use_cached = false;

        if (@file_exists($cached_file)) {
            // Check file content
            $xml = (string) file_get_contents($cached_file);
            if ($xml !== '') {
                $may_use_cached = true;
                $timestamp      = (int) @filemtime($cached_file);
                if ($timestamp > strtotime($this->cache_ttl)) {
                    // Direct cache
                    return new Parser((string) file_get_contents($cached_file));
                }

                $this->validators['IfModifiedSince'] = gmdate('D, d M Y H:i:s', $timestamp) . ' GMT';
            } else {
                // Cache file is empty, remove it
                @unlink($cached_file);
            }
        }

        if (!$this->getFeed($url)) {
            if ($may_use_cached && @file_exists($cached_file)) {
                // connection failed - fetched from cache
                return new Parser((string) file_get_contents($cached_file));
            }

            return false;
        }

        switch ($this->getStatus()) {
            case '304':
                @Files::touch($cached_file);

                return new Parser((string) file_get_contents($cached_file));
            case '200':
                $feed    = new Parser($this->getContent());
                $content = $feed->asXML();
                if ($content) {
                    try {
                        // Add feed in cache
                        Files::makeDir(dirname($cached_file), true);
                        if ($fp = @fopen($cached_file, 'wb')) {
                            fwrite($fp, (string) $content);
                            fclose($fp);
                            Files::inheritChmod($cached_file);
                        }
                    } catch (Exception) {
                        if (@file_exists($cached_file)) {
                            // Remove cache file
                            @unlink($cached_file);
                        }
                    }
                }

                return $feed;
        }

        return false;
    }

    /**
     * Build request
     *
     * Adds HTTP cache headers to common headers.
     *
     * {@inheritdoc}
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
}
