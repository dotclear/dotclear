<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Network\XmlRpc;

use Exception;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    public function test(): void
    {
        $client = new \Dotclear\Helper\Network\XmlRpc\Client('https://dotclear.org/xmlrpc');

        $this->expectException(
            Exception::class
        );
        $client->query('method1', 'hello', 'world');
        $this->expectExceptionMessage(
            'HTTP Error. 404 Not Found'
        );
    }
}
