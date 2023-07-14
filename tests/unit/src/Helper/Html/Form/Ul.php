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

class Ul extends atoum
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Ul();

        $this
            ->string($component->render())
            ->match('/<ul.*?>(?:.*?\n*)?<\/ul>/')
        ;
    }

    public function testWithId()
    {
        $component = new \Dotclear\Helper\Html\Form\Ul('myid');

        $this
            ->string($component->render())
            ->match('/<ul.*?>(?:.*?\n*)?<\/ul>/')
            ->contains('name="myid"')
            ->contains('id="myid"')
        ;
    }

    public function testGetDefaultElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Ul();

        $this
            ->string($component->getDefaultElement())
            ->isEqualTo('ul')
        ;
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Ul();

        $this
            ->string($component->getType())
            ->isEqualTo('Dotclear\Helper\Html\Form\Ul')
        ;
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Ul();

        $this
            ->string($component->getElement())
            ->isEqualTo('ul')
        ;
    }

    public function testGetElementWithOtherElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Ul('my', 'span');

        $this
            ->string($component->getElement())
            ->isEqualTo('span')
        ;
    }
}
