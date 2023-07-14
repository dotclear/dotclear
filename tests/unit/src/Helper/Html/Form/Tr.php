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

class Tr extends atoum
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Tr();

        $this
            ->string($component->render())
            ->match('/<tr.*?>(?:.*?\n*)?<\/tr>/')
        ;
    }

    public function testWithEmptyItems()
    {
        $component = new \Dotclear\Helper\Html\Form\Tr();
        $component->items([
        ]);

        $this
            ->string($component->render())
            ->match('/<tr.*?><\/tr>/')
        ;
    }

    public function testWithItems()
    {
        $component = new \Dotclear\Helper\Html\Form\Tr();
        $component->cols([
            (new \Dotclear\Helper\Html\Form\Th())->text('1st value'),
            (new \Dotclear\Helper\Html\Form\Td())->text('2nd value'),
        ]);

        $this
            ->string($component->render())
            ->match('/<tr.*?><th>1st value<\/th><td>2nd value<\/td><\/tr>/')
        ;
    }

    public function testWithId()
    {
        $component = new \Dotclear\Helper\Html\Form\Tr('myid');

        $this
            ->string($component->render())
            ->match('/<tr.*?>(?:.*?\n*)?<\/tr>/')
            ->contains('name="myid"')
            ->contains('id="myid"')
        ;
    }

    public function testGetDefaultElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Tr();

        $this
            ->string($component->getDefaultElement())
            ->isEqualTo('tr')
        ;
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Tr();

        $this
            ->string($component->getType())
            ->isEqualTo('Dotclear\Helper\Html\Form\Tr')
        ;
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Tr();

        $this
            ->string($component->getElement())
            ->isEqualTo('tr')
        ;
    }

    public function testGetElementWithOtherElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Tr('my', 'div');

        $this
            ->string($component->getElement())
            ->isEqualTo('div')
        ;
    }
}
