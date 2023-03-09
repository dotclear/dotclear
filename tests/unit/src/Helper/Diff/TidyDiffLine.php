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

namespace tests\unit\Dotclear\Helper\Diff;

require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', 'bootstrap.php']);

use atoum;

class TidyDiffLine extends atoum
{
    public function test()
    {
        $component = new \Dotclear\Helper\Diff\TidyDiffLine('context', [3, 4], 'content');

        $this
            ->string($component->type)
            ->isEqualTo('context')
        ;

        $this
            ->array($component->lines)
            ->isEqualTo([3, 4])
        ;

        $this
            ->string($component->content)
            ->isEqualTo('content')
        ;

        $this
            ->variable($component->unknown)
            ->isNull()
        ;
    }

    public function testWithUnknownType()
    {
        $component = new \Dotclear\Helper\Diff\TidyDiffLine('unknown', [3, 4], 'content');

        $this
            ->variable($component->type)
            ->isNull()
        ;

        $this
            ->variable($component->lines)
            ->isNull()
        ;

        $this
            ->variable($component->content)
            ->isNull()
        ;
    }

    public function testWithNullLines()
    {
        $component = new \Dotclear\Helper\Diff\TidyDiffLine('unknown', null, 'content');

        $this
            ->variable($component->type)
            ->isNull()
        ;

        $this
            ->variable($component->lines)
            ->isNull()
        ;

        $this
            ->variable($component->content)
            ->isNull()
        ;
    }

    public function testWithNullContent()
    {
        $component = new \Dotclear\Helper\Diff\TidyDiffLine('unknown', [3, 4], null);

        $this
            ->variable($component->type)
            ->isNull()
        ;

        $this
            ->variable($component->lines)
            ->isNull()
        ;

        $this
            ->variable($component->content)
            ->isNull()
        ;
    }

    public function testOverwrite()
    {
        $component = new \Dotclear\Helper\Diff\TidyDiffLine('context', [3, 4], 'content');

        $component->overwrite('new content');

        $this
            ->string($component->content)
            ->isEqualTo('new content')
        ;
    }

    public function testOverwriteWithNull()
    {
        $component = new \Dotclear\Helper\Diff\TidyDiffLine('context', [3, 4], 'content');

        $component->overwrite(null);

        $this
            ->string($component->content)
            ->isEqualTo('content')
        ;
    }
}
