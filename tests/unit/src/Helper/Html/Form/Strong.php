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

class Strong extends atoum
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Strong('TEXT');

        $this
            ->string($component->render())
            ->isEqualTo('<strong>TEXT</strong>' . "\n")
        ;
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Strong();

        $this
            ->string($component->getElement())
            ->isEqualTo('strong')
        ;
    }
}
