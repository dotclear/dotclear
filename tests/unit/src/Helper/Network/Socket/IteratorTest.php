<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Network\Socket;

use Exception;
use PHPUnit\Framework\TestCase;

class IteratorTest extends TestCase
{
    public function testWithHandle()
    {
        $input = realpath(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', '..', 'fixtures', 'src', 'Helper', 'Network', 'Socket', 'stream-input.txt']));

        $handle   = fopen($input, 'rb');
        $iterator = new \Dotclear\Helper\Network\Socket\Iterator($handle);

        $this->assertIsResource(
            $handle
        );
        $this->assertIsNotClosedResource(
            $handle
        );
        $this->assertTrue(
            $iterator->valid()
        );
        $this->assertEquals(
            0,
            $iterator->key()
        );
        $this->assertEquals(
            'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Mauris pretium mauris erat, id pellentesque leo facilisis sed. Vestibulum sit amet maximus justo, ut vulputate turpis. Proin vestibulum gravida tellus a vulputate. Vivamus vestibulum metus cursus, ornare mauris ut, cursus mauris. Maecenas varius tincidunt gravida. Curabitur tristique et nunc id tempor. Duis commodo et neque a varius. Vestibulum ultricies fermentum erat quis aliquam. Maecenas at nunc sed purus faucibus rutrum. Donec tincidunt magna varius laoreet luctus. Etiam luctus dictum malesuada.' . "\n",
            $iterator->current()
        );
        $this->assertEquals(
            "\n",
            $iterator->current()
        );
        $this->assertEquals(
            0,
            $iterator->key()
        );
        $this->assertEquals(
            'Nam tempus augue sed risus placerat pharetra. Phasellus euismod varius pellentesque. Aenean eget pulvinar diam, sit amet scelerisque felis. In condimentum ultrices ex in sagittis. Integer tristique venenatis lacinia. Pellentesque vitae ex in risus ullamcorper luctus. Aenean tincidunt neque ex, tempus rhoncus neque iaculis in. Etiam lacinia sem at nulla rutrum imperdiet. Aliquam purus nunc, fringilla quis rhoncus ut, suscipit a nulla. Quisque euismod bibendum odio, non bibendum dolor. Nulla facilisi. Curabitur malesuada, nunc sit amet vehicula ultrices, magna lectus blandit augue, sed venenatis leo nibh ac nisl. Duis iaculis fermentum purus, eget egestas lorem accumsan non.' . "\n",
            $iterator->current()
        );
        $this->assertFalse(
            $iterator->current()
        );
        $this->assertFalse(
            $iterator->valid()
        );
        $this->assertEquals(
            0,
            $iterator->key()
        );

        $iterator->rewind();

        $this->assertFalse(
            $iterator->valid()
        );
        $this->assertEquals(
            0,
            $iterator->key()
        );

        $iterator->next();
        $this->assertFalse(
            $iterator->valid()
        );
        $this->assertEquals(
            1,
            $iterator->key()
        );

        fclose($handle);
    }

    public function testWithoutHandle()
    {
        $handle = null;

        $this->expectException(Exception::class);
        $iterator = new \Dotclear\Helper\Network\Socket\Iterator($handle);
    }
}
