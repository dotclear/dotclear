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

class Tfoot extends atoum
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Tfoot();

        $this
            ->string($component->render())
            ->match('/<tfoot.*?>(?:.*?\n*)?<\/tfoot>/')
        ;
    }

    public function testWithEmptyItems()
    {
        $component = new \Dotclear\Helper\Html\Form\Tfoot();
        $component->items([
        ]);

        $this
            ->string($component->render())
            ->match('/<tfoot.*?><\/tfoot>/')
        ;
    }

    public function testWithItems()
    {
        $component = new \Dotclear\Helper\Html\Form\Tfoot();
        $component->rows([
            (new \Dotclear\Helper\Html\Form\Tr()),
            (new \Dotclear\Helper\Html\Form\Tr()),
        ]);

        $this
            ->string($component->render())
            ->match('/<tfoot.*?><tr><\/tr>\n*<tr><\/tr>\n*<\/tfoot>/')
        ;
    }

    public function testWithId()
    {
        $component = new \Dotclear\Helper\Html\Form\Tfoot('myid');

        $this
            ->string($component->render())
            ->match('/<tfoot.*?>(?:.*?\n*)?<\/tfoot>/')
            ->contains('name="myid"')
            ->contains('id="myid"')
        ;
    }

    public function testGetDefaultElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Tfoot();

        $this
            ->string($component->getDefaultElement())
            ->isEqualTo('tfoot')
        ;
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Tfoot();

        $this
            ->string($component->getType())
            ->isEqualTo('Dotclear\Helper\Html\Form\Tfoot')
        ;
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Tfoot();

        $this
            ->string($component->getElement())
            ->isEqualTo('tfoot')
        ;
    }

    public function testGetElementWithOtherElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Tfoot('my', 'div');

        $this
            ->string($component->getElement())
            ->isEqualTo('div')
        ;
    }
}
