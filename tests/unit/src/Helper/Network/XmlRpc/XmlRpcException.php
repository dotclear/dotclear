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
 * @tags XmlRpc, XmlRpcException
 */
class XmlRpcException extends atoum
{
    public function test()
    {
        $elt = new \Dotclear\Helper\Network\XmlRpc\XmlRpcException('dotclear', 42);

        $this
            ->string($elt->getMessage())
            ->isEqualTo('dotclear')
            ->integer($elt->getCode())
            ->isEqualTo(42)
        ;
    }
}
