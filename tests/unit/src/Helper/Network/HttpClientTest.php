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

            $this->assertIsString(
                $ret
            );
            $this->assertNotEquals(
                '',
                $ret
            );

            $this->assertFalse(
                \Dotclear\Helper\Network\HttpClient::quickGet('ptsv3.com')
            );
        }

        public function testQuickGetSsl(): void
        {
            $ret = \Dotclear\Helper\Network\HttpClient::quickGet('https://dotclear.org');

            $this->assertIsString(
                $ret
            );
            $this->assertNotEquals(
                '',
                $ret
            );
        }

        public function testQuickPost(): void
        {
            $ret = \Dotclear\Helper\Network\HttpClient::quickPost('https://ptsv3.com/', ['my' => 42]);

            $this->assertIsString(
                $ret
            );
            $this->assertNotEquals(
                '',
                $ret
            );

            $this->assertFalse(
                \Dotclear\Helper\Network\HttpClient::quickPost('ptsv3.com', ['my' => 42])
            );
        }

        public function testVariousData(): void
        {
            $client = new \Dotclear\Helper\Network\HttpClient('ptsv3.com', 80);

            $this->assertTrue(
                $client->get('/', ['my' => 42, 'tab' => ['a', 'b', 18]])
            );
            $this->assertEquals(
                'http://ptsv3.com/?my=42&tab=a&tab=b&tab=18',
                $client->getRequestURL()
            );
            $this->assertTrue(
                $client->get('/', 'single')
            );
            $this->assertEquals(
                'http://ptsv3.com/?single',
                $client->getRequestURL()
            );
        }

        #[BackupGlobals(true)]
        public function testSetProxy(): void
        {
            define('HTTP_PROXY_HOST', '127.0.0.1');
            define('HTTP_PROXY_PORT', 80);

            $client = new \Dotclear\Helper\Network\HttpClient('ptsv3.com', 80);

            $this->assertTrue(
                $client->get('/', ['my' => 42])
            );
            $this->assertEquals(
                'http://ptsv3.com/?my=42',
                $client->getRequestURL()
            );

            $client->setProxy(null, null);

            $this->assertTrue(
                $client->get('/', ['my' => 42])
            );
            $this->assertEquals(
                'http://ptsv3.com/?my=42',
                $client->getRequestURL()
            );
        }

        public function testProperties(): void
        {
            $client = new \Dotclear\Helper\Network\HttpClient('ptsv3.com', 80);

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
                $client->post('/', ['my' => 42], 'UTF-8')
            );
            $this->assertEquals(
                'http://ptsv3.com/',
                $client->getRequestURL()
            );
            $this->assertArrayHasKey(
                'content-type',
                $client->getHeaders()
            );
            $this->assertEquals(
                'text/html; charset=utf-8',
                $client->getHeader('content-type')
            );
            $this->assertEquals(
                200,
                $client->getStatus()
            );
            $this->assertStringContainsString(
                '<html>',
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
            $client = new \Dotclear\Helper\Network\HttpClient('dotclear.org', 80);
            $output = realpath(sys_get_temp_dir()) . DIRECTORY_SEPARATOR . 'dc-temp-test-' . bin2hex(random_bytes(8)) . '.txt';

            $client->setOutput($output);

            $this->assertTrue(
                $client->get('/', null)
            );

            $content = file_get_contents($output);

            $this->assertStringContainsString(
                'Blog management made easy',
                $content
            );

            if (file_exists($output)) {
                unlink($output);
            }
        }

        public function testHeadersOnly(): void
        {
            $client = new \Dotclear\Helper\Network\HttpClient('dotclear.org', 80);

            $client->setHeadersOnly(true);

            $this->assertTrue(
                $client->get('/', null)
            );
            $this->assertEquals(
                200,
                $client->getStatus()
            );
            $this->assertEmpty(
                $client->getContent()
            );
            $this->assertArrayHasKey(
                'content-type',
                $client->getHeaders()
            );
        }

        public function testMoreHeader(): void
        {
            $client = new \Dotclear\Helper\Network\HttpClient('dotclear.org', 80);

            $client->setAuthorization('noname', 'password');
            $client->setUserAgent('Dotclear HTTP Client');
            $client->useGzip(true);
            $client->setMoreHeader('Referrer Policy: strict-origin');

            $this->assertTrue(
                $client->post('/', ['my' => 42], 'UTF-8')
            );
            $this->assertEquals(
                'https://dotclear.org/',
                $client->getRequestURL()
            );
            $this->assertArrayHasKey(
                'content-type',
                $client->getHeaders()
            );
            $this->assertEquals(
                200,
                $client->getStatus()
            );
            $this->assertStringContainsString(
                'Blog management made easy',
                $client->getContent()
            );
            $this->assertEquals(
                [],
                $client->getCookies()
            );

            $client->voidMoreHeaders();

            $this->assertTrue(
                $client->post('/', ['my' => 42], 'UTF-8')
            );
            $this->assertEquals(
                'https://dotclear.org/',
                $client->getRequestURL()
            );
            $this->assertArrayHasKey(
                'content-type',
                $client->getHeaders()
            );
            $this->assertEquals(
                200,
                $client->getStatus()
            );
            $this->assertEquals(
                [],
                $client->getCookies()
            );
        }

        public function testRedirect(): void
        {
            $client = new \Dotclear\Helper\Network\HttpClient('dotclear.net', 80);

            $client->setHandleRedirects(true);

            $this->assertTrue(
                $client->get('/')
            );
            $this->assertEquals(
                'https://dotclear.org/',
                $client->getRequestURL()
            );
            $this->assertArrayHasKey(
                'content-type',
                $client->getHeaders()
            );
            $this->assertEquals(
                200,
                $client->getStatus()
            );
            $this->assertEquals(
                [],
                $client->getCookies()
            );
        }

        public function testHandlerRedirect(): void
        {
            $client = new \Dotclear\Helper\Network\HttpClient('dotclear.net', 80);

            $client->setHandleRedirects(false);

            $this->assertTrue(
                $client->get('/')
            );
            $this->assertEquals(
                'http://dotclear.net/',
                $client->getRequestURL()
            );
            $this->assertArrayHasKey(
                'content-type',
                $client->getHeaders()
            );
            $this->assertEquals(
                301,
                $client->getStatus()
            );
            $this->assertStringContainsString(
                'Moved Permanently',
                $client->getContent()
            );
        }

        public function testMaxRedirect(): void
        {
            $client = new \Dotclear\Helper\Network\HttpClient('dotclear.org', 80);

            $client->setMaxRedirects(1);

            $this->expectException(Exception::class);

            $client->get('/');

            $this->expectExceptionMessage('Number of redirects exceeded maximum (1)');
        }

        public function testDebug(): void
        {
            $client = new \Dotclear\Helper\Network\HttpClient('example.com', 443);
            $client->setDebug(true);

            $out = '';

            try {
                ob_start();

                $this->assertTrue(
                    $client->get('/')
                );

                $out = ob_get_contents();
                $this->assertStringContainsString(
                    'HttpClient Debug:',
                    $out
                );
            } catch (Exception $e) {
                $this->assertStringContainsString(
                    'Socket error',
                    $e->getMessage()
                );
            } finally {
                ob_end_clean();
            }
        }

        #[RunInSeparateProcess]
        public function testSSLDisabled(): void
        {
            $client = new \Dotclear\Helper\Network\HttpClient('dotclear.org', 80);
            $client->useSSL(true);

            $this->expectException(Exception::class);

            self::$no_ssl = true;
            $client->get('/');
            self::$no_ssl = false;

            $this->expectExceptionMessage('SSL support is not available');
        }
    }
}
