<?php

/**
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\Network;

use Dotclear\Helper\Network\Socket\Socket;
use Exception;

/**
 * @class HttpClient
 *
 * HTTP Client
 *
 * Features:
 *
 * - Implements a useful subset of the HTTP 1.0 and 1.1 protocols.
 * - Includes cookie support.
 * - Ability to set the user agent and referal fields.
 * - Can automatically handle redirected pages.
 * - Can be used for multiple requests, with any cookies sent by the server resent
 *   for each additional request.
 * - Support for gzip encoded content, which can dramatically reduce the amount of
 *   bandwidth used in a transaction.
 * - Object oriented, with static methods providing a useful shortcut for simple
 *   requests.
 * - The ability to only read the page headers - useful for implementing tools such
 *   as link checkers.
 * - Support for file uploads.
 *
 * This class is fully based on Simon Willison's HTTP Client in version 0.9 of
 * 6th April 2003 - http://scripts.incutio.com/httpclient/
 *
 * Changes since fork:
 *
 * - PHP7.4+ with Exception support
 * - Charset support in POST requests
 * - Proxy support through HTTP_PROXY_HOST and HTTP_PROXY_PORT or setProxy()
 * - SSL support (if possible)
 * - Handles redirects on other hosts
 * - Configurable output
 */
class HttpClient extends Socket
{
    /**
     * Server host
     *
     * @var string  $host
     */
    protected $host;

    /**
     * Server port
     *
     * @var int     $port
     */
    protected $port;

    /**
     * Query path
     *
     * @var string  $path
     */
    protected $path;

    /**
     * HTTP method
     *
     * @var string  $method
     */
    protected $method;

    /**
     * HTTP POST data
     *
     * @var string  $postdata
     */
    protected $postdata = '';

    /**
     * HTTP POST charset
     *
     * @var string  $post_charset
     */
    protected $post_charset;

    /**
     * Stack of cookies sent
     *
     * @var array<string, string>   $cookies
     */
    protected $cookies = [];

    /**
     * HTTP referer
     *
     * @var string  $referer
     */
    protected $referer;

    /**
     * HTTP accept mime-types
     *
     * @var string[]    $mime_types
     */
    protected $mime_types = [
        'text/xml',
        'application/xml',
        'application/xhtml+xml',
        'text/html',
        'text/plain',
        'image/png',
        'image/jpeg',
        'image/gif',
        'image/webp',
        'image/avif',
        '*/*',
    ];

    /**
     * HTTP accept header
     *
     * Composed with $mime_types (see above)
     */
    protected string $accept = '';

    /**
     * HTTP accept encoding
     *
     * @var string  $accept_encoding
     */
    protected $accept_encoding = 'gzip';

    /**
     * HTTP accept language
     *
     * @var string  $accept_language
     */
    protected $accept_language = 'en-us';

    /**
     * HTTP User Agent
     *
     * @var string  $user_agent
     */
    protected $user_agent = 'Dotclear HTTP Client';

    /**
     * HTTP optional headers
     *
     * @var string[]    $more_headers
     */
    protected $more_headers = [];

    /**
     * Connection timeout (in seconds)
     *
     * @var int     $timeout
     */
    protected $timeout = 10;

    /**
     * Stream timeout (in seconds)
     *
     * @var int     $stream_timeout
     */
    protected $stream_timeout;

    /**
     * Use SSL connection
     *
     * @var bool    $use_ssl
     */
    protected $use_ssl = false;

    /**
     * Use gzip transfert
     *
     * @var bool    $use_gzip
     */
    protected $use_gzip = false;

    /**
     * Allow persistant cookies
     *
     * @var bool    $persist_cookies
     */
    protected $persist_cookies = true;

    /**
     * Allow persistant referers
     *
     * @var bool    $persist_referers
     */
    protected $persist_referers = true;

    /**
     * Use debug mode
     *
     * @var bool    $debug
     */
    protected $debug = false;

    /**
     * Follow redirects
     *
     * @var bool    $handle_redirects
     */
    protected $handle_redirects = true;

    /**
     * Maximum redirects to follow
     *
     * @var int     $max_redirects
     */
    protected $max_redirects = 5;

    /**
     * Retrieve only headers
     *
     * @var bool    $headers_only
     */
    protected $headers_only = false;

    /**
     * Authentication user name
     *
     * @var string  $username
     */
    protected $username;

    /**
     * Authentication password
     *
     * @var string  $password
     */
    protected $password;

    /**
     * Proxy server host
     */
    protected ?string $proxy_host = null;

    /**
     * Proxy server port
     */
    protected ?int $proxy_port = null;

    // Response vars

    /**
     * HTTP Status code
     *
     * @var int     $status
     */
    protected $status;

    /**
     * HTTP Status string
     *
     * @var string  $status_string
     */
    protected $status_string;

    /**
     * Response headers
     *
     * @var array<string, string|string[]>  $headers
     */
    protected $headers = [];

    /**
     * Response body
     *
     * @var string  $content
     */
    protected $content = '';

    // Tracker variables

    /**
     * Internal redirects count
     *
     * @var int     $redirect_count
     */
    protected $redirect_count = 0;

    /**
     * Internal cookie host
     *
     * @var string  $cookie_host
     */
    protected $cookie_host = '';

    // Output module (null is this->content)

    /**
     * Output stream name
     *
     * @var string|null     $output
     */
    protected $output;

    /**
     * Output resource
     *
     * @var resource|null|false     $output_h
     */
    protected $output_h;

    /**
     * Verify peer flag
     *
     * @var bool    $verify_peer
     */
    protected $verify_peer = true;

    /**
     * Constructor.
     *
     * Takes the web server host, an optional port and timeout.
     *
     * @param string    $host            Server host
     * @param int       $port            Server port
     * @param int|null  $timeout         Connection timeout (in seconds)
     * @param int|null  $stream_timeout  Stream timeout (in seconds)
     */
    public function __construct($host, int $port = 80, ?int $timeout = null, ?int $stream_timeout = null)
    {
        if (defined('SOCKET_VERIFY_PEER')) {
            $this->verifyPeer(constant('SOCKET_VERIFY_PEER'));
        }

        $this->accept = implode(',', $this->mime_types);

        $this->setHost($host, $port);

        if (defined('HTTP_PROXY_HOST') && defined('HTTP_PROXY_PORT')) {
            $this->setProxy(constant('HTTP_PROXY_HOST'), constant('HTTP_PROXY_PORT'));
        }

        if ($timeout) {
            $this->setTimeout($timeout);
        }
        $this->_timeout = &$this->timeout;

        if ($stream_timeout) {
            $this->setStreamTimeout($stream_timeout);
        }
        $this->_stream_timeout = &$this->stream_timeout;
    }

    /**
     * GET Request
     *
     * Executes a GET request for the specified path. If <var>$data</var> is
     * specified, appends it to a query string as part of the get request.
     * <var>$data</var> can be an array of key value pairs, in which case a
     * matching query string will be constructed. Returns true on success.
     *
     * @param string                        $path            Request path
     * @param false|array<string, mixed>    $data            Request parameters
     */
    public function get(string $path, $data = false): bool
    {
        $this->path   = $path;
        $this->method = 'GET';

        if ($data !== false) {
            $this->path .= '?' . $this->buildQueryString($data);
        }

        return $this->doRequest();
    }

    /**
     * POST Request
     *
     * Executes a POST request for the specified path. If <var>$data</var> is
     * specified, appends it to a query string as part of the get request.
     * <var>$data</var> can be an array of key value pairs, in which case a
     * matching query string will be constructed. Returns true on success.
     *
     * @param string                        $path            Request path
     * @param array<string, mixed>|string   $data            Request parameters
     * @param string|null                   $charset         Request charset
     */
    public function post(string $path, $data, ?string $charset = null): bool
    {
        if ($charset) {
            $this->post_charset = $charset;
        }
        $this->path     = $path;
        $this->method   = 'POST';
        $this->postdata = $this->buildQueryString($data);

        return $this->doRequest();
    }

    /**
     * Query String Builder
     *
     * Prepares Query String for HTTP request. <var>$data</var> is an associative
     * array of arguments.
     *
     * @param array<string, mixed>|string        $data            Query data
     */
    protected function buildQueryString($data): string
    {
        if (is_array($data)) {
            $query_string = [];
            # Change data in to postable data
            foreach ($data as $key => $val) {
                if (is_array($val)) {
                    foreach ($val as $subval) {
                        $query_string[] = urlencode((string) $key) . '=' . urlencode((string) $subval);
                    }
                } else {
                    $query_string[] = urlencode((string) $key) . '=' . urlencode((string) $val);
                }
            }
            $query_string = implode('&', $query_string);
        } else {
            $query_string = $data;
        }

        return (string) $query_string;
    }

    /**
     * Do Request
     *
     * Sends HTTP request and stores status, headers, content object properties.
     */
    protected function doRequest(): bool
    {
        if ($this->proxy_host !== null && $this->proxy_port !== null) {
            $this->_host      = $this->proxy_host;
            $this->_port      = $this->proxy_port;
            $this->_transport = '';
        } else {
            $this->_host      = $this->host;
            $this->_port      = $this->port;
            $this->_transport = $this->use_ssl ? 'ssl://' : '';
        }

        // Reset all the variables that should not persist between requests
        $this->headers = [];
        $in_headers    = true;
        $this->outputOpen();

        $request = $this->buildRequest();
        $this->debug('Request', $request);

        $this->open();
        $this->debug('Connecting to ' . $this->_transport . $this->_host . ':' . $this->_port);
        foreach ($this->write($request) as $index => $line) {   // @phpstan-ignore-line
            if ($line !== false) {
                // Deal with first line of returned data
                if ($index == 0) {
                    $line = rtrim($line, "\r\n");
                    if (!preg_match('/HTTP\/(\\d\\.\\d)\\s*(\\d+)\\s*(.*)/', $line, $m)) {
                        throw new Exception('Status code line invalid: ' . $line);
                    }
                    $this->status        = (int) $m[2];
                    $this->status_string = $m[3];
                    $this->debug($line);

                    continue;
                }

                // Read headers
                if ($in_headers) {
                    $line = rtrim($line, "\r\n");
                    if ($line === '') {
                        $in_headers = false;
                        $this->debug('Received Headers', $this->headers);
                        if ($this->headers_only) {
                            break;
                        }

                        continue;
                    }

                    if (!preg_match('/([^:]+):\\s*(.*)/', $line, $m)) {
                        // Skip to the next header
                        continue;
                    }
                    $key = strtolower(trim($m[1]));
                    $val = trim($m[2]);

                    // Deal with the possibility of multiple headers of same name
                    if (isset($this->headers[$key])) {
                        if (is_array($this->headers[$key])) {
                            $this->headers[$key][] = $val;
                        } else {
                            $this->headers[$key] = [$this->headers[$key], $val];
                        }
                    } else {
                        $this->headers[$key] = $val;
                    }

                    continue;
                }

                // We're not in the headers, so append the line to the contents
                $this->outputWrite($line);
            }
        }
        $this->close();
        $this->outputClose();

        // If data is compressed, uncompress it
        if ($this->getHeader('content-encoding') && $this->use_gzip) {
            $this->debug('Content is gzip encoded, unzipping it');
            # See http://www.php.net/manual/en/function.gzencode.php
            $this->content = (string) gzinflate(substr($this->content, 10));
        }

        // If $persist_cookies, deal with any cookies
        if ($this->persist_cookies && $this->getHeader('set-cookie') && $this->host === $this->cookie_host) {
            $cookies = $this->headers['set-cookie'];
            if (!is_array($cookies)) {
                $cookies = [$cookies];
            }

            foreach ($cookies as $cookie) {
                if (preg_match('/([^=]+)=([^;]+);/', (string) $cookie, $m)) {
                    $this->cookies[$m[1]] = $m[2];
                }
            }

            // Record domain of cookies for security reasons
            $this->cookie_host = $this->host;
        }

        // If $persist_referers, set the referer ready for the next request
        if ($this->persist_referers) {
            $this->debug('Persisting referer: ' . $this->getRequestURL());
            $this->referer = $this->getRequestURL();
        }

        // Finally, if handle_redirects and a redirect is sent, do that
        if ($this->handle_redirects) {
            if (++$this->redirect_count >= $this->max_redirects) {
                $this->redirect_count = 0;

                throw new Exception('Number of redirects exceeded maximum (' . $this->max_redirects . ')');
            }

            $location = $this->headers['location'] ?? '';
            $uri      = $this->headers['uri']      ?? '';

            if (is_array($location)) {
                // Header may contain an array as value
                $location = $location[0];
            }
            if (is_array($uri)) {
                // Header may contain an array as value
                $uri = $uri[0];
            }

            $redir_ssl  = false;
            $redir_host = '';
            $redir_port = 0;
            $redir_path = '';
            $redir_user = '';
            $redir_pass = '';
            if (($location || $uri) && self::readURL($location . $uri, $redir_ssl, $redir_host, $redir_port, $redir_path, $redir_user, $redir_pass)) {
                // If we try to move on another host, remove cookies, user and pass
                if ($redir_host !== $this->host || $redir_port !== $this->port) {
                    $this->cookies = [];
                    $this->setAuthorization(null, null);
                    $this->setHost($redir_host, $redir_port);
                }
                $this->useSSL($redir_ssl);
                $this->debug('Redirect to: ' . $location . $uri);

                return $this->get($redir_path);
            }
            $this->redirect_count = 0;
        }

        return true;
    }

    /**
     * Prepare Request
     *
     * Prepares HTTP request and returns an array of HTTP headers.
     *
     * @return array<string>
     */
    protected function buildRequest(): array
    {
        $headers = [];

        $path = $this->proxy_host !== null ? $this->getRequestURL() : $this->path;

        // Using 1.1 leads to all manner of problems, such as "chunked" encoding
        $headers[] = $this->method . ' ' . $path . ' HTTP/1.0';

        $headers[] = 'Host: ' . $this->host;
        $headers[] = 'User-Agent: ' . $this->user_agent;
        $headers[] = 'Accept: ' . $this->accept;

        if ($this->use_gzip) {
            $headers[] = 'Accept-encoding: ' . $this->accept_encoding;
        }
        $headers[] = 'Accept-language: ' . $this->accept_language;

        if ($this->referer) {
            $headers[] = 'Referer: ' . $this->referer;
        }

        // Cookies
        if ($this->cookies) {
            $cookie = 'Cookie: ';
            foreach ($this->cookies as $key => $value) {
                $cookie .= $key . '=' . $value . ';';
            }
            $headers[] = $cookie;
        }

        // X-Forwarded-For
        $xforward = [];
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $xforward[] = $_SERVER['REMOTE_ADDR'];
        }
        if ($this->proxy_host !== null && isset($_SERVER['SERVER_ADDR'])) {
            $xforward[] = $_SERVER['SERVER_ADDR'];
        }
        if ($xforward !== []) {
            $headers[] = 'X-Forwarded-For: ' . implode(', ', $xforward);
        }

        // Basic authentication
        if ($this->username && $this->password) {
            $headers[] = 'Authorization: Basic ' . base64_encode($this->username . ':' . $this->password);
        }

        $headers = [...$headers, ...$this->more_headers];

        // If this is a POST, set the content type and length
        if ($this->postdata) {
            $needed = true;
            foreach ($headers as $value) {
                if (preg_match('/^Content-Type: /', $value)) {
                    // Content-Type already set in headers, ignore
                    $needed = false;

                    break;
                }
            }
            if ($needed) {
                $content_type = 'Content-Type: application/x-www-form-urlencoded';
                if ($this->post_charset) {
                    $content_type .= '; charset=' . $this->post_charset;
                }
                $headers[] = $content_type;
            }
            $headers[] = 'Content-Length: ' . strlen($this->postdata);
            $headers[] = '';
            $headers[] = $this->postdata;
        }

        return $headers;
    }

    /**
     * Open Output
     *
     * Initializes output handler if {@link $output} property is not null and
     * is a valid resource stream.
     */
    protected function outputOpen(): void
    {
        if ($this->output) {
            if (($this->output_h = @fopen($this->output, 'wb')) === false) {
                throw new Exception('Unable to open output stream ' . $this->output);
            }
        } else {
            $this->content = '';
        }
    }

    /**
     * Close Output
     *
     * Closes output module if exists.
     */
    protected function outputClose(): void
    {
        if ($this->output && is_resource($this->output_h)) {
            fclose($this->output_h);
        }
    }

    /**
     * Write Output
     *
     * Writes data to output module.
     *
     * @param string    $content                Data content
     */
    protected function outputWrite(string $content): void
    {
        if ($this->output && is_resource($this->output_h)) {
            fwrite($this->output_h, $content);
        } else {
            $this->content .= $content;
        }
    }

    /**
     * Get Status
     *
     * Returns the status code of the response - 200 means OK, 404 means file not
     * found, etc.
     */
    public function getStatus(): int
    {
        return (int) $this->status;
    }

    /**
     * Get Content
     *
     * Returns the content of the HTTP response. This is usually an HTML document.
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Response Headers
     *
     * Returns the HTTP headers returned by the server as an associative array.
     *
     * @return array<string, string|array<int, string>>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Response Header
     *
     * Returns the specified response header, or false if it does not exist.
     *
     * @param string    $header            Header name
     *
     * @return string|false|array<int, string>
     */
    public function getHeader($header): array|bool|string
    {
        $header = strtolower($header);

        return $this->headers[$header] ?? false;
    }

    /**
     * Cookies
     *
     * Returns an array of cookies set by the server.
     *
     * @return array<string, string>
     */
    public function getCookies(): array
    {
        return $this->cookies;
    }

    /**
     * Request URL
     *
     * Returns the full URL that has been requested.
     */
    public function getRequestURL(): string
    {
        $url = 'http' . ($this->use_ssl ? 's' : '') . '://' . $this->host;
        if (!$this->use_ssl && $this->port != 80 || $this->use_ssl && $this->port != 443) {
            $url .= ':' . $this->port;
        }

        return $url . $this->path;
    }

    /**
     * Sets server host and port.
     *
     * @param string    $host            Server host
     * @param int       $port            Server port
     */
    public function setHost(string $host, int $port = 80): void
    {
        $this->host = $host;
        $this->port = abs($port);
    }

    /**
     * Sets proxy host and port.
     *
     * @param string    $host                Proxy host
     * @param int       $port                Proxy port
     */
    public function setProxy(?string $host, ?int $port = 8080): void
    {
        if ($host === null || $port === null) {
            $this->proxy_host = null;
            $this->proxy_port = null;

            return;
        }

        $this->proxy_host = $host;
        $this->proxy_port = abs($port);
    }

    /**
     * Sets connection timeout.
     *
     * @param int    $timeout                Connection timeout (in seconds)
     */
    public function setTimeout(int $timeout): void
    {
        $this->timeout = abs($timeout);
    }

    /**
     * Sets stream timeout.
     *
     * @param int    $timeout                Stream timeout (in seconds)
     */
    public function setStreamTimeout(int $timeout): void
    {
        $this->stream_timeout = abs($timeout);
    }

    /**
     * User Agent String
     *
     * Sets the user agent string to be used in the request. Default is
     * "Dotclear HTTP Client".
     *
     * @param string    $user_agent            User agent string
     */
    public function setUserAgent(string $user_agent): void
    {
        $this->user_agent = $user_agent;
    }

    /**
     * HTTP Authentication
     *
     * Sets the HTTP authorization username and password to be used in requests.
     * Don't forget to unset this in subsequent requests to different servers.
     *
     * @param string    $username            User name
     * @param string    $password            Password
     */
    public function setAuthorization(?string $username, ?string $password): void
    {
        $this->username = (string) $username;
        $this->password = (string) $password;
    }

    /**
     * Add Header
     *
     * Sets additionnal header to be sent with the request.
     *
     * @param string    $header            Full header definition
     */
    public function setMoreHeader(string $header): void
    {
        $this->more_headers[] = $header;
    }

    /**
     * Empty additionnal headers
     */
    public function voidMoreHeaders(): void
    {
        $this->more_headers = [];
    }

    /**
     * Set Cookies
     *
     * Sets the cookies to be sent in the request. Takes an array of name value
     * pairs.
     *
     * @param array<string, string>        $cookies            Cookies array
     */
    public function setCookies(array $cookies): void
    {
        $this->cookies = $cookies;
    }

    /**
     * Enable / Disable SSL
     *
     * Sets SSL connection usage.
     *
     * @param bool    $flag            Enable/Disable SSL
     */
    public function useSSL(bool $flag): void
    {
        if ($flag) {
            if (!in_array('ssl', stream_get_transports())) {
                throw new Exception('SSL support is not available');
            }
            $this->use_ssl = true;
        } else {
            $this->use_ssl = false;
        }
    }

    /**
     * Use Gzip
     *
     * Specifies if the client should request gzip encoded content from the server
     * (saves bandwidth but can increase processor time). Default behaviour is
     * false.
     *
     * @param bool    $flag            Enable/Disable Gzip
     */
    public function useGzip(bool $flag): void
    {
        $this->use_gzip = $flag;
    }

    /**
     * Persistant Cookies
     *
     * Specify if the client should persist cookies between requests. Default
     * behaviour is true.
     *
     * @param bool    $flag            Enable/Disable Persist Cookies
     */
    public function setPersistCookies(bool $flag): void
    {
        $this->persist_cookies = $flag;
    }

    /**
     * Persistant Referrers
     *
     * Specify if the client should use the URL of the previous request as the
     * referral of a subsequent request. Default behaviour is true.
     *
     * @param bool    $flag            Enable/Disable Persistant Referrers
     */
    public function setPersistReferers(bool $flag): void
    {
        $this->persist_referers = $flag;
    }

    /**
     * Enable / Disable Redirects
     *
     * Specify if the client should automatically follow redirected requests.
     * Default behaviour is true.
     *
     * @param bool    $flag            Enable/Disable Redirects
     */
    public function setHandleRedirects(bool $flag): void
    {
        $this->handle_redirects = $flag;
    }

    /**
     * Maximum Redirects
     *
     * Set the maximum number of redirects allowed before the client quits
     * (mainly to prevent infinite loops) Default is 5.
     *
     * @param int    $num                Maximum redirects value
     */
    public function setMaxRedirects(int $num): void
    {
        $this->max_redirects = abs($num);
    }

    /**
     * Headers Only
     *
     * If true, the client only retrieves the headers from a page. This could be
     * useful for implementing things like link checkers. Defaults to false.
     *
     * @param bool    $flag            Enable/Disable Headers Only
     */
    public function setHeadersOnly(bool $flag): void
    {
        $this->headers_only = $flag;
    }

    /**
     * Debug mode
     *
     * Should the client run in debug mode? Default behaviour is false.
     *
     * @param bool    $flag            Enable/Disable Debug Mode
     */
    public function setDebug(bool $flag): void
    {
        $this->debug = $flag;
    }

    /**
     * Set Output
     *
     * Output module init. If <var>$out</var> is null, then output will be
     * directed to STDOUT.
     *
     * @param string|null    $out            Output stream
     */
    public function setOutput(?string $out): void
    {
        $this->output = $out;
    }

    /**
     * Quick Get
     *
     * Static method designed for running simple GET requests. Returns content or
     * false on failure.
     *
     * @param string    $url                Request URL
     * @param string    $output             Optionnal output stream
     */
    public static function quickGet(string $url, ?string $output = null): false|string
    {
        $path = '';
        if (($client = self::initClient($url, $path)) === false) {
            return false;
        }
        $client->setOutput($output);
        $client->get($path);

        return $client->getStatus() == 200 ? $client->getContent() : false;
    }

    /**
     * Quick Post
     *
     * Static method designed for running simple POST requests. Returns content or
     * false on failure.
     *
     * @param string                $url               Request URL
     * @param array<string, mixed>  $data              Array of parameters
     * @param string                $output            Optionnal output stream
     */
    public static function quickPost(string $url, array $data, ?string $output = null): false|string
    {
        $path = '';
        if (($client = self::initClient($url, $path)) === false) {
            return false;
        }
        $client->setOutput($output);
        $client->post($path, $data);

        return $client->getStatus() == 200 ? $client->getContent() : false;
    }

    /**
     * Quick Init
     *
     * Returns a new instance of the class. <var>$path</var> is an output variable.
     *
     * @param string    $url                Request URL
     * @param string    $path               Resulting path
     *
     * @return HttpClient|false
     */
    public static function initClient(string $url, string &$path): false|HttpClient
    {
        $ssl  = false;
        $host = '';
        $port = 0;
        $user = '';
        $pass = '';

        if (!self::readURL($url, $ssl, $host, $port, $path, $user, $pass)) {
            return false;
        }

        $client = new self($host, $port);
        $client->useSSL($ssl);
        $client->setAuthorization($user, $pass);

        return $client;
    }

    /**
     * Read URL
     *
     * Parses an URL and fills <var>$ssl</var>, <var>$host</var>, <var>$port</var>,
     * <var>$path</var>, <var>$user</var> and <var>$pass</var> variables. Returns
     * true on succes.
     *
     * @param string    $url             Request URL
     * @param boolean   $ssl             true if HTTPS URL
     * @param string    $host            Host name
     * @param int       $port            Server Port
     * @param string    $path            Path
     * @param string    $user            Username
     * @param string    $pass            Password
     *
     * @phpstan-param-out string|null  $user
     * @phpstan-param-out string|null  $pass
     */
    public static function readURL(string $url, bool &$ssl, string &$host, int &$port, string &$path, string &$user, string &$pass): bool
    {
        $bits = parse_url($url);

        if (empty($bits['host'])) {
            return false;
        }

        if (empty($bits['scheme']) || !preg_match('%^http[s]?$%', $bits['scheme'])) {
            return false;
        }

        $scheme = $bits['scheme'];
        $host   = $bits['host'];
        $port   = (int) ($bits['port'] ?? 0);
        $path   = $bits['path'] ?? '/';
        $user   = $bits['user'] ?? null;
        $pass   = $bits['pass'] ?? null;

        $ssl = $scheme == 'https';

        if ($port === 0) {
            $port = $ssl ? 443 : 80;
        }

        if (isset($bits['query'])) {
            $path .= '?' . $bits['query'];
        }

        return true;
    }

    /**
     * Debug
     *
     * This method is the method the class calls whenever there is debugging
     * information available. $msg is a debugging message and $object is an
     * optional object to be displayed (usually an array). Default behaviour is to
     * display the message and the object in a red bordered div. If you wish
     * debugging information to be handled in a different way you can do so by
     * creating a new class that extends HttpClient and over-riding the debug()
     * method in that class.
     *
     * @param string        $msg               Debug message
     * @param mixed         $object            Variable to print_r
     */
    protected function debug(string $msg, $object = false): void
    {
        if ($this->debug) {
            echo "-----------------------------------------------------------\n";
            echo '-- HttpClient Debug: ' . $msg . "\n";
            if ($object) {
                print_r($object);
                echo "\n";
            }
            echo "-----------------------------------------------------------\n\n";
        }
    }
}
