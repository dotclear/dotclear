<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Network\Feed;

use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    private string $testDirectory;

    protected function setUp(): void
    {
        $this->testDirectory = (string) realpath(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', '..', 'fixtures', 'src', 'Helper', 'Network', 'Feed']));
    }

    public function testAsXML(): void
    {
        $xml = (string) file_get_contents($this->testDirectory . DIRECTORY_SEPARATOR . 'atom-1.0.xml');

        $parser = new \Dotclear\Helper\Network\Feed\Parser($xml);

        $this->assertStringStartsWith(
            '<?xml version="1.0"',
            (string) $parser->asXML()
        );
    }

    public function testRss10(): void
    {
        $xml    = (string) file_get_contents($this->testDirectory . DIRECTORY_SEPARATOR . 'rss-1.0.xml');
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
        // @phpstan-ignore method.impossibleType
        $this->assertEquals(
            '',
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

    public function testRss20(): void
    {
        $xml    = (string) file_get_contents($this->testDirectory . DIRECTORY_SEPARATOR . 'rss-2.0.xml');
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

    public function testAtom03(): void
    {
        $xml    = (string) file_get_contents($this->testDirectory . DIRECTORY_SEPARATOR . 'atom-0.3.xml');
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

    public function testAtom10(): void
    {
        $xml    = (string) file_get_contents($this->testDirectory . DIRECTORY_SEPARATOR . 'atom-1.0.xml');
        $parser = new \Dotclear\Helper\Network\Feed\Parser($xml);

        $this->assertEquals(
            'atom 1.0',
            $parser->feed_type
        );
        $this->assertEquals(
            'Example Feed',
            $parser->title
        );
        // @phpstan-ignore method.impossibleType
        $this->assertNull(
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

    public function testInvalidAndEmptyFeeds(): void
    {
        $invalid = new \Dotclear\Helper\Network\Feed\Parser('<broken');
        $this->assertFalse($invalid->asXML());

        $rdf = new \Dotclear\Helper\Network\Feed\Parser('<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"/>');
        $this->assertSame('rss 1.0 (rdf)', $rdf->feed_type);
        $this->assertSame([], $rdf->items);

        $rss = new \Dotclear\Helper\Network\Feed\Parser('<rss version="2.0"><channel><title>Empty RSS</title></channel></rss>');
        $this->assertSame('rss 2.0', $rss->feed_type);
        $this->assertSame([], $rss->items);

        $atom03 = new \Dotclear\Helper\Network\Feed\Parser('<feed><title>Empty Atom</title></feed>');
        $this->assertSame('atom 0.3', $atom03->feed_type);
        $this->assertSame([], $atom03->items);

        $atom10 = new \Dotclear\Helper\Network\Feed\Parser('<feed xmlns="http://www.w3.org/2005/Atom"><title>Empty Atom</title></feed>');
        $this->assertSame('atom 1.0', $atom10->feed_type);
        $this->assertSame([], $atom10->items);
    }

    public function testRssOptionalItemMetadata(): void
    {
        $xml = <<<'XML'
            <rss version="2.0"
                xmlns:dc="http://purl.org/dc/elements/1.1/"
                xmlns:content="http://purl.org/rss/1.0/modules/content/">
              <channel>
                <item>
                  <title>Metadata item</title>
                  <link>https://example.test/item</link>
                  <category/>
                  <category>news</category>
                  <dc:subject>featured</dc:subject>
                  <dc:date>2024-01-02T03:04:05Z</dc:date>
                  <content:encoded>body</content:encoded>
                </item>
              </channel>
            </rss>
            XML;
        $parser = new \Dotclear\Helper\Network\Feed\Parser($xml);

        $this->assertSame('Metadata item', $parser->items[0]->title);
        $this->assertSame(['featured', '', 'news'], $parser->items[0]->subject);
        $this->assertSame('2024-01-02T03:04:05Z', $parser->items[0]->pubdate);
        $this->assertSame('body', $parser->items[0]->content);
        $this->assertSame('https://example.test/item', $parser->items[0]->guid);
    }

    public function testAtomAlternateLinks(): void
    {
        $atom03 = new \Dotclear\Helper\Network\Feed\Parser(<<<'XML'
            <feed>
              <link rel="alternate" type="application/xhtml+xml" href="https://example.test/"/>
              <entry>
                <link rel="self" href="https://example.test/self"/>
                <link rel="alternate" type="application/xhtml+xml" href="https://example.test/item"/>
              </entry>
            </feed>
            XML);
        $this->assertSame('https://example.test/', $atom03->link);
        $this->assertSame('https://example.test/item', $atom03->items[0]->link);

        $atom10 = new \Dotclear\Helper\Network\Feed\Parser(<<<'XML'
            <feed xmlns="http://www.w3.org/2005/Atom">
              <link rel="alternate" type="application/xhtml+xml" href="https://example.test/"/>
              <entry>
                <link rel="self" href="https://example.test/self"/>
                <link rel="alternate" type="application/xhtml+xml" href="https://example.test/item"/>
                <title>Atom entry</title>
                <updated>2024-01-02T03:04:05Z</updated>
                <published>2024-01-01T03:04:05Z</published>
              </entry>
            </feed>
            XML);
        $this->assertSame('https://example.test/', $atom10->link);
        $this->assertSame('https://example.test/item', $atom10->items[0]->link);
        $this->assertSame('2024-01-01T03:04:05Z', $atom10->items[0]->pubdate);
    }
}
