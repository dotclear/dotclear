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

class Btn extends atoum
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Btn(null, 'My Btn');

        $this
            ->string($component->render())
            ->match('/<button.*?>(?:.*?\n*)?<\/button>/')
            ->contains('My Btn')
        ;
    }

    public function testWithText()
    {
        $component = new \Dotclear\Helper\Html\Form\Btn();
        $component->text('My Btn');

        $this
            ->string($component->render())
            ->match('/<button.*?>(?:.*?\n*)?<\/button>/')
            ->contains('My Btn')
        ;
    }

    public function testWithPopovertarget()
    {
        $component = new \Dotclear\Helper\Html\Form\Btn();
        $component->popovertarget('My-Popover');

        $this
            ->string($component->render())
            ->match('/<button.*?>(?:.*?\n*)?<\/button>/')
            ->contains('popovertarget="My-Popover"')
        ;
    }

    public function testWithPopovertargetaction()
    {
        $component = new \Dotclear\Helper\Html\Form\Btn();
        $component->popovertargetaction('show');

        $this
            ->string($component->render())
            ->match('/<button.*?>(?:.*?\n*)?<\/button>/')
            ->contains('popovertargetaction="show"')
        ;
    }

    public function testWithoutText()
    {
        $component = new \Dotclear\Helper\Html\Form\Btn();

        $this
            ->string($component->render())
            ->match('/<button.*?><\/button>/')
        ;
    }

    public function testWithId()
    {
        $component = new \Dotclear\Helper\Html\Form\Btn('myid', 'My Btn');

        $this
            ->string($component->render())
            ->match('/<button.*?>(?:.*?\n*)?<\/button>/')
            ->contains('name="myid"')
            ->contains('id="myid"')
        ;
    }

    public function testGetDefaultElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Btn('My Btn');

        $this
            ->string($component->getDefaultElement())
            ->isEqualTo('button')
        ;
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Btn('My Btn');

        $this
            ->string($component->getType())
            ->isEqualTo('Dotclear\Helper\Html\Form\Btn')
        ;
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Btn('My Btn');

        $this
            ->string($component->getElement())
            ->isEqualTo('button')
        ;
    }

    public function testGetElementWithOtherElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Btn('myid', 'My Btn', 'span');

        $this
            ->string($component->getElement())
            ->isEqualTo('span')
        ;
    }
}
