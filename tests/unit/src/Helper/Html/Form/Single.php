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

class Single extends atoum
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Single('hr');

        $this
            ->string($component->render())
            ->isEqualTo('<hr>')
        ;
    }

    public function testWithEmptyElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Single('');

        $this
            ->string($component->render())
            ->isEqualTo('')
        ;
    }

    public function testWithACommonAttribute()
    {
        $component = new \Dotclear\Helper\Html\Form\Single('hr');
        $component->setIdentifier('myid');

        $this
            ->string($component->render())
            ->match('/<hr.*?>/')
            ->contains('name="myid"')
            ->contains('id="myid"')
        ;
    }

    public function testGetDefaultElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Single('');

        $this
            ->string($component->getDefaultElement())
            ->isEqualTo('')
        ;
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Single('');

        $this
            ->string($component->getType())
            ->isEqualTo('Dotclear\Helper\Html\Form\Single')
        ;
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Single('');

        $this
            ->string($component->getElement())
            ->isEqualTo('')
        ;
    }

    public function testGetElementWithOtherElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Single('br');

        $this
            ->string($component->getElement())
            ->isEqualTo('br')
        ;
    }
}
