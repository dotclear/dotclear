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

namespace tests\unit\Dotclear\Helper\Network\Socket;

require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', '..', 'bootstrap.php']);

use atoum;

class Iterator extends atoum
{
    public function test()
    {
        $input = realpath(implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', '..', 'fixtures', 'src', 'Helper', 'Network', 'Socket', 'stream-input.txt']));

        $handle   = fopen($input, 'rb');
        $iterator = new \Dotclear\Helper\Network\Socket\Iterator($handle);

        $this
            ->resource($handle)
            ->isStream()
            ->boolean($iterator->valid())
            ->isTrue()
            ->integer($iterator->key())
            ->isEqualTo(0)
            ->string($iterator->current())
            ->isEqualTo('Lorem ipsum dolor sit amet, consectetur adipiscing elit. Mauris pretium mauris erat, id pellentesque leo facilisis sed. Vestibulum sit amet maximus justo, ut vulputate turpis. Proin vestibulum gravida tellus a vulputate. Vivamus vestibulum metus cursus, ornare mauris ut, cursus mauris. Maecenas varius tincidunt gravida. Curabitur tristique et nunc id tempor. Duis commodo et neque a varius. Vestibulum ultricies fermentum erat quis aliquam. Maecenas at nunc sed purus faucibus rutrum. Donec tincidunt magna varius laoreet luctus. Etiam luctus dictum malesuada.' . "\n")
            ->string($iterator->current())
            ->isEqualTo("\n")
            ->integer($iterator->key())
            ->isEqualTo(0)
            ->string($iterator->current())
            ->isEqualTo('Nam tempus augue sed risus placerat pharetra. Phasellus euismod varius pellentesque. Aenean eget pulvinar diam, sit amet scelerisque felis. In condimentum ultrices ex in sagittis. Integer tristique venenatis lacinia. Pellentesque vitae ex in risus ullamcorper luctus. Aenean tincidunt neque ex, tempus rhoncus neque iaculis in. Etiam lacinia sem at nulla rutrum imperdiet. Aliquam purus nunc, fringilla quis rhoncus ut, suscipit a nulla. Quisque euismod bibendum odio, non bibendum dolor. Nulla facilisi. Curabitur malesuada, nunc sit amet vehicula ultrices, magna lectus blandit augue, sed venenatis leo nibh ac nisl. Duis iaculis fermentum purus, eget egestas lorem accumsan non.' . "\n")
            ->boolean($iterator->current())
            ->isFalse()
            ->boolean($iterator->valid())
            ->isFalse()
            ->integer($iterator->key())
            ->isEqualTo(0)
            ->when($iterator->rewind())
            ->boolean($iterator->valid())
            ->isFalse()
            ->integer($iterator->key())
            ->isEqualTo(0)
            ->when($iterator->next())
            ->boolean($iterator->valid())
            ->isFalse()
            ->integer($iterator->key())
            ->isEqualTo(1)
        ;

        fclose($handle);
    }

    public function testNoHandle()
    {
        $handle = null;

        $this
            ->exception(function () use ($handle) { $iterator = new \Dotclear\Helper\Network\Socket\Iterator($handle); })
        ;
    }
}
