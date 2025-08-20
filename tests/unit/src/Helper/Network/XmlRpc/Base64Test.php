<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Network\XmlRpc;

use PHPUnit\Framework\TestCase;

class Base64Test extends TestCase
{
    public function test()
    {
        $data = new \Dotclear\Helper\Network\XmlRpc\Base64('data');

        $this->assertEquals(
            '<base64>ZGF0YQ==</base64>',
            $data->getXml()
        );
    }
}
