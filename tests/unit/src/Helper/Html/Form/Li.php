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

class Li extends atoum
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Li();

        $this
            ->string($component->render())
            ->match('/<li.*?>(?:.*?\n*)?<\/li>/')
        ;
    }

    public function testWithText()
    {
        $component = new \Dotclear\Helper\Html\Form\Li();
        $component->text('Here');

        $this
            ->string($component->render())
            ->match('/<li.*?>Here<\/li>/')
        ;
    }

    public function testWithId()
    {
        $component = new \Dotclear\Helper\Html\Form\Li('myid');

        $this
            ->string($component->render())
            ->match('/<li.*?>(?:.*?\n*)?<\/li>/')
            ->contains('name="myid"')
            ->contains('id="myid"')
        ;
    }

    public function testWithType()
    {
        $component = new \Dotclear\Helper\Html\Form\Li();
        $component->type('I');

        $this
            ->string($component->render())
            ->match('/<li.*?>(?:.*?\n*)?<\/li>/')
            ->contains('type="I"')
        ;
    }
    public function testGetDefaultElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Li();

        $this
            ->string($component->getDefaultElement())
            ->isEqualTo('li')
        ;
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Li();

        $this
            ->string($component->getType())
            ->isEqualTo('Dotclear\Helper\Html\Form\Li')
        ;
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Li();

        $this
            ->string($component->getElement())
            ->isEqualTo('li')
        ;
    }

    public function testGetElementWithOtherElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Li('my', 'span');

        $this
            ->string($component->getElement())
            ->isEqualTo('span')
        ;
    }
}
