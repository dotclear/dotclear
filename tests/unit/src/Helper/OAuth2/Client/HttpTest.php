<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\OAuth2\Client;

use Dotclear\Helper\OAuth2\Client\Http;
use Dotclear\Helper\OAuth2\Client\Methods;
use PHPUnit\Framework\TestCase;

class OAuth2InspectableHttp extends Http
{
    public function parseHeader(string $header): int
    {
        $handler = curl_init();
        return $this->parseHeaders($handler, $header);
    }

    public function linearize(array $headers): array
    {
        return $this->linearizeHeaders($headers);
    }

    public function headers(): array
    {
        return $this->headers;
    }

    public function disableHandler(): void
    {
        $this->handler = false;
    }
}

class HttpTest extends TestCase
{
    public function testHeadersAreParsedAndLinearized(): void
    {
        $http = new OAuth2InspectableHttp();

        $this->assertSame(strlen("X-Test: first:value\r\n"), $http->parseHeader("X-Test: first:value\r\n"));
        $this->assertSame(strlen("X-Test: second\r\n"), $http->parseHeader("X-Test: second\r\n"));
        $this->assertSame(strlen("malformed header\r\n"), $http->parseHeader("malformed header\r\n"));

        $this->assertSame(
            ['X-Test' => ['first:value', 'second']],
            $http->headers()
        );
        $this->assertSame(
            ['Accept: application/json', 'X-Flag: 1', 'X-Flag: value'],
            $http->linearize([
                'Accept' => 'application/json',
                'X-Flag' => [1, null, 'value', new \stdClass()],
                'ignored' => new \stdClass(),
            ])
        );
    }

    public function testRequestsUseCurlMethodOptionsAndFailGracefully(): void
    {
        $http = new OAuth2InspectableHttp();

        foreach (Methods::cases() as $method) {
            $response = $http->request($method, 'http://127.0.0.1:1/resource', ['key' => 'value']);

            $this->assertSame(0, $response['status']);
            $this->assertSame([], $response['header']);
            $this->assertFalse($response['content']);
            $this->assertNotSame('', $response['error']);
        }

        $http->disableHandler();
        $this->assertSame([], $http->request(Methods::GET, 'http://127.0.0.1:1/resource'));
    }
}
