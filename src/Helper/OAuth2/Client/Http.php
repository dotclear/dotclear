<?php

/**
 * @package     Dotclear
 *
 * @copyright   Olivier Meunier & Association Dotclear
 * @copyright   AGPL-3.0
 */
declare(strict_types=1);

namespace Dotclear\Helper\OAuth2\Client;

use CurlHandle;

/**
 * @brief   oAuth2 client HTTP request helper.
 *
 * @author  Jean-Christian Paul Denis
 * @since   2.36
 */
class Http
{
    /**
     * Request handler.
     *
     * @var     false|CurlHandle    $handler
     */
    protected $handler;

    /**
     * Response headers.
     *
     * @var     array<string, mixed>    $headers
     */
    protected $headers = [];

    /**
     * Request options.
     *
     * @var     mixed[]     $curl_opt
     */
    protected $curl_opt = [
        CURLOPT_USERAGENT      => 'Dotclear - OAuth2Client',
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_NOBODY         => false,
        CURLOPT_MAXREDIRS      => 10,
        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
    ];

    /**
     * Instanciate curl handler.
     */
    public function __construct()
    {
        $this->handler = curl_init();
    }

    /**
     * Destruct curl handler.
     */
    public function __destruct()
    {
        if ($this->handler !== false) {
            curl_close($this->handler);
        }
    }

    /**
     * Make a request.
     *
     * @param   string|array<string, mixed>     $parameters     The parameters
     * @param   array<string, mixed>            $headers        The headers
     *
     * @return  array<string, mixed>    The HTTP response
     */
    public function request(Methods $method, string $url, string|array $parameters = [], array $headers = []): array
    {
        if ($this->handler === false) {
            return [];
        }

        $this->headers = [];
        $options       = array_replace($this->curl_opt, [
            CURLOPT_URL            => $url . (str_contains($url, '?') ? '&' : '?') . (is_string($parameters) ? $parameters : http_build_query($parameters, '', '&')),
            CURLOPT_CUSTOMREQUEST  => $method->name,
            CURLOPT_HTTPHEADER     => $this->linearizeHeaders($headers),
            CURLOPT_HEADERFUNCTION => $this->parseHeaders(...),
        ]);

        switch ($method->name) {
            case 'HEAD':
                $options[CURLOPT_NOBODY] = true;

                break;

            case 'GET':
                $options[CURLOPT_HTTPGET] = true;

                break;

            case 'POST':
                $options[CURLOPT_POST] = true;
                $options[CURLOPT_URL]  = $url;
                if (!empty($parameters)) {
                    $options[CURLOPT_POSTFIELDS] = $parameters;
                }

                break;

            case 'DELETE':
            case 'PATCH':
            case 'OPTIONS':
            case 'PUT':
            default:
                $options[CURLOPT_CUSTOMREQUEST] = $method->name;
                if (!empty($parameters)) {
                    $options[CURLOPT_POSTFIELDS] = $parameters;
                }
        }

        curl_setopt_array($this->handler, $options);
        $content = curl_exec($this->handler);

        // see: https://www.php.net/manual/en/function.curl-getinfo
        $rsp = [
            'status'  => curl_getinfo($this->handler, CURLINFO_HTTP_CODE),
            'header'  => $this->headers,
            'error'   => curl_error($this->handler),
            'content' => $content,
        ];

        curl_reset($this->handler);

        return $rsp;
    }

    /**
     * Parse headers.
     *
     * @param   curlHandle  $handler    The CurlHandle resourse
     * @param   string      $header     The headers
     *
     * @return  int     Lengh
     */
    protected function parseHeaders(curlHandle $handler, string $header): int
    {
        $parts = explode(':', $header, 2);
        if (count($parts) == 2) {
            [$name, $value]               = $parts;
            $this->headers[trim($name)][] = trim($value);
        }

        return mb_strlen($header, '8bit');
    }

    /**
     * Linearize headers.
     *
     * @param   array<string, mixed>    $headers    The headers
     *
     * @return  string[]    The headers
     */
    protected function linearizeHeaders(array $headers): array
    {
        $res = [];
        foreach ($headers as $key => $values) {
            if (!is_array($values)) {
                $res[] = sprintf('%s: %s', $key, $values);
            } else {
                foreach ($values as $value) {
                    $res[] = sprintf('%s: %s', $key, $value);
                }
            }
        }

        return $res;
    }
}
