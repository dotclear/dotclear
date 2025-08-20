<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Network\XmlRpc;

use Exception;
use PHPUnit\Framework\TestCase;

class MessageTest extends TestCase
{
    private string $testDirectory;

    protected function setUp(): void
    {
        $this->testDirectory = realpath(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', '..', 'fixtures', 'src', 'Helper', 'Network', 'XmlRpc']));
    }

    public function test()
    {
        $xml = file_get_contents($this->testDirectory . DIRECTORY_SEPARATOR . 'msgMethod.xml');
        $msg = new \Dotclear\Helper\Network\XmlRpc\Message($xml);

        $this->assertTrue(
            $msg->parse()
        );
    }

    public function testValues()
    {
        $xml = file_get_contents($this->testDirectory . DIRECTORY_SEPARATOR . 'msgValues.xml');
        $msg = new \Dotclear\Helper\Network\XmlRpc\Message($xml);

        $this->assertTrue(
            $msg->parse()
        );
    }

    public function testEmpty()
    {
        $xml = file_get_contents($this->testDirectory . DIRECTORY_SEPARATOR . 'msgEmpty.xml');
        $msg = new \Dotclear\Helper\Network\XmlRpc\Message($xml);

        $this->expectException(Exception::class);
        $msg->parse();
        $this->expectExceptionMessage(
            'XML Parser Error. Empty message'
        );
    }

    public function testEmptyWithDtd()
    {
        $xml = file_get_contents($this->testDirectory . DIRECTORY_SEPARATOR . 'msgEmptyWithDtd.xml');
        $msg = new \Dotclear\Helper\Network\XmlRpc\Message($xml);

        $this->expectException(Exception::class);
        $msg->parse();
        $this->expectExceptionMessage(
            'XML Parser Error.'
        );
    }

    public function testDoubleDtd()
    {
        $xml = file_get_contents($this->testDirectory . DIRECTORY_SEPARATOR . 'msgDoubleDtd.xml');
        $msg = new \Dotclear\Helper\Network\XmlRpc\Message($xml);

        $this->expectException(Exception::class);
        $msg->parse();
        $this->expectExceptionMessage(
            'XML Parser Error.'
        );
    }

    public function testRootUnknown()
    {
        $xml = file_get_contents($this->testDirectory . DIRECTORY_SEPARATOR . 'msgRootUnknown.xml');
        $msg = new \Dotclear\Helper\Network\XmlRpc\Message($xml);

        $this->expectException(Exception::class);
        $msg->parse();
        $this->expectExceptionMessage(
            'XML Parser Error.'
        );
    }

    public function testBadXml()
    {
        $xml = file_get_contents($this->testDirectory . DIRECTORY_SEPARATOR . 'msgBadXml.xml');
        $msg = new \Dotclear\Helper\Network\XmlRpc\Message($xml);

        $this->expectException(Exception::class);
        $msg->parse();
        $this->expectExceptionMessage(
            'XML Parser Error.'
        );
    }

    public function testFault()
    {
        $xml = file_get_contents($this->testDirectory . DIRECTORY_SEPARATOR . 'msgFault.xml');
        $msg = new \Dotclear\Helper\Network\XmlRpc\Message($xml);

        $this->assertTrue(
            $msg->parse()
        );
        $this->assertEquals(
            42,
            $msg->faultCode
        );
        $this->assertEquals(
            'I\'m afraid I can\'t do that Dave!',
            $msg->faultString
        );
    }
}
