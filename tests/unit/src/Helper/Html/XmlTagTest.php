<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Html;

use PHPUnit\Framework\TestCase;

class XmlTagTest extends TestCase
{
    public function test(): void
    {
        $xml = new \Dotclear\Helper\Html\XmlTag();

        $this->assertEquals(
            '',
            $xml->toXML()
        );
    }

    public function testWithName(): void
    {
        $xml = new \Dotclear\Helper\Html\XmlTag('mytag');

        $this->assertEquals(
            '<mytag/>',
            $xml->toXML()
        );
    }

    public function testWithValue(): void
    {
        $xml = new \Dotclear\Helper\Html\XmlTag(null, 'myvalue');

        $this->assertEquals(
            'myvalue',
            $xml->toXML()
        );
    }

    public function testWithNameAndValue(): void
    {
        $xml = new \Dotclear\Helper\Html\XmlTag('mytag', 'myvalue');

        $this->assertEquals(
            '<mytag>myvalue</mytag>',
            $xml->toXML()
        );
    }

    public function testWithTrueValue(): void
    {
        $xml = new \Dotclear\Helper\Html\XmlTag(null, true);

        $this->assertEquals(
            '1',
            $xml->toXML()
        );
    }

    public function testWithFalseValue(): void
    {
        $xml = new \Dotclear\Helper\Html\XmlTag(null, false);

        $this->assertEquals(
            '0',
            $xml->toXML()
        );
    }

    public function testWithArrayValue(): void
    {
        $xml = new \Dotclear\Helper\Html\XmlTag(null, ['mystring' => 13, 'myvalue' => 42]);

        $this->assertEquals(
            '<mystring>13</mystring><myvalue>42</myvalue>',
            $xml->toXML()
        );
    }

    public function testWithNodeValue(): void
    {
        $xml = new \Dotclear\Helper\Html\XmlTag('mytag', new \Dotclear\Helper\Html\XmlTag('node', 'nodevalue'));

        $this->assertEquals(
            '<mytag><node>nodevalue</node></mytag>',
            $xml->toXML()
        );
    }

    public function testWithBadArrayValue(): void
    {
        $this->expectException(\TypeError::class);

        new \Dotclear\Helper\Html\XmlTag(null, ['mystring' => 13, 'myvalue' => 42, -1]);

        $msg = 'Dotclear\Helper\Html\XmlTag::__construct(): Argument #1 ($_name) must be of type ?string, int given, called in';
        $this->expectExceptionMessageMatches('/' . preg_quote($msg) . '/');
    }

    public function testAddAttribute(): void
    {
        $xml = new \Dotclear\Helper\Html\XmlTag('mytag');

        $xml->insertAttr('myattr', 42);

        $this->assertEquals(
            '<mytag myattr="42"/>',
            $xml->toXML()
        );
    }

    public function testMagicAddAttribute(): void
    {
        $xml = new \Dotclear\Helper\Html\XmlTag('mytag');

        $xml->myattr = 42;

        $this->assertEquals(
            '<mytag myattr="42"/>',
            $xml->toXML()
        );
    }

    public function testInsertNode(): void
    {
        $xml = new \Dotclear\Helper\Html\XmlTag('mytag');

        $xml->insertNode();

        $this->assertEquals(
            '<mytag></mytag>',
            $xml->toXML()
        );
    }

    public function testInsertNodeWithValue(): void
    {
        $xml = new \Dotclear\Helper\Html\XmlTag('mytag');

        $xml->insertNode('mynode');

        $this->assertEquals(
            '<mytag>mynode</mytag>',
            $xml->toXML()
        );
    }

    public function testMagicInsertNode(): void
    {
        $xml = new \Dotclear\Helper\Html\XmlTag('mytag');

        $xml->myattr(42);

        $this->assertEquals(
            '<mytag><myattr>42</myattr></mytag>',
            $xml->toXML()
        );
    }

    public function testMagicInsertNodeWithBadName(): void
    {
        $xml = new \Dotclear\Helper\Html\XmlTag('mytag');

        $this->assertFalse($xml->MYATTR(42));

        $this->assertEquals(
            '<mytag/>',
            $xml->toXML()
        );
    }

    public function testMagicInsertNodeWithNoValue(): void
    {
        $xml = new \Dotclear\Helper\Html\XmlTag('mytag');

        $xml->myattr();

        $this->assertEquals(
            '<mytag><myattr/></mytag>',
            $xml->toXML()
        );
    }

    public function testCDATA(): void
    {
        $xml = new \Dotclear\Helper\Html\XmlTag('mytag');

        $xml->CDATA('mydata');

        $this->assertEquals(
            '<mytag>mydata</mytag>',
            $xml->toXML()
        );
    }

    public function testToXMLWithProlog(): void
    {
        $xml = new \Dotclear\Helper\Html\XmlTag('mytag', 'myvalue');

        $this->assertEquals(
            '<?xml version="1.0" encoding="UTF-8" ?>' . "\n" . '<mytag>myvalue</mytag>',
            $xml->toXML(true)
        );
    }

    public function testToXMLWithPrologAndEnconding(): void
    {
        $xml = new \Dotclear\Helper\Html\XmlTag('mytag', 'myvalue');

        $this->assertEquals(
            '<?xml version="1.0" encoding="ISO-8859-1" ?>' . "\n" . '<mytag>myvalue</mytag>',
            $xml->toXML(true, 'ISO-8859-1')
        );
    }

    public function testToXMLWithPrologButNoName(): void
    {
        $xml = new \Dotclear\Helper\Html\XmlTag();

        $this->assertEquals(
            '',
            $xml->toXML(true)
        );
    }
}
