<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Network\XmlRpc;

use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    public function test(): void
    {
        $req = new \Dotclear\Helper\Network\XmlRpc\Request('myBestMethod', ['id' => 42, 'dotclear' => true]);

        $this->assertEquals(
            214,
            $req->getLength()
        );
        $this->assertEquals(
            '<?xml version="1.0"?>' . "\n" . '<methodCall>' . "\n" . '  <methodName>myBestMethod</methodName>' . "\n" . '  <params>' . "\n" . '    <param><value><int>42</int></value></param>' . "\n" . '    <param><value><boolean>1</boolean></value></param>' . "\n" . '  </params>' . "\n" . '</methodCall>',
            $req->getXml()
        );
    }
}
