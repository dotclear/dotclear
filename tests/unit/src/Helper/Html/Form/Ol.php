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

class Ol extends atoum
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Ol();

        $this
            ->string($component->render())
            ->match('/<ol.*?>(?:.*?\n*)?<\/ol>/')
        ;
    }

    public function testWithId()
    {
        $component = new \Dotclear\Helper\Html\Form\Ol('myid');

        $this
            ->string($component->render())
            ->match('/<ol.*?>(?:.*?\n*)?<\/ol>/')
            ->contains('name="myid"')
            ->contains('id="myid"')
        ;
    }

    public function testWithStart()
    {
        $component = new \Dotclear\Helper\Html\Form\Ol();
        $component->start('3');

        $this
            ->string($component->render())
            ->match('/<ol.*?>(?:.*?\n*)?<\/ol>/')
            ->contains('start="3"')
        ;
    }

    public function testWithType()
    {
        $component = new \Dotclear\Helper\Html\Form\Ol();
        $component->type('I');

        $this
            ->string($component->render())
            ->match('/<ol.*?>(?:.*?\n*)?<\/ol>/')
            ->contains('type="I"')
        ;
    }

    public function testWithReversed()
    {
        $component = new \Dotclear\Helper\Html\Form\Ol();
        $component->reversed(true);

        $this
            ->string($component->render())
            ->match('/<ol.*?>(?:.*?\n*)?<\/ol>/')
            ->contains('reversed')
        ;

        $component->reversed(false);

        $this
            ->string($component->render())
            ->match('/<ol.*?>(?:.*?\n*)?<\/ol>/')
            ->notContains('reversed')
        ;
    }

    public function testGetDefaultElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Ol();

        $this
            ->string($component->getDefaultElement())
            ->isEqualTo('ol')
        ;
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Ol();

        $this
            ->string($component->getType())
            ->isEqualTo('Dotclear\Helper\Html\Form\Ol')
        ;
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Ol();

        $this
            ->string($component->getElement())
            ->isEqualTo('ol')
        ;
    }

    public function testGetElementWithOtherElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Ol('my', 'span');

        $this
            ->string($component->getElement())
            ->isEqualTo('span')
        ;
    }
}
