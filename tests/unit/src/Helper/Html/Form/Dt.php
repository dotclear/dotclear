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

class Dt extends atoum
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Dt();

        $this
            ->string($component->render())
            ->match('/<dt.*?>(?:.*?\n*)?<\/dt>/')
        ;
    }

    public function testWithText()
    {
        $component = new \Dotclear\Helper\Html\Form\Dt();
        $component->text('Here');

        $this
            ->string($component->render())
            ->match('/<dt.*?>Here<\/dt>/')
        ;
    }

    public function testWithId()
    {
        $component = new \Dotclear\Helper\Html\Form\Dt('myid');

        $this
            ->string($component->render())
            ->match('/<dt.*?>(?:.*?\n*)?<\/dt>/')
            ->contains('name="myid"')
            ->contains('id="myid"')
        ;
    }

    public function testGetDefaultElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Dt();

        $this
            ->string($component->getDefaultElement())
            ->isEqualTo('dt')
        ;
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Dt();

        $this
            ->string($component->getType())
            ->isEqualTo('Dotclear\Helper\Html\Form\Dt')
        ;
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Dt();

        $this
            ->string($component->getElement())
            ->isEqualTo('dt')
        ;
    }

    public function testGetElementWithOtherElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Dt('my', 'span');

        $this
            ->string($component->getElement())
            ->isEqualTo('span')
        ;
    }
}
