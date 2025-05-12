<?php

/**
 * Unit tests
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace tests\unit\Dotclear\Helper\Network\Socket;

require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', '..', 'bootstrap.php']);

use atoum;

class Socket extends atoum
{
    public function test()
    {
        $socket = new \Dotclear\Helper\Network\Socket\Socket('example.org', 443);

        $this
            ->string($socket->host())
            ->isEqualTo('example.org')
            ->boolean($socket->host('example.com'))
            ->isTrue()
            ->string($socket->host())
            ->isEqualTo('example.com')
            ->integer($socket->port())
            ->isEqualTo(443)
            ->boolean($socket->port(80))
            ->isTrue()
            ->integer($socket->port())
            ->isEqualTo(80)
            ->integer($socket->timeout())
            ->isEqualTo(10)
            ->boolean($socket->timeout(20))
            ->isTrue()
            ->integer($socket->timeout())
            ->isEqualTo(20)
            ->boolean($socket->setBlocking(true))
            ->isFalse()
            ->boolean($socket->write(''))
            ->isFalse()
            ->boolean($socket->flush())
            ->isFalse()
            ->boolean($socket->isOpen())
            ->isFalse()
        ;
        $socket->close();

        // Next test with content

        $socket = new \Dotclear\Helper\Network\Socket\Socket('example.org', 80);
        $socket->open();

        $this
            ->boolean($socket->isOpen())
            ->isTrue()
        ;

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
        foreach ($socket->write($data) as $value) {
            if ($line < count($expected)) {
                //$this->dump($value);
                if ($expected[$line] === '*') {
                    continue;
                }
                if (mb_substr($value, 0, mb_strlen($expected[$line])) !== $expected[$line]) {
                    $this->dump(json_encode(mb_substr($value, 0, mb_strlen($expected[$line]))));
                }

                $this
                ->string(mb_substr($value, 0, mb_strlen($expected[$line])))
                ->isEqualTo($expected[$line])
                ;
            }
            $line++;
        }

        if (gettype($value) === 'boolean') {
            $this
                ->boolean($value)->isEqualTo(false)
            ;
        } else {
            $this
                ->string($value)->startWith('</BODY></HTML>')
            ;
        }

        $socket->close();

        $this
            ->boolean($socket->isOpen())
            ->isFalse()
        ;
    }

    public function testOpenError()
    {
        $socket = new \Dotclear\Helper\Network\Socket\Socket('unknowndomain.badextension', 80);

        $this
            ->exception(function () use ($socket) { $iterator = $socket->open(); })
        ;

        $this
            ->boolean($socket->isOpen())
            ->isFalse()
        ;

        unset($socket);
    }
}
