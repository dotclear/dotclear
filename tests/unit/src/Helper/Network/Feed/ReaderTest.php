<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Network\Feed;

use Dotclear\Helper\File\Files;
use Exception;
use PHPUnit\Framework\TestCase;

class ReaderTest extends TestCase
{
    private string $cacheDirectory;

    protected function setUp(): void
    {
        $this->cacheDirectory = implode(DIRECTORY_SEPARATOR, [realpath(sys_get_temp_dir()), 'feed_reader']);
        if (!file_exists($this->cacheDirectory)) {
            mkdir($this->cacheDirectory);
        }
    }

    protected function tearDown(): void
    {
        Files::deltree($this->cacheDirectory);
    }

    public function test(): void
    {
        $reader = new \Dotclear\Helper\Network\Feed\Reader();

        try {
            $parser = $reader->parse('https://dotclear.org/feed/atom');
            if ($parser) {
                $this->assertEquals(
                    'Dotclear',
                    $parser->title
                );
                $this->assertEquals(
                    'https://dotclear.org/',
                    $parser->link
                );
            } else {
                fwrite(STDOUT, 'Error on parsing https://dotclear.org/feed/atom' . "\n");
            }

            // Again to use cache
            $reader->setCacheDir($this->cacheDirectory);
            $reader->setCacheTTL('-2 hours');
            $reader->setCacheTTL('4 hours');

            $parser = $reader->parse('https://dotclear.org/feed/atom');
            if ($parser) {
                $this->assertEquals(
                    'Dotclear',
                    $parser->title
                );
                $this->assertEquals(
                    'https://dotclear.org/',
                    $parser->link
                );
            } else {
                fwrite(STDOUT, 'Error on parsing https://dotclear.org/feed/atom (with cache)' . "\n");
            }

            // 2nd time (from cache)
            $parser = $reader->parse('https://dotclear.org/feed/atom');
            if ($parser) {
                $this->assertEquals(
                    'Dotclear',
                    $parser->title
                );
                $this->assertEquals(
                    'https://dotclear.org/',
                    $parser->link
                );
            } else {
                fwrite(STDOUT, 'Error on parsing https://dotclear.org/feed/atom (from cache)' . "\n");
            }

            // Quick parse
            $parser = \Dotclear\Helper\Network\Feed\Reader::quickParse('https://dotclear.org/feed/atom', $this->cacheDirectory);
            if ($parser) {
                $this->assertEquals(
                    'Dotclear',
                    $parser->title
                );
                $this->assertEquals(
                    'https://dotclear.org/',
                    $parser->link
                );
            } else {
                fwrite(STDOUT, 'Error on parsing https://dotclear.org/feed/atom (quick parse)' . "\n");
            }
        } catch (Exception) {
            $this->expectNotToPerformAssertions();
        }
    }

    public function testBadURL(): void
    {
        try {
            $parser = \Dotclear\Helper\Network\Feed\Reader::quickParse('https://dotclear.org/feed/atome');

            $this->assertFalse(
                $parser
            );
        } catch (Exception) {
            $this->expectNotToPerformAssertions();
        }
    }
}
