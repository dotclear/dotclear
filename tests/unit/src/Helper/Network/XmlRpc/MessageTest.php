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

    public function test(): void
    {
        $xml = file_get_contents($this->testDirectory . DIRECTORY_SEPARATOR . 'msgMethod.xml');
        $msg = new \Dotclear\Helper\Network\XmlRpc\Message($xml);

        $this->assertTrue(
            $msg->parse()
        );
    }

    public function testValues(): void
    {
        $xml = file_get_contents($this->testDirectory . DIRECTORY_SEPARATOR . 'msgValues.xml');
        $msg = new \Dotclear\Helper\Network\XmlRpc\Message($xml);

        $this->assertTrue(
            $msg->parse()
        );
    }

    public function testEmpty(): void
    {
        $xml = file_get_contents($this->testDirectory . DIRECTORY_SEPARATOR . 'msgEmpty.xml');
        $msg = new \Dotclear\Helper\Network\XmlRpc\Message($xml);

        $this->expectException(Exception::class);
        $msg->parse();
        $this->expectExceptionMessage(
            'XML Parser Error. Empty message'
        );
    }

    public function testEmptyWithDtd(): void
    {
        $xml = file_get_contents($this->testDirectory . DIRECTORY_SEPARATOR . 'msgEmptyWithDtd.xml');
        $msg = new \Dotclear\Helper\Network\XmlRpc\Message($xml);

        $this->expectException(Exception::class);
        $msg->parse();
        $this->expectExceptionMessage(
            'XML Parser Error.'
        );
    }

    public function testDoubleDtd(): void
    {
        $xml = file_get_contents($this->testDirectory . DIRECTORY_SEPARATOR . 'msgDoubleDtd.xml');
        $msg = new \Dotclear\Helper\Network\XmlRpc\Message($xml);

        $this->expectException(Exception::class);
        $msg->parse();
        $this->expectExceptionMessage(
            'XML Parser Error.'
        );
    }

    public function testRootUnknown(): void
    {
        $xml = file_get_contents($this->testDirectory . DIRECTORY_SEPARATOR . 'msgRootUnknown.xml');
        $msg = new \Dotclear\Helper\Network\XmlRpc\Message($xml);

        $this->expectException(Exception::class);
        $msg->parse();
        $this->expectExceptionMessage(
            'XML Parser Error.'
        );
    }

    public function testBadXml(): void
    {
        $xml = file_get_contents($this->testDirectory . DIRECTORY_SEPARATOR . 'msgBadXml.xml');
        $msg = new \Dotclear\Helper\Network\XmlRpc\Message($xml);

        $this->expectException(Exception::class);
        $msg->parse();
        $this->expectExceptionMessage(
            'XML Parser Error.'
        );
    }

    public function testFault(): void
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
