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

namespace tests\unit\Dotclear\Helper\Html\Form;

require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', '..', 'bootstrap.php']);

use atoum;

class Span extends atoum
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Span('TEXT');

        $this
            ->string($component->render())
            ->isEqualTo('<span>TEXT</span>' . "\n")
        ;
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Span();

        $this
            ->string($component->getElement())
            ->isEqualTo('span')
        ;
    }
}
