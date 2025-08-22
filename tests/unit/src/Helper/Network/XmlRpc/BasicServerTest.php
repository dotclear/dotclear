<?php

declare(strict_types=1);

namespace Dotclear\Helper\Network\XmlRpc {
    use Exception;

    function terminate()
    {
        throw new Exception('Exit application', 13);
    }
}

namespace Dotclear\Tests\Helper\Network\XmlRpc {
    use Exception;
    use PHPUnit\Framework\TestCase;

    class BasicServerTest extends TestCase
    {
        public function testServer()
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
    }
}
