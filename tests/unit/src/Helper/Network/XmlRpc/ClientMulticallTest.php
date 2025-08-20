<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Network\XmlRpc;

use Exception;
use PHPUnit\Framework\TestCase;

class ClientMulticallTest extends TestCase
{
    public function test()
    {
        $client = new \Dotclear\Helper\Network\XmlRpc\ClientMulticall('https://dotclear.org/xmlrpc');

        $client->addCall('method1', 'hello', 'world');
        $client->addCall('method2', 'foo', 'bar');

        $this->expectException(
            Exception::class
        );
        $client->query();
        $this->expectExceptionMessage(
            'HTTP Error. 404 Not Found'
        );
    }
}
