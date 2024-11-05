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

namespace tests\unit\Dotclear\Helper\Network\XmlRpc;

require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', '..', 'bootstrap.php']);

use atoum;

/*
 * @tags XmlRpc, XmlRpcClientMulticall
 */
class ClientMulticall extends atoum
{
    public function test()
    {
        $client = new \Dotclear\Helper\Network\XmlRpc\ClientMulticall('http://example.com/xmlrpc');

        $this
            ->given($client->addCall('method1', 'hello', 'world'))
            ->and($client->addCall('method2', 'foo', 'bar'))
            ->exception(fn () => $client->query())
            ->hasMessage('HTTP Error. 405 Method Not Allowed')
        ;
    }
}
