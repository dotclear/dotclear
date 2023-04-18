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
 * @tags XmlRpc, XmlRpcMessage
 */
class Message extends atoum
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
        $xml = file_get_contents($this->testDirectory . DIRECTORY_SEPARATOR . 'msgMethod.xml');

        $this
            ->given($msg = new \Dotclear\Helper\Network\XmlRpc\Message($xml))
            ->boolean($msg->parse())
            ->isTrue()
        ;
    }

    public function testValues()
    {
        $xml = file_get_contents($this->testDirectory . DIRECTORY_SEPARATOR . 'msgValues.xml');

        $this
            ->given($msg = new \Dotclear\Helper\Network\XmlRpc\Message($xml))
            ->boolean($msg->parse())
            ->isTrue()
        ;
    }

    public function testEmpty()
    {
        $xml = file_get_contents($this->testDirectory . DIRECTORY_SEPARATOR . 'msgEmpty.xml');

        $this
            ->given($msg = new \Dotclear\Helper\Network\XmlRpc\Message($xml))
            ->exception(fn () => $msg->parse())
            ->hasMessage('XML Parser Error. Empty message')
        ;
    }

    public function testEmptyWithDtd()
    {
        $xml = file_get_contents($this->testDirectory . DIRECTORY_SEPARATOR . 'msgEmptyWithDtd.xml');

        $this
            ->given($msg = new \Dotclear\Helper\Network\XmlRpc\Message($xml))
            ->exception(fn () => $msg->parse())
            ->hasMessage('XML Parser Error.')
        ;
    }

    public function testDoubleDtd()
    {
        $xml = file_get_contents($this->testDirectory . DIRECTORY_SEPARATOR . 'msgDoubleDtd.xml');

        $this
            ->given($msg = new \Dotclear\Helper\Network\XmlRpc\Message($xml))
            ->exception(fn () => $msg->parse())
            ->hasMessage('XML Parser Error.')
        ;
    }

    public function testRootUnknown()
    {
        $xml = file_get_contents($this->testDirectory . DIRECTORY_SEPARATOR . 'msgRootUnknown.xml');

        $this
            ->given($msg = new \Dotclear\Helper\Network\XmlRpc\Message($xml))
            ->exception(fn () => $msg->parse())
            ->hasMessage('XML Parser Error.')
        ;
    }

    public function testBadXml()
    {
        $xml = file_get_contents($this->testDirectory . DIRECTORY_SEPARATOR . 'msgBadXml.xml');

        $this
            ->given($msg = new \Dotclear\Helper\Network\XmlRpc\Message($xml))
            ->exception(fn () => $msg->parse())
            ->hasMessage('XML Parser Error.')
        ;
    }

    public function testFault()
    {
        $xml = file_get_contents($this->testDirectory . DIRECTORY_SEPARATOR . 'msgFault.xml');

        $this
            ->given($msg = new \Dotclear\Helper\Network\XmlRpc\Message($xml))
            ->boolean($msg->parse())
            ->isTrue()
            ->integer($msg->faultCode)
            ->isEqualTo(42)
            ->string($msg->faultString)
            ->isEqualTo('I\'m afraid I can\'t do that Dave!')
        ;
    }

    public function testParseError()
    {
        $xml = file_get_contents($this->testDirectory . DIRECTORY_SEPARATOR . 'msgParseError.xml');

        $this
            ->given($msg = new \Dotclear\Helper\Network\XmlRpc\Message($xml))
            ->given($this->function->xml_parse = 0)
            ->exception(function () use ($msg) {
                $msg->parse();
            })
            ->hasMessage('XML Parser Error. No error on line 1')
        ;
    }
}
