<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Network\XmlRpc;

use PHPUnit\Framework\TestCase;

class XmlRpcExceptionTest extends TestCase
{
    public function test(): void
    {
        $elt = new \Dotclear\Helper\Network\XmlRpc\XmlRpcException('dotclear', 42);

        $this->assertEquals(
            'dotclear',
            $elt->getMessage()
        );
        $this->assertEquals(
            42,
            $elt->getCode()
        );
    }
}
