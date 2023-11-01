<?php
/**
 * @package     Dotclear
 * @subpackage  Upgrade
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   GPL-2.0-only
 */
declare(strict_types=1);

namespace Dotclear\Core\Upgrade;

use Dotclear\App;
use Dotclear\Helper\File\Files;
use Dotclear\Helper\Network\HttpClient;
use Exception;

class Attic extends Update
{
    /**
     * Releases cache filename.
     *
     * @var     string CACHE_FILENAME
     */
    public const CACHE_FILENAME = 'releases.json';

    /**
     * The releases stack.
     *
     * @var     array<string, array<string, string>>
     */
    private array $releases = [];

    /**
     * Constructor.
     *
     * @param   string  $url        Versions file URL
     * @param   string  $cache_dir  Directory cache path
     */
    public function __construct(string $url, string $cache_dir)
    {
        $this->url        = $url;
        $this->cache_file = $cache_dir . DIRECTORY_SEPARATOR . self::CACHE_FILENAME;
    }

    /**
     * Sets the notify flag.
     *
     * @param   mixed   $n  The new value
     */
    public function setNotify($n): void
    {
    }

    public function selectVersion(string $version): string
    {
        if (!isset($this->releases[$version])) {
            return '';
        }

        $this->version_info['version']  = $version;
        $this->version_info['href']     = $this->releases[$version]['href'];
        $this->version_info['checksum'] = $this->releases[$version]['checksum'];

        return App::config()->backupRoot() . '/' . basename((string) $this->version_info['href']);
    }

    public function getVersionInfo(bool $nocache = false): ?HttpClient
    {
        # Check cached file
        if (is_readable($this->cache_file) && filemtime($this->cache_file) > strtotime($this->cache_ttl) && !$nocache) {
            $contents = file_get_contents($this->cache_file);
            if (!is_string($contents)) {
                return null;
            }
            $contents = json_decode($contents, true);
            if (!is_array($contents)) {
                return null;
            }
        }

        $cache_dir = dirname($this->cache_file);
        $can_write = (!is_dir($cache_dir) && is_writable(dirname($cache_dir)))
        || (!file_exists($this->cache_file) && is_writable($cache_dir))
        || is_writable($this->cache_file);

        # If we can't write file, don't bug host with queries
        if (!$can_write) {
            return null;
        }

        if (!is_dir($cache_dir)) {
            try {
                Files::makeDir($cache_dir);
            } catch (Exception) {
                return null;
            }
        }

        # Try to get latest version number
        try {
            $path   = '';
            $status = 0;

            $http_get = function ($http_url) use (&$status, $path) {
                $client = HttpClient::initClient($http_url, $path);
                if ($client !== false) {
                    $client->setTimeout(App::config()->queryTimeout());
                    $client->setUserAgent($_SERVER['HTTP_USER_AGENT']);
                    $client->get($path);
                    $status = $client->getStatus();
                }

                return $client;
            };

            $client = $http_get($this->url);
            if ($client !== false && $status >= 400) {
                // If original URL uses HTTPS, try with HTTP
                $url_parts = parse_url($client->getRequestURL());
                if (isset($url_parts['scheme']) && $url_parts['scheme'] == 'https') {
                    // Replace https by http in url
                    $this->url = (string) preg_replace('/^https(?=:\/\/)/i', 'http', $this->url);
                    $client    = $http_get($this->url);
                }
            }
            if ($client === false || !$status || $status >= 400) {
                throw new Exception();
            }
            $this->parseContent($client->getContent());
        } catch (Exception) {
            return null;
        }

        # Create cache
        file_put_contents($this->cache_file, json_encode($this->releases));

        return null;
    }

    public function parseContent(string $data): bool
    {
        $this->releases = [];

        try {
            $xml = simplexml_load_string($data);
            if (!$xml) {
                return false;
            }
        } catch(Exception) {
            return false;
        }

        foreach ($xml->subject->release as $release) {
            $this->releases[(string) $release['version']] = [
                'href'     => (string) $release['href'],
                'checksum' => (string) $release['checksum'],
            ];
        }

        uksort($this->releases, fn ($a, $b) => version_compare($a, $b, '>') ? 1 : -1);

        return true;
    }

    /**
     * Get available releases.
     *
     * @param   string  $version    The minimum version
     *
     * @return  array<string, array<string, string>>
     */
    public function getReleases(string $version = ''): array
    {
        if (!$version) {
            return $this->releases;
        }

        $releases = [];
        foreach ($this->releases as $v => $release) {
            if (version_compare($v, $version, '>')) {
                $releases[$v] = $release;
            }
        }

        return $releases;
    }
}
