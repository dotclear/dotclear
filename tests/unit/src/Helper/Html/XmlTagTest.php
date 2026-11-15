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

        $xml = new \Dotclear\Helper\Html\XmlTag(null, ['mystring' => 13, 'myvalue' => 42, -1]);

        $this->assertEquals(
            '<mystring>13</mystring><myvalue>42</myvalue>',
            $xml->toXML()
        );
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

    public function testGettersAndNonScalarAttributes(): void
    {
        $xml = new \Dotclear\Helper\Html\XmlTag('root');
        $xml->myattr = 'value';
        $xml->insertAttr('ignored', new \stdClass());
        $xml->insertNode('child');

        $this->assertSame('value', $xml->myattr);
        $this->assertNull($xml->missing);
        $this->assertSame('root', $xml->getName());
        $this->assertCount(1, $xml->getNodes());
        $this->assertSame('child', $xml->getNode(0));
        $this->assertSame('', $xml->getNode(1));
        $this->assertSame('<root myattr="value">child</root>', $xml->toXML());
    }

    public function testNestedArrayAndUnsupportedValues(): void
    {
        $xml = new \Dotclear\Helper\Html\XmlTag('root');
        $xml->item(['nested' => 'value']);
        $xml->ignored(new \stdClass());
        $xml->insertNode(['kept' => 'entry', 'ignored' => new \stdClass()]);
        $xml->insertNode(['deep' => ['child' => 'value']]);

        $this->assertSame(
            '<root><item><nested>value</nested></item><kept>entry</kept><deep><child>value</child></deep></root>',
            $xml->toXML()
        );
    }
}
