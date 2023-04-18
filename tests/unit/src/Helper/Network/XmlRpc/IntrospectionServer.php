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
 * @tags XmlRpc, XmlRpcBasicServer
 */
class IntrospectionServer extends atoum
{
    private string $testDirectory;

    public function __construct()
    {
        parent::__construct();

        $this->testDirectory = realpath(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', '..', 'fixtures', 'src', 'Helper', 'Network', 'XmlRpc']));

        $this
            ->dump($this->testDirectory)
        ;
    }

    public function test()
    {
        $server = new \Dotclear\Helper\Network\XmlRpc\IntrospectionServer();

        $this
            ->dump('I know no way to catch the exit; statement which ends every public method of the tested class parent!')
        ;
    }
}
