<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Network\Feed;

use Dotclear\Helper\File\Files;
use PHPUnit\Framework\TestCase;

class ReaderTest extends TestCase
{
    private string $cacheDirectory;

    protected function setUp(): void
    {
        $this->cacheDirectory = realpath(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', '..', 'fixtures', 'src', 'Helper', 'Network', 'Feed']));
        $this->cacheDirectory .= DIRECTORY_SEPARATOR . 'cache';
        if (!file_exists($this->cacheDirectory)) {
            mkdir($this->cacheDirectory);
        }
    }

    protected function tearDown(): void
    {
        Files::deltree($this->cacheDirectory);
    }

    public function test()
    {
        $reader = new \Dotclear\Helper\Network\Feed\Reader();

        $parser = $reader->parse('https://dotclear.org/blog/feed/atom');
        if ($parser) {
            $this->assertTrue(
                $parser instanceof \Dotclear\Helper\Network\Feed\Parser
            );
            $this->assertEquals(
                'Dotclear News',
                $parser->title
            );
            $this->assertEquals(
                'https://dotclear.org/blog/',
                $parser->link
            );
        } else {
            fwrite(STDOUT, 'Error on parsing https://dotclear.org/blog/feed/atom' . "\n");
        }

        // Again to use cache
        $reader->setCacheDir($this->cacheDirectory);
        $reader->setCacheTTL('-2 hours');
        $reader->setCacheTTL('4 hours');

        $parser = $reader->parse('https://dotclear.org/blog/feed/atom');
        if ($parser) {
            $this->assertTrue(
                $parser instanceof \Dotclear\Helper\Network\Feed\Parser
            );
            $this->assertEquals(
                'Dotclear News',
                $parser->title
            );
            $this->assertEquals(
                'https://dotclear.org/blog/',
                $parser->link
            );
        } else {
            fwrite(STDOUT, 'Error on parsing https://dotclear.org/blog/feed/atom (with cache)' . "\n");
        }

        // 2nd time (from cache)
        $parser = $reader->parse('https://dotclear.org/blog/feed/atom');
        if ($parser) {
            $this->assertTrue(
                $parser instanceof \Dotclear\Helper\Network\Feed\Parser
            );
            $this->assertEquals(
                'Dotclear News',
                $parser->title
            );
            $this->assertEquals(
                'https://dotclear.org/blog/',
                $parser->link
            );
        } else {
            fwrite(STDOUT, 'Error on parsing https://dotclear.org/blog/feed/atom (from cache)' . "\n");
        }

        // Quick parse
        $parser = \Dotclear\Helper\Network\Feed\Reader::quickParse('https://dotclear.org/blog/feed/atom', $this->cacheDirectory);
        if ($parser) {
            $this->assertTrue(
                $parser instanceof \Dotclear\Helper\Network\Feed\Parser
            );
            $this->assertEquals(
                'Dotclear News',
                $parser->title
            );
            $this->assertEquals(
                'https://dotclear.org/blog/',
                $parser->link
            );
        } else {
            fwrite(STDOUT, 'Error on parsing https://dotclear.org/blog/feed/atom (quick parse)' . "\n");
        }
    }

    public function testBadURL()
    {
        $parser = \Dotclear\Helper\Network\Feed\Reader::quickParse('https://dotclear.org/blog/feed/atome');

        $this->assertFalse(
            $parser
        );
    }
}
