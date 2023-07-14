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

class Tbody extends atoum
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Tbody();

        $this
            ->string($component->render())
            ->match('/<tbody.*?>(?:.*?\n*)?<\/tbody>/')
        ;
    }

    public function testWithEmptyItems()
    {
        $component = new \Dotclear\Helper\Html\Form\Tbody();
        $component->items([
        ]);

        $this
            ->string($component->render())
            ->match('/<tbody.*?><\/tbody>/')
        ;
    }

    public function testWithItems()
    {
        $component = new \Dotclear\Helper\Html\Form\Tbody();
        $component->rows([
            (new \Dotclear\Helper\Html\Form\Tr()),
            (new \Dotclear\Helper\Html\Form\Tr()),
        ]);

        $this
            ->string($component->render())
            ->match('/<tbody.*?><tr><\/tr>\n*<tr><\/tr>\n*<\/tbody>/')
        ;
    }

    public function testWithId()
    {
        $component = new \Dotclear\Helper\Html\Form\Tbody('myid');

        $this
            ->string($component->render())
            ->match('/<tbody.*?>(?:.*?\n*)?<\/tbody>/')
            ->contains('name="myid"')
            ->contains('id="myid"')
        ;
    }

    public function testGetDefaultElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Tbody();

        $this
            ->string($component->getDefaultElement())
            ->isEqualTo('tbody')
        ;
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Tbody();

        $this
            ->string($component->getType())
            ->isEqualTo('Dotclear\Helper\Html\Form\Tbody')
        ;
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Tbody();

        $this
            ->string($component->getElement())
            ->isEqualTo('tbody')
        ;
    }

    public function testGetElementWithOtherElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Tbody('my', 'div');

        $this
            ->string($component->getElement())
            ->isEqualTo('div')
        ;
    }
}
