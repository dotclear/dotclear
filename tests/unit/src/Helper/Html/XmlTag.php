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

namespace tests\unit\Dotclear\Helper\Html;

require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', 'bootstrap.php']);

use atoum;

/**
 * @tags XmlTag
 */
class XmlTag extends atoum
{
    public function test()
    {
        $xml = new \Dotclear\Helper\Html\XmlTag();

        $this
            ->string($xml->toXML())
            ->isEqualTo('')
        ;
    }

    public function testWithName()
    {
        $xml = new \Dotclear\Helper\Html\XmlTag('mytag');

        $this
            ->string($xml->toXML())
            ->isEqualTo('<mytag/>')
        ;
    }

    public function testWithValue()
    {
        $xml = new \Dotclear\Helper\Html\XmlTag(null, 'myvalue');

        $this
            ->string($xml->toXML())
            ->isEqualTo('myvalue')
        ;
    }

    public function testWithNameAndValue()
    {
        $xml = new \Dotclear\Helper\Html\XmlTag('mytag', 'myvalue');

        $this
            ->string($xml->toXML())
            ->isEqualTo('<mytag>myvalue</mytag>')
        ;
    }

    public function testWithTrueValue()
    {
        $xml = new \Dotclear\Helper\Html\XmlTag(null, true);

        $this
            ->string($xml->toXML())
            ->isEqualTo('1')
        ;
    }

    public function testWithFalseValue()
    {
        $xml = new \Dotclear\Helper\Html\XmlTag(null, false);

        $this
            ->string($xml->toXML())
            ->isEqualTo('0')
        ;
    }

    public function testWithArrayValue()
    {
        $xml = new \Dotclear\Helper\Html\XmlTag(null, ['mystring' => 13, 'myvalue' => 42]);

        $this
            ->string($xml->toXML())
            ->isEqualTo('<mystring>13</mystring><myvalue>42</myvalue>')
        ;
    }

    public function testWithNodeValue()
    {
        $xml = new \Dotclear\Helper\Html\XmlTag('mytag', new \Dotclear\Helper\Html\XmlTag('node', 'nodevalue'));

        $this
            ->string($xml->toXML())
            ->isEqualTo('<mytag><node>nodevalue</node></mytag>')
        ;
    }

    public function testWithBadArrayValue()
    {
        $this
            ->exception(function () {
                new \Dotclear\Helper\Html\XmlTag(null, ['mystring' => 13, 'myvalue' => 42, -1]);
            })
            ->message
                ->contains('Dotclear\Helper\Html\XmlTag::__construct(): Argument #1 ($name) must be of type ?string, int given, called in')
        ;
    }

    public function testAddAttribute()
    {
        $xml = new \Dotclear\Helper\Html\XmlTag('mytag');

        $this
            ->given($xml->insertAttr('myattr', 42))
            ->string($xml->toXML())
            ->isEqualTo('<mytag myattr="42"/>')
        ;
    }

    public function testMagicAddAttribute()
    {
        $xml = new \Dotclear\Helper\Html\XmlTag('mytag');

        $this
            ->given($xml->myattr = 42)
            ->string($xml->toXML())
            ->isEqualTo('<mytag myattr="42"/>')
        ;
    }

    public function testInsertNode()
    {
        $xml = new \Dotclear\Helper\Html\XmlTag('mytag');

        $this
            ->given($xml->insertNode())
            ->string($xml->toXML())
            ->isEqualTo('<mytag></mytag>')
        ;
    }

    public function testInsertNodeWithValue()
    {
        $xml = new \Dotclear\Helper\Html\XmlTag('mytag');

        $this
            ->given($xml->insertNode('mynode'))
            ->string($xml->toXML())
            ->isEqualTo('<mytag>mynode</mytag>')
        ;
    }

    public function testMagicInsertNode()
    {
        $xml = new \Dotclear\Helper\Html\XmlTag('mytag');

        $this
            ->given($xml->myattr(42))
            ->string($xml->toXML())
            ->isEqualTo('<mytag><myattr>42</myattr></mytag>')
        ;
    }

    public function testMagicInsertNodeWithBadName()
    {
        $xml = new \Dotclear\Helper\Html\XmlTag('mytag');

        $this
            ->boolean($xml->MYATTR(42))
            ->isFalse()
            ->string($xml->toXML())
            ->isEqualTo('<mytag/>')
        ;
    }

    public function testMagicInsertNodeWithNoValue()
    {
        $xml = new \Dotclear\Helper\Html\XmlTag('mytag');

        $this
            ->given($xml->myattr())
            ->string($xml->toXML())
            ->isEqualTo('<mytag><myattr/></mytag>')
        ;
    }

    public function testCDATA()
    {
        $xml = new \Dotclear\Helper\Html\XmlTag('mytag');

        $this
            ->given($xml->CDATA('mydata'))
            ->string($xml->toXML())
            ->isEqualTo('<mytag>mydata</mytag>')
        ;
    }

    public function testToXMLWithProlog()
    {
        $xml = new \Dotclear\Helper\Html\XmlTag('mytag', 'myvalue');

        $this
            ->string($xml->toXML(true))
            ->isEqualTo('<?xml version="1.0" encoding="UTF-8" ?>' . "\n" . '<mytag>myvalue</mytag>')
        ;
    }

    public function testToXMLWithPrologAndEnconding()
    {
        $xml = new \Dotclear\Helper\Html\XmlTag('mytag', 'myvalue');

        $this
            ->string($xml->toXML(true, 'ISO-8859-1'))
            ->isEqualTo('<?xml version="1.0" encoding="ISO-8859-1" ?>' . "\n" . '<mytag>myvalue</mytag>')
        ;
    }

    public function testToXMLWithPrologButNoName()
    {
        $xml = new \Dotclear\Helper\Html\XmlTag();

        $this
            ->string($xml->toXML(true))
            ->isEqualTo('')
        ;
    }
}
