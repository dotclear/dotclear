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

class Note extends atoum
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Note();

        $this
            ->string($component->render())
            ->match('/<p.*?>(?:.*?\n*)?<\/p>/')
        ;
    }

    public function testWithText()
    {
        $component = new \Dotclear\Helper\Html\Form\Note();
        $component->text('Here');

        $this
            ->string($component->render())
            ->match('/<p.*?>Here<\/p>/')
        ;
    }

    public function testWithId()
    {
        $component = new \Dotclear\Helper\Html\Form\Note('myid');

        $this
            ->string($component->render())
            ->match('/<p.*?>(?:.*?\n*)?<\/p>/')
            ->contains('name="myid"')
            ->contains('id="myid"')
        ;
    }

    public function testGetDefaultElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Note();

        $this
            ->string($component->getDefaultElement())
            ->isEqualTo('p')
        ;
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Note();

        $this
            ->string($component->getType())
            ->isEqualTo('Dotclear\Helper\Html\Form\Note')
        ;
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Note();

        $this
            ->string($component->getElement())
            ->isEqualTo('p')
        ;
    }

    public function testGetElementWithOtherElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Note('my', 'span');

        $this
            ->string($component->getElement())
            ->isEqualTo('span')
        ;
    }
}
