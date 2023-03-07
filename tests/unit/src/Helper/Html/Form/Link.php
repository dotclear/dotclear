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

class Link extends atoum
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Link();

        $this
            ->string($component->render())
            ->match('/<a.*?>(?:.*?\n*)?<\/a>/')
        ;
    }

    public function testWithHref()
    {
        $component = new \Dotclear\Helper\Html\Form\Link();
        $component->href('#here');

        $this
            ->string($component->render())
            ->match('/<a.*?>(?:.*?\n*)?<\/a>/')
            ->contains('href="#here"')
        ;
    }

    public function testWithText()
    {
        $component = new \Dotclear\Helper\Html\Form\Link();
        $component->text('Here');

        $this
            ->string($component->render())
            ->match('/<a.*?>Here<\/a>/')
        ;
    }

    public function testWithId()
    {
        $component = new \Dotclear\Helper\Html\Form\Link('myid');

        $this
            ->string($component->render())
            ->match('/<a.*?>(?:.*?\n*)?<\/a>/')
            ->contains('name="myid"')
            ->contains('id="myid"')
        ;
    }

    public function testGetDefaultElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Link();

        $this
            ->string($component->getDefaultElement())
            ->isEqualTo('a')
        ;
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Link();

        $this
            ->string($component->getType())
            ->isEqualTo('Dotclear\Helper\Html\Form\Link')
        ;
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Link();

        $this
            ->string($component->getElement())
            ->isEqualTo('a')
        ;
    }

    public function testGetElementWithOtherElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Link('my', 'slot');

        $this
            ->string($component->getElement())
            ->isEqualTo('slot')
        ;
    }
}
