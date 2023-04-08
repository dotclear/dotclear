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

        $socket->open();

        $this
            ->boolean($socket->isOpen())
            ->isTrue()
        ;

        $data = [
            'GET / HTTP/1.0',
        ];
        $expected = [
            'HTTP/1.0 404 Not Found' . "\r\n",
            'Content-Type: text/html' . "\r\n",
            'Date: ',
            'Server: ',
            'Content-Length: ',
            'Connection: close' . "\r\n",
            "\r\n",
            '<?xml version="1.0" encoding="iso-8859-1"?>',
            '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN',
            '         "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">',
            '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">',
            "\t" . '<head>',
            "\t\t" . '<title>404 - Not Found</title>',
            "\t" . '</head>',
            "\t" . '<body>',
            "\t\t" . '<h1>404 - Not Found</h1>',
            "\t" . '</body>',
            '</html>',
        ];
        $line = 0;
        foreach ($socket->write($data) as $value) {
            if ($line < count($expected)) {
                if (mb_substr($value, 0, mb_strlen($expected[$line])) !== $expected[$line]) {
                    $this
                        ->dump(json_encode(mb_substr($value, 0, mb_strlen($expected[$line]))))
                    ;
                }

                $this
                ->string(mb_substr($value, 0, mb_strlen($expected[$line])))
                ->isEqualTo($expected[$line])
                ;
            }
            $line++;
        }
        $this
            ->boolean($value)
            ->isFalse()
        ;

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
