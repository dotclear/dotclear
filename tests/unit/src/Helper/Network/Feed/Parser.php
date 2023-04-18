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

namespace tests\unit\Dotclear\Helper\Network\Feed;

require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', '..', 'bootstrap.php']);

use atoum;

/*
 * @tags Feed, FeedParser
 */
class Parser extends atoum
{
    private string $testDirectory;

    public function __construct()
    {
        parent::__construct();

        $this->testDirectory = realpath(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', '..', 'fixtures', 'src', 'Helper', 'Network', 'Feed']));

        $this
            ->dump($this->testDirectory)
        ;
    }

    public function testRss10()
    {
        $xml = file_get_contents($this->testDirectory . DIRECTORY_SEPARATOR . 'rss-1.0.xml');

        $this
            ->given($parser = new \Dotclear\Helper\Network\Feed\Parser($xml))
            ->string($parser->feed_type)
            ->isEqualTo('rss 1.0 (rdf)')
            ->string($parser->title)
            ->isEqualTo('Openweb.eu.org')
            ->string($parser->link)
            ->isEqualTo('http://www.openweb.eu.org/')
            ->string($parser->description)
            ->isEqualTo('Description du site OpenWeb')
            ->string($parser->pubdate)
            ->isEqualTo('')
            ->variable($parser->generator)
            ->isNull()
            ->array($parser->items)
            ->isNotEmpty()
            ->integer(count($parser->items))
            ->isEqualTo(3)
            ->array((array) $parser->items[0])
            ->isEqualTo([
                'title'       => 'Le PNG face au GIF',
                'link'        => 'http://openweb.eu.org/articles/png_vs_gif/',
                'creator'     => '',
                'description' => 'Qui est donc ce remplaçant du GIF, datant de 1996 et méconnu de la plupart des graphistes, amateurs comme professionnels ?',
                'content'     => '',
                'subject'     => [],
                'pubdate'     => '',
                'TS'          => false,
                'guid'        => 'http://openweb.eu.org/articles/png_vs_gif/',
            ])
        ;
    }

    public function testRss20()
    {
        $xml = file_get_contents($this->testDirectory . DIRECTORY_SEPARATOR . 'rss-2.0.xml');

        $this
            ->given($parser = new \Dotclear\Helper\Network\Feed\Parser($xml))
            ->string($parser->feed_type)
            ->isEqualTo('rss 2.0')
            ->string($parser->title)
            ->isEqualTo('Example Feed')
            ->string($parser->link)
            ->isEqualTo('http://example.org/')
            ->string($parser->description)
            ->isEqualTo('Insert witty or insightful remark here')
            ->string($parser->pubdate)
            ->isEqualTo('')
            ->string($parser->generator)
            ->isEqualTo('')
            ->array($parser->items)
            ->isNotEmpty()
            ->integer(count($parser->items))
            ->isEqualTo(1)
            ->array((array) $parser->items[0])
            ->isEqualTo([
                'title'       => 'Atom-Powered Robots Run Amok',
                'link'        => 'http://example.org/2003/12/13/atom03',
                'creator'     => '',
                'description' => 'Some text.',
                'content'     => '',
                'subject'     => [],
                'pubdate'     => 'Sat, 13 Dec 2003 18:30:02 GMT',
                'TS'          => 1071340202,
                'guid'        => 'urn:uuid:1225c695-cfb8-4ebb-aaaa-80da344efa6a',
            ])
        ;
    }

    public function testAtom03()
    {
        $xml = file_get_contents($this->testDirectory . DIRECTORY_SEPARATOR . 'atom-0.3.xml');

        $this
            ->given($parser = new \Dotclear\Helper\Network\Feed\Parser($xml))
            ->string($parser->feed_type)
            ->isEqualTo('atom 0.3')
            ->string($parser->title)
            ->isEqualTo('The name of your data feed')
            ->string($parser->link)
            ->isEqualTo('http://www.example.com')
            ->string($parser->description)
            ->isEqualTo('')
            ->string($parser->pubdate)
            ->isEqualTo('2005-10-11T18:30:02Z')
            ->string($parser->generator)
            ->isEqualTo('')
            ->array($parser->items)
            ->isNotEmpty()
            ->integer(count($parser->items))
            ->isEqualTo(1)
            ->array((array) $parser->items[0])
            ->isEqualTo([
                'link'        => 'http://www.example.com/item1-info-page.html',
                'title'       => 'Red wool sweater',
                'creator'     => '',
                'description' => 'Comfortable and soft, this sweater will keep you warm on those cold winter nights.',
                'content'     => '',
                'subject'     => [],
                'pubdate'     => '2005-10-13T18:30:02Z',
                'TS'          => 1129228202,
            ])
        ;
    }

    public function testAtom10()
    {
        $xml = file_get_contents($this->testDirectory . DIRECTORY_SEPARATOR . 'atom-1.0.xml');

        $this
            ->given($parser = new \Dotclear\Helper\Network\Feed\Parser($xml))
            ->string($parser->feed_type)
            ->isEqualTo('atom 1.0')
            ->string($parser->title)
            ->isEqualTo('Example Feed')
            ->variable($parser->link)
            ->isNull()
            ->string($parser->description)
            ->isEqualTo('')
            ->string($parser->pubdate)
            ->isEqualTo('2003-12-13T18:30:02Z')
            ->string($parser->generator)
            ->isEqualTo('')
            ->array($parser->items)
            ->isNotEmpty()
            ->integer(count($parser->items))
            ->isEqualTo(1)
            ->array((array) $parser->items[0])
            ->isEqualTo([
                'link'        => 'http://example.org/2003/12/13/atom03',
                'title'       => 'Atom-Powered Robots Run Amok',
                'creator'     => '',
                'description' => 'Some text.',
                'content'     => '',
                'subject'     => [],
                'pubdate'     => '2003-12-13T18:30:02Z',
                'TS'          => 1071340202,
            ])
        ;
    }
}
