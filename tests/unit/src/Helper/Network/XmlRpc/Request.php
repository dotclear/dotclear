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
 * @tags XmlRpc, XmlRpcRequest
 */
class Request extends atoum
{
    public function test()
    {
        $req = new \Dotclear\Helper\Network\XmlRpc\Request('myBestMethod', ['id' => 42, 'dotclear' => true]);

        $this
            ->integer($req->getLength())
            ->isEqualTo(214)
            ->string($req->getXml())
            ->isEqualTo('<?xml version="1.0"?>' . "\n" . '<methodCall>' . "\n" . '  <methodName>myBestMethod</methodName>' . "\n" . '  <params>' . "\n" . '    <param><value><int>42</int></value></param>' . "\n" . '    <param><value><boolean>1</boolean></value></param>' . "\n" . '  </params>' . "\n" . '</methodCall>')
        ;
    }
}
