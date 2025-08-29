<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Network\Socket;

use Exception;
use PHPUnit\Framework\TestCase;

class SocketTest extends TestCase
{
    public function test(): void
    {
        $socket = new \Dotclear\Helper\Network\Socket\Socket('example.org', 443);

        $this->assertEquals(
            'example.org',
            $socket->host()
        );
        $this->assertTrue(
            $socket->host('example.com')
        );
        $this->assertEquals(
            'example.com',
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

        // Next test with content

        $socket = new \Dotclear\Helper\Network\Socket\Socket('example.org', 80);

        try {
            $socket->open();

            $this->assertTrue(
                $socket->isOpen()
            );

            $data = [
                'GET / HTTP/1.0',
            ];
            $expected = [
                'HTTP/1.0 400 Bad Request' . "\r\n",
                'Server: AkamaiGHost' . "\r\n",
                'Mime-Version: 1.0' . "\r\n",
                'Content-Type: text/html' . "\r\n",
                'Conten',
                'Expire',
                'Date: ',
                'Connection: close' . "\r\n",
                "\r\n",
                '<HTML><HEAD>' . "\n",
                '<TITLE>Invalid URL</TITLE>' . "\n",
                '</HEAD><BODY>' . "\n",
                '<H1>Invalid URL</H1>' . "\n",
                '*',
                '*',
                '*',
                '</BODY></HTML>' . "\n",
            ];
            $line = 0;
            // @phpstan-ignore foreach.nonIterable
            foreach ($socket->write($data) as $value) {
                if ($line < count($expected)) {
                    //$this->dump($value);
                    if ($expected[$line] === '*') {
                        continue;
                    }
                    $this->assertEquals(
                        $expected[$line],
                        mb_substr((string) $value, 0, mb_strlen($expected[$line]))
                    );
                }
                $line++;
            }

            // @phpstan-ignore variable.undefined
            if (gettype($value) === 'boolean') {
                // @phpstan-ignore method.impossibleType
                $this->assertFalse(
                    $value
                );
            } else {
                $this->assertStringStartsWith(
                    '</BODY></HTML>',
                    $value
                );
            }

            $socket->close();
        } catch (Exception $e) {
            $this->assertEquals(
                'Socket error: Operation timed out (60)example.org',
                $e->getMessage()
            );
        }

        $this->assertFalse(
            $socket->isOpen()
        );
    }

    public function testOpenError(): void
    {
        $this->expectException(Exception::class);

        $socket   = new \Dotclear\Helper\Network\Socket\Socket('unknowndomain.badextension', 80);
        $iterator = $socket->open();

        $this->assertFalse(
            $socket->isOpen()
        );
    }
}
