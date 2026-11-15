<?php

declare(strict_types=1);

namespace Dotclear\Helper\Network\HttpClient {
    use Dotclear\Tests\Helper\Network\HttpClientTest;

    /**
     * @return list<string>
     */
    function stream_get_transports(): array
    {
        return HttpClientTest::stream_get_transports();
    }
}

namespace Dotclear\Helper\Network {
    use Dotclear\Tests\Helper\Network\HttpClientTest;

    /**
     * @return list<string>
     */
    function stream_get_transports(): array
    {
        return HttpClientTest::stream_get_transports();
    }

    /**
     * @return array<string, mixed>
     */
    function stream_get_meta_data(mixed $stream): array
    {
        return HttpClientTest::stream_get_meta_data($stream);
    }
}

namespace Dotclear\Helper\Network\Socket {
    use Dotclear\Tests\Helper\Network\HttpClientTest;

    function fsockopen(
        string $hostname,
        int $port = -1,
        ?int &$error_code = null,
        ?string &$error_message = null,
        ?float $timeout = null
    ): mixed {
        return HttpClientTest::fsockopen($hostname, $port, $error_code, $error_message, $timeout);
    }

    function fwrite(mixed $stream, string $data, ?int $length = null): int|false
    {
        return HttpClientTest::socketWrite($stream, $data, $length);
    }

    /**
     * @return array<string, mixed>
     */
    function stream_get_meta_data(mixed $stream): array
    {
        return HttpClientTest::stream_get_meta_data($stream);
    }
}

namespace Dotclear\Tests\Helper\Network {
    use Exception;
    use PHPUnit\Framework\Attributes\BackupGlobals;
    use PHPUnit\Framework\Attributes\DataProvider;
    use PHPUnit\Framework\Attributes\RunInSeparateProcess;
    use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
    use PHPUnit\Framework\TestCase;

    #[RunTestsInSeparateProcesses]
    class HttpClientTest extends TestCase
    {
        private static bool $no_ssl = false;
        private static bool $simulate_timeout = false;
        private static bool $mock_enabled = false;
        private static mixed $mock_handle = null;
        /**
         * @var list<string>
         */
        private static array $responses = [];

        protected function setUp(): void
        {
            self::$no_ssl          = false;
            self::$simulate_timeout = false;
            self::$mock_enabled    = true;
            self::$responses       = [];
        }

        /**
         * @param list<string> $responses
         */
        private static function setResponses(array $responses): void
        {
            self::$responses = $responses;
        }

        public static function fsockopen(
            string $hostname,
            int $port = -1,
            ?int &$error_code = null,
            ?string &$error_message = null,
            ?float $timeout = null
        ): mixed {
            if (!self::$mock_enabled) {
                return \fsockopen($hostname, $port, $error_code, $error_message, $timeout);
            }

            $handle = fopen('php://temp', 'w+b');
            if ($handle === false) {
                return false;
            }

            self::$mock_handle = $handle;
            self::$responses   = self::$responses ?: [self::httpResponse(200, 'OK', "Blog management made easy\n", ['Content-Type: text/html'])];

            if (!self::$simulate_timeout) {
                fwrite($handle, array_shift(self::$responses));
                rewind($handle);
            }

            return $handle;
        }

        public static function socketWrite(mixed $stream, string $data, ?int $length = null): int|false
        {
            if ($stream === self::$mock_handle) {
                return strlen($length === null ? $data : substr($data, 0, $length));
            }

            return \fwrite($stream, $data, $length);
        }

        /**
         * @param list<string> $headers
         */
        private static function httpResponse(int $status, string $reason, string $body = '', array $headers = []): string
        {
            $headers[] = 'Content-Length: ' . strlen($body);

            return sprintf("HTTP/1.1 %d %s\r\n%s\r\n\r\n%s", $status, $reason, implode("\r\n", $headers), $body);
        }

        /**
         * @return array<string, mixed>
         */
        public static function stream_get_meta_data(mixed $stream): array
        {
            if (self::$simulate_timeout) {
                return ['timed_out' => true];
            }

            $metadata = \stream_get_meta_data($stream);
            $metadata['timed_out'] ??= false;

            return $metadata;
        }

        /**
         * @return list<string>
         */
        public static function stream_get_transports(): array
        {
            if (self::$no_ssl) {
                fwrite(STDOUT, 'Being tested with: ' . 'NO SSL' . "\n");

                return [];
            }

            return \stream_get_transports();
        }

        public function test(): void
        {
            $client = new \Dotclear\Helper\Network\HttpClient('example.com', 443, 5);

            $client->useSSL(false);

            $this->assertEquals(
                'http://example.com:443',
                $client->getRequestURL()
            );

            $client->useSSL(true);

            $this->assertEquals(
                'https://example.com',
                $client->getRequestURL()
            );
        }

        /**
         * @param  array{string, bool, array{bool, string, int, string, ?string, ?string}}  $data
         */
        #[DataProvider('dataProviderReadURL')]
        public function testReadURL(string $url, bool $status, array $data): void
        {
            $ssl  = false;
            $host = '';
            $port = 0;
            $path = '';
            $user = '';
            $pass = '';

            $this->assertEquals(
                $status,
                \Dotclear\Helper\Network\HttpClient::readURL($url, $ssl, $host, $port, $path, $user, $pass)
            );
            $this->assertEquals(
                $data,
                [$ssl, $host, $port, $path, $user, $pass]
            );
        }

        /**
         * @return list<array{string, bool, array{bool, string, int, string, ?string, ?string}}>
         */
        public static function dataProviderReadURL(): array
        {
            return [
                // URL, return value, [ssl, host, port=0, path=/, user=null, password=null]
                ['http://example.com', true, [false, 'example.com', 80, '/', null, null]],
                ['https://example.com', true, [true, 'example.com', 443, '/', null, null]],
                ['example.com', false, [false, '', 0, '', null, null]],
                ['http://example.com/debug', true, [false, 'example.com', 80, '/debug', null, null]],
                ['http://noname@example.com/debug', true, [false, 'example.com', 80, '/debug', 'noname', null]],
                ['http://noname:password@example.com/debug', true, [false, 'example.com', 80, '/debug', 'noname', 'password']],
                ['http://:password@example.com/debug', true, [false, 'example.com', 80, '/debug', '', 'password']],
                ['http://example.com:80', true, [false, 'example.com', 80, '/', null, null]],
                ['http://example.com:8080', true, [false, 'example.com', 8080, '/', null, null]],
                ['ssl://example.com', false, [false, '', 0, '', null, null]],
                ['http://noname@example.com/debug?my=42', true, [false, 'example.com', 80, '/debug?my=42', 'noname', null]],
            ];
        }

        public function testQuickGet(): void
        {
            $ret = \Dotclear\Helper\Network\HttpClient::quickGet('http://dotclear.org');

            $this->assertIsString($ret);
            $this->assertNotSame('', $ret);
            $this->assertFalse(\Dotclear\Helper\Network\HttpClient::quickGet('ptsv3.com'));
        }

        public function testQuickGetSsl(): void
        {
            $ret = \Dotclear\Helper\Network\HttpClient::quickGet('https://dotclear.org');

            $this->assertIsString($ret);
            $this->assertNotSame('', $ret);
        }

        public function testQuickPost(): void
        {
            $ret = \Dotclear\Helper\Network\HttpClient::quickPost('https://example.com/post', ['my' => 42]);

            $this->assertIsString($ret);
            $this->assertNotSame('', $ret);
            $this->assertFalse(\Dotclear\Helper\Network\HttpClient::quickPost('ptsv3.com', ['my' => 42]));
        }

        public function testRequestTimeout(): void
        {
            self::$simulate_timeout = true;

            $this->expectException(Exception::class);
            $this->expectExceptionMessage('Socket timeout');

            (new \Dotclear\Helper\Network\HttpClient('example.com'))->get('/');
        }

        public function testQuickGetTimeout(): void
        {
            self::$simulate_timeout = true;

            $this->assertFalse(
                \Dotclear\Helper\Network\HttpClient::quickGet('http://example.com')
            );
        }

        public function testQuickPostTimeout(): void
        {
            self::$simulate_timeout = true;

            $this->assertFalse(
                \Dotclear\Helper\Network\HttpClient::quickPost('http://example.com', ['my' => 42])
            );
        }

        public function testVariousData(): void
        {
            $client = new \Dotclear\Helper\Network\HttpClient('example.com', 80);

            $this->assertTrue(
                $client->get('/', ['my' => 42, 'tab' => ['a', 'b', 18]])
            );
            $this->assertEquals(
                'http://example.com/?my=42&tab=a&tab=b&tab=18',
                $client->getRequestURL()
            );
            $this->assertTrue(
                $client->get('/', 'single')
            );
            $this->assertEquals(
                'http://example.com/?single',
                $client->getRequestURL()
            );
        }

        #[BackupGlobals(true)]
        public function testSetProxy(): void
        {
            define('HTTP_PROXY_HOST', '127.0.0.1');
            define('HTTP_PROXY_PORT', 80);

            $client = new \Dotclear\Helper\Network\HttpClient('example.com', 80);

            $this->assertTrue(
                $client->get('/', ['my' => 42])
            );
            $this->assertEquals(
                'http://example.com/?my=42',
                $client->getRequestURL()
            );

            $client->setProxy(null, null);

            $this->assertTrue(
                $client->get('/', ['my' => 42])
            );
            $this->assertEquals(
                'http://example.com/?my=42',
                $client->getRequestURL()
            );
        }

        public function testProperties(): void
        {
            self::setResponses([
                self::httpResponse(200, 'OK', '{"message":"Request recorded","status":"success"}', ['Content-Type: application/json']),
            ]);
            $client = new \Dotclear\Helper\Network\HttpClient('example.com', 80);

            $client->setAuthorization('noname', 'password');
            $client->setUserAgent('Dotclear HTTP Client');
            $client->useGzip(true);
            $client->setPersistReferers(true);
            $client->setPersistCookies(true);
            $client->setCookies([
                'dcxd-dc2-git' => '61af8c3f8fcfa8921814cc9d2db0d87482e1ff76',
            ]);
            $client->setMoreHeader('Set-Cookie: dcxd-dc2-hg=0');

            $this->assertTrue(
                $client->post('/dotclear', ['my' => 42], 'UTF-8')
            );
            $this->assertEquals(
                'http://example.com/dotclear',
                $client->getRequestURL()
            );
            $this->assertArrayHasKey(
                'content-type',
                $client->getHeaders()
            );
            $this->assertEquals(
                'application/json',
                $client->getHeader('content-type')
            );
            $this->assertEquals(
                200,
                $client->getStatus()
            );
            $this->assertStringContainsString(
                '{"message":"Request recorded","status":"success"}',
                $client->getContent()
            );
            $this->assertEquals(
                [
                    'dcxd-dc2-git' => '61af8c3f8fcfa8921814cc9d2db0d87482e1ff76',
                ],
                $client->getCookies()
            );
        }

        public function testOutput(): void
        {
            $client = new \Dotclear\Helper\Network\HttpClient('example.com', 80);
            $output = realpath(sys_get_temp_dir()) . DIRECTORY_SEPARATOR . 'dc-temp-test-' . bin2hex(random_bytes(8)) . '.txt';

            try {
                $client->setOutput($output);

                $this->assertTrue($client->get('/', false));
                $this->assertStringContainsString('Blog management made easy', (string) file_get_contents($output));
            } finally {
                if (file_exists($output)) {
                    unlink($output);
                }
            }
        }

        public function testHeadersOnly(): void
        {
            $client = new \Dotclear\Helper\Network\HttpClient('example.com', 80);
            $client->setHeadersOnly(true);

            $this->assertTrue($client->get('/', false));
            $this->assertSame(200, $client->getStatus());
            $this->assertSame('', $client->getContent());
            $this->assertArrayHasKey('content-type', $client->getHeaders());
        }

        public function testCompressedResponseAndCookies(): void
        {
            $compressed = gzencode('compressed fixture');
            self::setResponses([
                self::httpResponse(200, 'OK', $compressed, [
                    'Content-Type: text/plain',
                    'Content-Encoding: gzip',
                    'Set-Cookie: session=abc; Path=/',
                    'Set-Cookie: invalid',
                    'Set-Cookie: token=xyz; Secure',
                ]),
            ]);
            $client = new HttpClientTester('example.com', 80);
            $client->useGzip(true);
            $client->setCookieHost('example.com');

            $this->assertTrue($client->get('/'));
            $this->assertSame('compressed fixture', $client->getContent());
            $this->assertSame(['session' => 'abc', 'token' => 'xyz'], $client->getCookies());
        }

        public function testMoreHeader(): void
        {
            $client = new \Dotclear\Helper\Network\HttpClient('example.com', 80);
            $client->setAuthorization('noname', 'password');
            $client->setUserAgent('Dotclear HTTP Client');
            $client->useGzip(true);
            $client->setMoreHeader('Referrer Policy: strict-origin');

            $this->assertTrue($client->post('/', ['my' => 42], 'UTF-8'));
            $this->assertSame('http://example.com/', $client->getRequestURL());
            $this->assertArrayHasKey('content-type', $client->getHeaders());
            $this->assertSame(200, $client->getStatus());
            $this->assertStringContainsString('Blog management made easy', $client->getContent());
            $this->assertSame([], $client->getCookies());

            $client->voidMoreHeaders();

            $this->assertTrue($client->post('/', ['my' => 42], 'UTF-8'));
            $this->assertSame('http://example.com/', $client->getRequestURL());
            $this->assertArrayHasKey('content-type', $client->getHeaders());
            $this->assertSame(200, $client->getStatus());
            $this->assertSame([], $client->getCookies());
        }

        public function testRedirect(): void
        {
            self::setResponses([
                self::httpResponse(302, 'Found', '', [
                    'Location: https://example.com/redirected',
                    'Location: https://ignored.example/second',
                ]),
                self::httpResponse(200, 'OK', 'redirected', ['Content-Type: text/plain']),
            ]);
            $client = new \Dotclear\Helper\Network\HttpClient('example.net', 80);

            $this->assertTrue($client->get('/'));
            $this->assertSame('https://example.com/redirected', $client->getRequestURL());
            $this->assertSame(200, $client->getStatus());
            $this->assertSame('redirected', $client->getContent());
        }

        public function testHandlerRedirect(): void
        {
            self::setResponses([
                self::httpResponse(404, 'Not Found', 'missing', ['Content-Type: text/plain']),
            ]);
            $client = new \Dotclear\Helper\Network\HttpClient('example.net', 80);

            $client->setHandleRedirects(false);

            $this->assertTrue(
                $client->get('/')
            );
            $this->assertEquals(
                'http://example.net/',
                $client->getRequestURL()
            );
            $this->assertArrayHasKey(
                'content-type',
                $client->getHeaders()
            );
            $this->assertEquals(
                404,
                $client->getStatus()
            );
        }

        public function testMaxRedirect(): void
        {
            $client = new \Dotclear\Helper\Network\HttpClient('example.com', 80);

            $client->setMaxRedirects(1);

            $this->expectException(Exception::class);

            $client->get('/');

            $this->expectExceptionMessage('Number of redirects exceeded maximum (1)');
        }

        public function testInvalidStatusLine(): void
        {
            self::setResponses(["not an HTTP status\r\n\r\n"]);
            $client = new \Dotclear\Helper\Network\HttpClient('example.com', 80);

            $this->expectException(Exception::class);
            $this->expectExceptionMessage('Status code line invalid: not an HTTP status');
            $client->get('/');
        }

        public function testDebug(): void
        {
            $client = new \Dotclear\Helper\Network\HttpClient('example.com', 443);
            $client->setDebug(true);

            ob_start();
            try {
                $this->assertTrue($client->get('/'));
                $this->assertStringContainsString('HttpClient Debug:', (string) ob_get_contents());
            } finally {
                ob_end_clean();
            }
        }

        #[RunInSeparateProcess]
        public function testSSLDisabled(): void
        {
            $client = new \Dotclear\Helper\Network\HttpClient('example.com', 80);
            self::$no_ssl = true;

            $this->expectException(Exception::class);
            $this->expectExceptionMessage('SSL support is not available');
            $client->useSSL(true);
        }
    }

    class HttpClientTester extends \Dotclear\Helper\Network\HttpClient
    {
        public function setCookieHost(string $host): void
        {
            $this->cookie_host = $host;
        }
    }
}
