<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper;

use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    private string $testDirectory;

    protected function setUp(): void
    {
        $this->testDirectory = realpath(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', '..', 'fixtures', 'src', 'Helper', 'Network', 'Feed']));
    }

    public function testAsXML()
    {
        $xml = file_get_contents($this->testDirectory . DIRECTORY_SEPARATOR . 'atom-1.0.xml');

        $parser = new \Dotclear\Helper\Network\Feed\Parser($xml);

        $this->assertStringStartsWith(
            '<?xml version="1.0"',
            $parser->asXML()
        );
    }

    public function testRss10()
    {
        $xml    = file_get_contents($this->testDirectory . DIRECTORY_SEPARATOR . 'rss-1.0.xml');
        $parser = new \Dotclear\Helper\Network\Feed\Parser($xml);

        $this->assertEquals(
            'rss 1.0 (rdf)',
            $parser->feed_type
        );
        $this->assertEquals(
            'Openweb.eu.org',
            $parser->title
        );
        $this->assertEquals(
            'http://www.openweb.eu.org/',
            $parser->link
        );
        $this->assertEquals(
            'Description du site OpenWeb',
            $parser->description
        );
        $this->assertEquals(
            '',
            $parser->pubdate
        );
        $this->isNull(
            $parser->generator
        );
        $this->assertNotEmpty(
            $parser->items
        );
        $this->assertEquals(
            3,
            count($parser->items)
        );
        $list = (array) $parser->items[0];
        ksort($list);
        $this->assertEquals(
            [
                'TS'          => false,
                'content'     => '',
                'creator'     => '',
                'description' => 'Qui est donc ce remplaçant du GIF, datant de 1996 et méconnu de la plupart des graphistes, amateurs comme professionnels ?',
                'guid'        => 'http://openweb.eu.org/articles/png_vs_gif/',
                'link'        => 'http://openweb.eu.org/articles/png_vs_gif/',
                'pubdate'     => '',
                'subject'     => [],
                'title'       => 'Le PNG face au GIF',
            ],
            $list
        );
    }

    public function testRss20()
    {
        $xml    = file_get_contents($this->testDirectory . DIRECTORY_SEPARATOR . 'rss-2.0.xml');
        $parser = new \Dotclear\Helper\Network\Feed\Parser($xml);

        $this->assertEquals(
            'rss 2.0',
            $parser->feed_type
        );
        $this->assertEquals(
            'Example Feed',
            $parser->title
        );
        $this->assertEquals(
            'http://example.org/',
            $parser->link
        );
        $this->assertEquals(
            'Insert witty or insightful remark here',
            $parser->description
        );
        $this->assertEquals(
            '',
            $parser->pubdate
        );
        $this->assertEquals(
            '',
            $parser->generator
        );
        $this->assertNotEmpty(
            $parser->items
        );
        $this->assertEquals(
            1,
            count($parser->items)
        );
        $list = (array) $parser->items[0];
        ksort($list);
        $this->assertEquals(
            [
                'TS'          => 1071340202,
                'content'     => '',
                'creator'     => '',
                'description' => 'Some text.',
                'guid'        => 'urn:uuid:1225c695-cfb8-4ebb-aaaa-80da344efa6a',
                'link'        => 'http://example.org/2003/12/13/atom03',
                'pubdate'     => 'Sat, 13 Dec 2003 18:30:02 GMT',
                'title'       => 'Atom-Powered Robots Run Amok',
                'subject'     => [],
            ],
            $list
        );
    }

    public function testAtom03()
    {
        $xml    = file_get_contents($this->testDirectory . DIRECTORY_SEPARATOR . 'atom-0.3.xml');
        $parser = new \Dotclear\Helper\Network\Feed\Parser($xml);

        $this->assertEquals(
            'atom 0.3',
            $parser->feed_type
        );
        $this->assertEquals(
            'The name of your data feed',
            $parser->title
        );
        $this->assertEquals(
            'http://www.example.com',
            $parser->link
        );
        $this->assertEquals(
            '',
            $parser->description
        );
        $this->assertEquals(
            '2005-10-11T18:30:02Z',
            $parser->pubdate
        );
        $this->assertEquals(
            '',
            $parser->generator
        );
        $this->assertNotEmpty(
            $parser->items
        );
        $this->assertEquals(
            1,
            count($parser->items)
        );
        $list = (array) $parser->items[0];
        ksort($list);
        $this->assertEquals(
            [
                'TS'          => 1129228202,
                'content'     => '',
                'creator'     => '',
                'description' => 'Comfortable and soft, this sweater will keep you warm on those cold winter nights.',
                'link'        => 'http://www.example.com/item1-info-page.html',
                'pubdate'     => '2005-10-13T18:30:02Z',
                'subject'     => [],
                'title'       => 'Red wool sweater',
            ],
            $list
        );
    }

    public function testAtom10()
    {
        $xml    = file_get_contents($this->testDirectory . DIRECTORY_SEPARATOR . 'atom-1.0.xml');
        $parser = new \Dotclear\Helper\Network\Feed\Parser($xml);

        $this->assertEquals(
            'atom 1.0',
            $parser->feed_type
        );
        $this->assertEquals(
            'Example Feed',
            $parser->title
        );
        $this->isNull(
            $parser->link
        );
        $this->assertEquals(
            '',
            $parser->description
        );
        $this->assertEquals(
            '2003-12-13T18:30:02Z',
            $parser->pubdate
        );
        $this->assertEquals(
            '',
            $parser->generator
        );
        $this->assertNotEmpty(
            $parser->items
        );
        $this->assertEquals(
            1,
            count($parser->items)
        );
        $list = (array) $parser->items[0];
        ksort($list);
        $this->assertEquals(
            [
                'TS'          => 1071340202,
                'content'     => '',
                'creator'     => '',
                'description' => 'Some text.',
                'link'        => 'http://example.org/2003/12/13/atom03',
                'pubdate'     => '2003-12-13T18:30:02Z',
                'subject'     => [],
                'title'       => 'Atom-Powered Robots Run Amok',
            ],
            $list
        );
    }
}
