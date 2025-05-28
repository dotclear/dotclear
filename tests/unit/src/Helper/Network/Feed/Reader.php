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
use Dotclear\Helper\File\Files;

/*
 * @tags Feed, FeedParser
 */
class Reader extends atoum
{
    private string $cacheDirectory;

    public function __construct()
    {
        parent::__construct();

        $this->cacheDirectory = realpath(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', '..', 'fixtures', 'src', 'Helper', 'Network', 'Feed']));
        $this->cacheDirectory .= DIRECTORY_SEPARATOR . 'cache';
        if (!file_exists($this->cacheDirectory)) {
            mkdir($this->cacheDirectory);
        }

        $this
            ->dump($this->cacheDirectory)
        ;
    }

    public function tearDown()
    {
        Files::deltree($this->cacheDirectory);
    }

    public function test()
    {
        $reader = new \Dotclear\Helper\Network\Feed\Reader();

        $parser = $reader->parse('https://dotclear.org/blog/feed/atom');
        if ($parser) {
            $this
                ->object($parser)
                ->isInstanceOf(\Dotclear\Helper\Network\Feed\Parser::class)
                ->string($parser->title)
                ->isEqualTo('Dotclear News')
                ->string($parser->link)
                ->isEqualTo('https://dotclear.org/blog/')
            ;
        } else {
            $this->dump($parser);
        }

        // Again to use cache
        $reader->setCacheDir($this->cacheDirectory);
        $reader->setCacheTTL('-2 hours');
        $reader->setCacheTTL('4 hours');

        $parser = $reader->parse('https://dotclear.org/blog/feed/atom');
        if ($parser) {
            $this
                ->object($parser)
                ->isInstanceOf(\Dotclear\Helper\Network\Feed\Parser::class)
                ->string($parser->title)
                ->isEqualTo('Dotclear News')
                ->string($parser->link)
                ->isEqualTo('https://dotclear.org/blog/')
            ;
        } else {
            $this
                ->dump(__LINE__)
                ->dump($parser);
        }

        // 2nd time (from cache)
        $parser = $reader->parse('https://dotclear.org/blog/feed/atom');
        if ($parser) {
            $this
                ->object($parser)
                ->isInstanceOf(\Dotclear\Helper\Network\Feed\Parser::class)
                ->string($parser->title)
                ->isEqualTo('Dotclear News')
                ->string($parser->link)
                ->isEqualTo('https://dotclear.org/blog/')
            ;
        } else {
            $this
                ->dump(__LINE__)
                ->dump($parser);
        }

        // Quick parse
        $parser = \Dotclear\Helper\Network\Feed\Reader::quickParse('https://dotclear.org/blog/feed/atom', $this->cacheDirectory);
        if ($parser) {
            $this
                ->object($parser)
                ->isInstanceOf(\Dotclear\Helper\Network\Feed\Parser::class)
                ->string($parser->title)
                ->isEqualTo('Dotclear News')
                ->string($parser->link)
                ->isEqualTo('https://dotclear.org/blog/')
            ;
        } else {
            $this
                ->dump(__LINE__)
                ->dump($parser);
        }
    }

    public function testBadURL()
    {
        $this
            ->given($parser = \Dotclear\Helper\Network\Feed\Reader::quickParse('https://dotclear.org/blog/feed/atome'))
            ->boolean($parser)
            ->isFalse()
        ;
    }
}
