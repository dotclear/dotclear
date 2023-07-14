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

class Thead extends atoum
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Thead();

        $this
            ->string($component->render())
            ->match('/<thead.*?>(?:.*?\n*)?<\/thead>/')
        ;
    }

    public function testWithEmptyItems()
    {
        $component = new \Dotclear\Helper\Html\Form\Thead();
        $component->items([
        ]);

        $this
            ->string($component->render())
            ->match('/<thead.*?><\/thead>/')
        ;
    }

    public function testWithItems()
    {
        $component = new \Dotclear\Helper\Html\Form\Thead();
        $component->rows([
            (new \Dotclear\Helper\Html\Form\Tr()),
            (new \Dotclear\Helper\Html\Form\Tr()),
        ]);

        $this
            ->string($component->render())
            ->match('/<thead.*?><tr><\/tr>\n*<tr><\/tr>\n*<\/thead>/')
        ;
    }

    public function testWithId()
    {
        $component = new \Dotclear\Helper\Html\Form\Thead('myid');

        $this
            ->string($component->render())
            ->match('/<thead.*?>(?:.*?\n*)?<\/thead>/')
            ->contains('name="myid"')
            ->contains('id="myid"')
        ;
    }

    public function testGetDefaultElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Thead();

        $this
            ->string($component->getDefaultElement())
            ->isEqualTo('thead')
        ;
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Thead();

        $this
            ->string($component->getType())
            ->isEqualTo('Dotclear\Helper\Html\Form\Thead')
        ;
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Thead();

        $this
            ->string($component->getElement())
            ->isEqualTo('thead')
        ;
    }

    public function testGetElementWithOtherElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Thead('my', 'div');

        $this
            ->string($component->getElement())
            ->isEqualTo('div')
        ;
    }
}
