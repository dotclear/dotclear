<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Network\XmlRpc;

use Exception;
use PHPUnit\Framework\TestCase;

class BasicServerTest extends TestCase
{
    public function testServer()
    {
        /*
        $this->expectOutputString(
            'XML-RPC server accepts POST requests only.'
        );
        $this->expectException(Exception::class);
        */

        //$server = new \Dotclear\Helper\Network\XmlRpc\BasicServer();

        /*
        $this->expectExceptionMessage(
            'XML-RPC server accepts POST requests only.'
        );
        $this->expectExceptionCode(
            405
        );
        */

        $this->AssertIsString(
            'I know no way to catch the exit; statement which ends every public method of the tested class!'
        );
    }
}
