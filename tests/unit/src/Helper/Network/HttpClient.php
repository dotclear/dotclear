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

namespace tests\unit\Dotclear\Helper\Network;

require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', 'bootstrap.php']);

use atoum;

/*
 * @tags HttpClient
 */
class HttpClient extends atoum
{
    public function test()
    {
        $client = new \Dotclear\Helper\Network\HttpClient('example.com', 443, 5);

        $this
            ->string(get_class($client))
            ->isEqualTo('Dotclear\Helper\Network\HttpClient')
            ->given($client->useSSL(false))
            ->string($client->getRequestURL())
            ->isEqualTo('http://example.com:443')
            ->given($client->useSSL(true))
            ->string($client->getRequestURL())
            ->isEqualTo('https://example.com')
        ;
    }

    public function testReadURL(?string $url, bool $status, array $data)
    {
        $ssl  = false;
        $host = '';
        $port = 0;
        $path = '';
        $user = '';
        $pass = '';

        $this
            ->boolean(\Dotclear\Helper\Network\HttpClient::readURL($url, $ssl, $host, $port, $path, $user, $pass))
            ->isEqualTo($status)
            ->array([$ssl, $host, $port, $path, $user, $pass])
            ->isEqualTo($data)
        ;
    }

    protected function testReadURLDataProvider()
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

    public function testQuickGet()
    {
        $this
            ->string(\Dotclear\Helper\Network\HttpClient::quickGet('http://example.com'))
            ->isNotEmpty()
            ->boolean(\Dotclear\Helper\Network\HttpClient::quickGet('example.com'))
            ->isFalse()
        ;
    }

    public function testQuickGetSsl()
    {
        $this
            ->string(\Dotclear\Helper\Network\HttpClient::quickGet('https://example.com'))
            ->isNotEmpty()
        ;
    }

    public function testQuickPost()
    {
        $this
            ->string(\Dotclear\Helper\Network\HttpClient::quickPost('https://ptsv3.com/', ['my' => 42]))
            ->isNotEmpty()
            ->boolean(\Dotclear\Helper\Network\HttpClient::quickPost('ptsv3.com', ['my' => 42]))
            ->isFalse()
        ;
    }

    public function testVariousData()
    {
        $client = new \Dotclear\Helper\Network\HttpClient('example.com', 80, 5);

        $this
            ->boolean($client->get('/', ['my' => 42, 'tab' => ['a', 'b', 18]]))
            ->isTrue()
            ->string($client->getRequestURL())
            ->isEqualTo('http://example.com/?my=42&tab=a&tab=b&tab=18')
            ->boolean($client->get('/', 'single'))
            ->isTrue()
            ->string($client->getRequestURL())
            ->isEqualTo('http://example.com/?single')
        ;
    }

    public function testSetProxy()
    {
        define('HTTP_PROXY_HOST', '127.0.0.1');
        define('HTTP_PROXY_PORT', 80);

        $client = new \Dotclear\Helper\Network\HttpClient('example.com', 80, 5);

        $this
            ->boolean($client->get('/', ['my' => 42]))
            ->isTrue()
            ->string($client->getRequestURL())
            ->isEqualTo('http://example.com/?my=42')
            ->given($client->setProxy(null, null))
            ->boolean($client->get('/', ['my' => 42]))
            ->isTrue()
            ->string($client->getRequestURL())
            ->isEqualTo('http://example.com/?my=42')
        ;
    }

    public function testProperties()
    {
        $client = new \Dotclear\Helper\Network\HttpClient('ptsv3.com', 80, 5);

        $this
            ->given($client->setAuthorization('noname', 'password'))
            ->and($client->setUserAgent('Dotclear HTTP Client'))
            ->and($client->useGzip(true))
            ->and($client->setPersistReferers(true))
            ->and($client->setPersistCookies(true))
            ->and($client->setCookies(['Cookie: dcxd-dc2-git=61af8c3f8fcfa8921814cc9d2db0d87482e1ff76']))
            ->and($client->setMoreHeader('Set-Cookie: dcxd-dc2-hg=0'))
            ->boolean($client->post('/', ['my' => 42], 'UTF-8'))
            ->isTrue()
            ->string($client->getRequestURL())
            ->isEqualTo('http://ptsv3.com/')
            ->boolean(in_array('content-type', array_keys($client->getHeaders())))
            ->isTrue()
            ->string($client->getHeader('content-type'))
            ->isEqualTo('text/html; charset=utf-8')
            ->integer($client->getStatus())
            ->isEqualTo(200)
            ->string($client->getContent())
            ->contains('<html>')
            ->array($client->getCookies())
            ->isEqualTo(['Cookie: dcxd-dc2-git=61af8c3f8fcfa8921814cc9d2db0d87482e1ff76'])
        ;
    }

    public function testOutput()
    {
        $client = new \Dotclear\Helper\Network\HttpClient('example.com', 80, 5);
        $output = realpath(sys_get_temp_dir()) . DIRECTORY_SEPARATOR . 'dc-temp-test-' . bin2hex(random_bytes(8)) . '.txt';

        $this
            ->given($client->setOutput($output))
            ->boolean($client->get('/', null))
            ->isTrue()
            ->string(file_get_contents($output))
            ->contains('<title>Example Domain</title>')
        ;

        if (file_exists($output)) {
            unlink($output);
        }
    }

    public function testHeadersOnly()
    {
        $client = new \Dotclear\Helper\Network\HttpClient('example.com', 80, 5);
        $output = realpath(sys_get_temp_dir()) . DIRECTORY_SEPARATOR . 'dc-temp-test-' . bin2hex(random_bytes(8)) . '.txt';

        $this
            ->given($client->setHeadersOnly(true))
            ->boolean($client->get('/'))
            ->isTrue()
            ->integer($client->getStatus())
            ->isEqualTo(200)
            ->string($client->getContent())
            ->isEmpty()
            ->boolean(in_array('content-type', array_keys($client->getHeaders())))
            ->isTrue()
        ;

        if (file_exists($output)) {
            unlink($output);
        }
    }

    public function testMoreHeader()
    {
        $client = new \Dotclear\Helper\Network\HttpClient('example.com', 80, 5);

        $this
            ->given($client->setAuthorization('noname', 'password'))
            ->and($client->setUserAgent('Dotclear HTTP Client'))
            ->and($client->useGzip(true))
            ->and($client->setMoreHeader('Referrer Policy: strict-origin'))
            ->boolean($client->post('/', ['my' => 42], 'UTF-8'))
            ->isTrue()
            ->string($client->getRequestURL())
            ->isEqualTo('http://example.com/')
            ->boolean(in_array('content-type', array_keys($client->getHeaders())))
            ->isTrue()
            ->integer($client->getStatus())
            ->isEqualTo(400)
            ->string($client->getContent())
            ->contains('400 - Bad Request')
            ->array($client->getCookies())
            ->isEqualTo([])
        ;

        $this
            ->given($client->voidMoreHeaders())
            ->boolean($client->post('/', ['my' => 42], 'UTF-8'))
            ->isTrue()
            ->string($client->getRequestURL())
            ->isEqualTo('http://example.com/')
            ->boolean(in_array('content-type', array_keys($client->getHeaders())))
            ->isTrue()
            ->integer($client->getStatus())
            ->isEqualTo(405)
            ->array($client->getCookies())
            ->isEqualTo([])
        ;
    }

    public function testRedirect()
    {
        $client = new \Dotclear\Helper\Network\HttpClient('dotclear.org', 80, 5);

        $this
            ->boolean($client->get('/'))
            ->isTrue()
            ->string($client->getRequestURL())
            ->isEqualTo('https://dotclear.org/')
            ->boolean(in_array('content-type', array_keys($client->getHeaders())))
            ->isTrue()
            ->integer($client->getStatus())
            ->isEqualTo(200)
            ->string($client->getContent())
            ->contains('Dotclear')
            ->array($client->getCookies())
            ->isEqualTo([])
        ;
    }

    public function testHandlerRedirect()
    {
        $client = new \Dotclear\Helper\Network\HttpClient('dotclear.org', 80, 5);

        $this
            ->given($client->setHandleRedirects(false))
            ->boolean($client->get('/'))
            ->isTrue()
            ->string($client->getRequestURL())
            ->isEqualTo('http://dotclear.org/')
            ->boolean(in_array('content-type', array_keys($client->getHeaders())))
            ->isTrue()
            ->integer($client->getStatus())
            ->isEqualTo(302)
            ->string($client->getContent())
            ->contains('302 Found')
        ;
    }

    public function testMaxRedirect()
    {
        $client = new \Dotclear\Helper\Network\HttpClient('dotclear.org', 80, 5);

        $this
            ->given($client->setMaxRedirects(1))
            ->exception(function () use ($client) {
                $client->get('/');
            })
            ->HasMessage('Number of redirects exceeded maximum (1)')
        ;
    }

    public function testSSLDisabled()
    {
        $client = new \Dotclear\Helper\Network\HttpClient('dotclear.org', 80, 5);

        $this
            ->given($this->function->stream_get_transports = [])
            ->exception(function () use ($client) {
                $client->useSSL(true);
            })
            ->HasMessage('SSL support is not available')
        ;
    }

    public function testDebug()
    {
        $client = new \Dotclear\Helper\Network\HttpClient('example.com', 80, 5);

        ob_start();
        $this
            ->given($client->setDebug(true))
            ->boolean($client->get('/'))
            ->isTrue()
        ;

        $out = ob_get_contents();
        ob_end_clean();

        $this
            ->string($out)
            ->contains('HttpClient Debug:')
        ;
    }
}
