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

class Legend extends atoum
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Legend('My Legend');

        $this
            ->string($component->render())
            ->match('/<legend.*?>(?:.*?\n*)?<\/legend>/')
            ->contains('My Legend')
        ;
    }

    public function testWithText()
    {
        $component = new \Dotclear\Helper\Html\Form\Legend();
        $component->text('My Legend');

        $this
            ->string($component->render())
            ->match('/<legend.*?>(?:.*?\n*)?<\/legend>/')
            ->contains('My Legend')
        ;
    }

    public function testWithoutText()
    {
        $component = new \Dotclear\Helper\Html\Form\Legend();

        $this
            ->string($component->render())
            ->match('/<legend.*?><\/legend>/')
        ;
    }

    public function testWithId()
    {
        $component = new \Dotclear\Helper\Html\Form\Legend('My Legend', 'myid');

        $this
            ->string($component->render())
            ->match('/<legend.*?>(?:.*?\n*)?<\/legend>/')
            ->contains('name="myid"')
            ->contains('id="myid"')
        ;
    }

    public function testGetDefaultElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Legend('My Legend');

        $this
            ->string($component->getDefaultElement())
            ->isEqualTo('legend')
        ;
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Legend('My Legend');

        $this
            ->string($component->getType())
            ->isEqualTo('Dotclear\Helper\Html\Form\Legend')
        ;
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Legend('My Legend');

        $this
            ->string($component->getElement())
            ->isEqualTo('legend')
        ;
    }

    public function testGetElementWithOtherElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Legend('My Legend', 'myid', 'span');

        $this
            ->string($component->getElement())
            ->isEqualTo('span')
        ;
    }
}
