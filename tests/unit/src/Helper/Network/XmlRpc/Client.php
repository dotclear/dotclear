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
 * @tags XmlRpc, XmlRpcClient
 */
class Client extends atoum
{
    public function test()
    {
        $client = new \Dotclear\Helper\Network\XmlRpc\Client('http://example.com/xmlrpc');

        $this
            ->exception(fn () => $client->query('method1', 'hello', 'world'))
            ->hasMessage('HTTP Error. 405 Method Not Allowed')
        ;
    }
}
