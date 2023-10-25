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

class Dl extends atoum
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Dl();

        $this
            ->string($component->render())
            ->match('/<dl.*?>(?:.*?\n*)?<\/dl>/')
        ;
    }

    public function testWithId()
    {
        $component = new \Dotclear\Helper\Html\Form\Dl('myid');

        $this
            ->string($component->render())
            ->match('/<dl.*?>(?:.*?\n*)?<\/dl>/')
            ->contains('name="myid"')
            ->contains('id="myid"')
        ;
    }

    public function testGetDefaultElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Dl();

        $this
            ->string($component->getDefaultElement())
            ->isEqualTo('dl')
        ;
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Dl();

        $this
            ->string($component->getType())
            ->isEqualTo('Dotclear\Helper\Html\Form\Dl')
        ;
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Dl();

        $this
            ->string($component->getElement())
            ->isEqualTo('dl')
        ;
    }

    public function testGetElementWithOtherElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Dl('my', 'span');

        $this
            ->string($component->getElement())
            ->isEqualTo('span')
        ;
    }
}
