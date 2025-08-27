<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Network\XmlRpc;

use Exception;
use PHPUnit\Framework\Attributes\BackupGlobals;
use PHPUnit\Framework\TestCase;

class IntrospectionServerTest extends TestCase
{
    /**
     * Note: terminate() is mocked by BasicServerTest.php
     */
    #[BackupGlobals(true)]
    public function test(): void
    {
        $this->expectOutputString(
            ''
        );
        //$this->expectException(Exception::class);

        $_SERVER['REQUEST_METHOD'] = 'GET';

        $server = new \Dotclear\Helper\Network\XmlRpc\IntrospectionServer();

        //$this->expectExceptionMessage(
        //    'XML-RPC server accepts POST requests only.'
        //);
        //$this->expectExceptionCode(
        //    405
        //);
    }
}
