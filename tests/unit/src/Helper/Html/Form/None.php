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

class None extends atoum
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\None();

        $this
            ->string($component->render())
            ->isEqualTo('')
        ;
    }

    public function testGetDefaultElement()
    {
        $component = new \Dotclear\Helper\Html\Form\None();

        $this
            ->string($component->getDefaultElement())
            ->isEqualTo('')
        ;
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\None();

        $this
            ->string($component->getType())
            ->isEqualTo('Dotclear\Helper\Html\Form\None')
        ;
    }
}
