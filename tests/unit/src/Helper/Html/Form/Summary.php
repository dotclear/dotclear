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

class Summary extends atoum
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Summary('My summary');

        $this
            ->string($component->render())
            ->match('/<summary*?>(?:.*?\n*)?<\/summary>/')
            ->contains('My summary')
        ;
    }

    public function testWithText()
    {
        $component = new \Dotclear\Helper\Html\Form\Summary();
        $component->text('My summary');

        $this
            ->string($component->render())
            ->match('/<summary.*?>(?:.*?\n*)?<\/summary>/')
            ->contains('My summary')
        ;
    }

    public function testWithoutText()
    {
        $component = new \Dotclear\Helper\Html\Form\Summary();

        $this
            ->string($component->render())
            ->match('/<summary.*?><\/summary>/')
        ;
    }

    public function testWithId()
    {
        $component = new \Dotclear\Helper\Html\Form\Summary('My summary', 'myid');

        $this
            ->string($component->render())
            ->match('/<summary.*?>(?:.*?\n*)?<\/summary>/')
            ->contains('name="myid"')
            ->contains('id="myid"')
        ;
    }

    public function testGetDefaultElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Summary('My summary');

        $this
            ->string($component->getDefaultElement())
            ->isEqualTo('summary')
        ;
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Summary('My summary');

        $this
            ->string($component->getType())
            ->isEqualTo('Dotclear\Helper\Html\Form\Summary')
        ;
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Summary('My summary');

        $this
            ->string($component->getElement())
            ->isEqualTo('summary')
        ;
    }

    public function testGetElementWithOtherElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Summary('My summary', 'myid', 'span');

        $this
            ->string($component->getElement())
            ->isEqualTo('span')
        ;
    }
}
