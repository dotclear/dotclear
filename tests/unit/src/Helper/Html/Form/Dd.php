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

class Dd extends atoum
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Dd();

        $this
            ->string($component->render())
            ->match('/<dd.*?>(?:.*?\n*)?<\/dd>/')
        ;
    }

    public function testWithText()
    {
        $component = new \Dotclear\Helper\Html\Form\Dd();
        $component->text('Here');

        $this
            ->string($component->render())
            ->match('/<dd.*?>Here<\/dd>/')
        ;
    }

    public function testWithId()
    {
        $component = new \Dotclear\Helper\Html\Form\Dd('myid');

        $this
            ->string($component->render())
            ->match('/<dd.*?>(?:.*?\n*)?<\/dd>/')
            ->contains('name="myid"')
            ->contains('id="myid"')
        ;
    }

    public function testGetDefaultElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Dd();

        $this
            ->string($component->getDefaultElement())
            ->isEqualTo('dd')
        ;
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Dd();

        $this
            ->string($component->getType())
            ->isEqualTo('Dotclear\Helper\Html\Form\Dd')
        ;
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Dd();

        $this
            ->string($component->getElement())
            ->isEqualTo('dd')
        ;
    }

    public function testGetElementWithOtherElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Dd('my', 'span');

        $this
            ->string($component->getElement())
            ->isEqualTo('span')
        ;
    }
}
