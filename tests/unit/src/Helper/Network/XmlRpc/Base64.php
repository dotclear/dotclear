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
 * @tags XmlRpc, XmlRpcBase64
 */
class Base64 extends atoum
{
    public function test()
    {
        $data = new \Dotclear\Helper\Network\XmlRpc\Base64('data');

        $this
            ->string($data->getXml())
            ->isEqualTo('<base64>ZGF0YQ==</base64>')
        ;
    }
}
