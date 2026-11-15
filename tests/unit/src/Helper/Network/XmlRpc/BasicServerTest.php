<?php

declare(strict_types=1);

namespace Dotclear\Helper\Network\XmlRpc {
    use Exception;

    function dotclear_exit(): void
    {
        throw new Exception('Exit application', 13);
    }
}

namespace Dotclear\Tests\Helper\Network\XmlRpc {
    use Exception;
    use PHPUnit\Framework\Attributes\BackupGlobals;
    use PHPUnit\Framework\TestCase;

    class BasicServerTest extends TestCase
    {
        #[BackupGlobals(true)]
        public function testServer(): void
        {
            $this->expectOutputString(
                'XML-RPC server accepts POST requests only.'
            );
            $this->expectException(Exception::class);

            $_SERVER['REQUEST_METHOD'] = 'GET';

            $server = new \Dotclear\Helper\Network\XmlRpc\BasicServer();

            $this->expectExceptionMessage(
                'XML-RPC server accepts POST requests only.'
            );
            $this->expectExceptionCode(
                405
            );
        }

        public function testBuiltInMethodsAndMulticall(): void
        {
            $server = new BasicServerTester();

            $this->assertArrayHasKey('xmlrpc', $server->capabilities());
            $this->assertContains('test.echo', $server->methods());
            $this->assertSame('echo', $server->invoke('test.echo', ['echo']));

            $results = $server->invoke('system.multicall', [[
                ['methodName' => 'test.echo', 'params' => ['called']],
                ['methodName' => 'missing.method', 'params' => []],
                ['methodName' => 'system.multicall', 'params' => []],
                ['methodName' => 42, 'params' => 'not-an-array'],
            ]]);

            $this->assertSame(['called'], $results[0]);
            $this->assertSame(-32601, $results[1]['faultCode']);
            $this->assertSame(-32600, $results[2]['faultCode']);
            $this->assertSame(-32601, $results[3]['faultCode']);
        }

        public function testServeMethodCallOutput(): void
        {
            $request = '<methodCall><methodName>test.echo</methodName><params><param><value><string>ok</string></value></param></params></methodCall>';

            $this->expectOutputRegex('/<string>ok<\\/string>/');
            $this->expectExceptionMessage('Exit application');

            new \Dotclear\Helper\Network\XmlRpc\BasicServer(['test.echo' => static fn (string $value): string => $value], $request);
        }

        public function testServeParserErrorOutput(): void
        {
            $this->expectOutputRegex('/<name>faultCode<\\/name>/');
            $this->expectExceptionMessage('Exit application');

            new \Dotclear\Helper\Network\XmlRpc\BasicServer([], '<invalid/>');
        }

        #[BackupGlobals(true)]
        public function testMissingHostResponse(): void
        {
            $_SERVER['REQUEST_METHOD'] = 'POST';
            unset($_SERVER['HTTP_HOST']);

            $this->expectOutputString('No Host Specified');
            $this->expectExceptionMessage('Exit application');

            new \Dotclear\Helper\Network\XmlRpc\BasicServer();
        }

        #[BackupGlobals(true)]
        public function testStrictRequestValidation(): void
        {
            $base = [
                'REQUEST_METHOD' => 'POST',
                'HTTP_HOST'      => 'example.test',
            ];

            $this->assertStrictFailure($base, 'No User Agent Specified');
            $this->assertStrictFailure($base + ['HTTP_USER_AGENT' => 'test'], 'Invalid Content-Type');
            $this->assertStrictFailure($base + ['HTTP_USER_AGENT' => 'test', 'CONTENT_TYPE' => 'text/plain'], 'Invalid Content-Type');
            $this->assertStrictFailure($base + ['HTTP_USER_AGENT' => 'test', 'CONTENT_TYPE' => 'text/xml'], 'Invalid Content-Lenth');
            $this->assertStrictFailure($base + ['HTTP_USER_AGENT' => 'test', 'CONTENT_TYPE' => 'text/xml', 'CONTENT_LENGTH' => '0'], 'Invalid Content-Lenth');
        }

        /**
         * @param array<string, string> $server
         */
        private function assertStrictFailure(array $server, string $message): void
        {
            $_SERVER = $server;
            ob_start();
            $exception = null;
            try {
                (new StrictBasicServerTester())->process();
            } catch (Exception $caughtException) {
                $exception = $caughtException;
            } finally {
                $output = (string) ob_get_clean();
            }

            $this->assertInstanceOf(Exception::class, $exception);
            $this->assertSame($message, $output);
            $this->assertSame(13, $exception->getCode());
        }
    }

    class BasicServerTester extends \Dotclear\Helper\Network\XmlRpc\BasicServer
    {
        public function __construct()
        {
            $this->setCapabilities();
            $this->callbacks['test.echo'] = static fn (string $value): string => $value;
            $this->setCallbacks();
        }

        public function capabilities(): array
        {
            return $this->getCapabilities();
        }

        public function methods(): array
        {
            return $this->listMethods();
        }

        public function invoke(string $method, array $args): mixed
        {
            return $this->call($method, $args);
        }
    }

    class StrictBasicServerTester extends \Dotclear\Helper\Network\XmlRpc\BasicServer
    {
        public function __construct()
        {
            $this->strict_check = true;
            $this->setCapabilities();
            $this->setCallbacks();
        }

        public function process(): void
        {
            $this->serve();
        }
    }
}
