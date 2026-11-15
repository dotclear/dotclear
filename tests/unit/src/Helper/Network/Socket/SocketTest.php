<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Network\Socket;

use Exception;
use PHPUnit\Framework\TestCase;

class SocketTest extends TestCase
{
    public function test(): void
    {
        $socket = new \Dotclear\Helper\Network\Socket\Socket('dotclear.org', 443);

        $this->assertEquals(
            'dotclear.org',
            $socket->host()
        );
        $this->assertTrue(
            $socket->host('dotclear.org')
        );
        $this->assertEquals(
            'dotclear.org',
            $socket->host()
        );
        $this->assertEquals(
            443,
            $socket->port()
        );
        $this->assertTrue(
            $socket->port(80)
        );
        $this->assertEquals(
            80,
            $socket->port()
        );
        $this->assertEquals(
            10,
            $socket->timeout()
        );
        $this->assertTrue(
            $socket->timeout(20)
        );
        $this->assertEquals(
            20,
            $socket->timeout()
        );
        $this->assertEquals(
            null,
            $socket->streamTimeout()
        );
        $this->assertTrue(
            $socket->streamTimeout(20)
        );
        $this->assertEquals(
            20,
            $socket->streamTimeout()
        );
        $this->assertEquals(
            null,
            $socket->verifyPeer()
        );
        $this->assertFalse(
            $socket->setBlocking(true)
        );
        $this->assertFalse(
            $socket->write('')
        );
        $this->assertFalse(
            $socket->flush()
        );
        $this->assertFalse(
            $socket->isOpen()
        );

        $socket->close();

        $server = stream_socket_server('tcp://127.0.0.1:0', $errorCode, $errorMessage);
        $this->assertNotFalse($server, $errorMessage);

        $address = stream_socket_get_name($server, false);
        $this->assertNotFalse($address);
        $port = (int) substr((string) $address, strrpos((string) $address, ':') + 1);
        $socket = new \Dotclear\Helper\Network\Socket\Socket('tcp://127.0.0.1', $port);
        $socket->verifyPeer(false);
        $socket->streamTimeout(2);

        try {
            $socket->open();
            $connection = stream_socket_accept($server, 1);
            $this->assertNotFalse($connection);

            $this->assertTrue($socket->isOpen());
            $this->assertTrue($socket->setBlocking(true));
            $this->assertNull($socket->flush());

            $response = $socket->write(['GET / HTTP/1.0']);
            $this->assertNotFalse($response);
            $this->assertSame("GET / HTTP/1.0\r\n\r\n", fgets($connection) . fgets($connection));
            fwrite($connection, "HTTP/1.0 200 OK\r\nContent-Type: text/plain\r\n\r\nsocket fixture\n");
            fclose($connection);

            $lines = [];
            foreach ($response as $line) {
                $lines[] = $line;
            }

            $this->assertSame([
                "HTTP/1.0 200 OK\r\n",
                "Content-Type: text/plain\r\n",
                "\r\n",
                "socket fixture\n",
            ], $lines);

            $socket->close();
            $this->assertFalse($socket->isOpen());
        } finally {
            $socket->close();
            fclose($server);
        }

        // Test with port = 0

        $this->expectException(Exception::class);

        $socket = new \Dotclear\Helper\Network\Socket\Socket('127.0.0.1', 0);

        $socket->open();

        $this->assertFalse(
            $socket->isOpen()
        );

        $socket->close();
    }

    public function testOpenError(): void
    {
        $this->expectException(Exception::class);

        $socket   = new \Dotclear\Helper\Network\Socket\Socket('127.0.0.1', 0);
        $iterator = $socket->open();

        $this->assertFalse(
            $socket->isOpen()
        );
    }
}
