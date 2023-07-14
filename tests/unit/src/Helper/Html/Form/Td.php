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

class Td extends atoum
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Td();

        $this
            ->string($component->render())
            ->match('/<td.*?>(?:.*?\n*)?<\/td>/')
        ;
    }

    public function testWithText()
    {
        $component = new \Dotclear\Helper\Html\Form\Td();
        $component->text('Here');

        $this
            ->string($component->render())
            ->match('/<td.*?>Here<\/td>/')
        ;
    }

    public function testWithId()
    {
        $component = new \Dotclear\Helper\Html\Form\Td('myid');

        $this
            ->string($component->render())
            ->match('/<td.*?>(?:.*?\n*)?<\/td>/')
            ->contains('name="myid"')
            ->contains('id="myid"')
        ;
    }

    public function testWithColspan()
    {
        $component = new \Dotclear\Helper\Html\Form\Td();
        $component->colspan(2);

        $this
            ->string($component->render())
            ->match('/<td.*?>(?:.*?\n*)?<\/td>/')
            ->contains('colspan=2')
        ;
    }

    public function testWithRowspan()
    {
        $component = new \Dotclear\Helper\Html\Form\Td();
        $component->rowspan(4);

        $this
            ->string($component->render())
            ->match('/<td.*?>(?:.*?\n*)?<\/td>/')
            ->contains('rowspan=4')
        ;
    }

    public function testWithHeaders()
    {
        $component = new \Dotclear\Helper\Html\Form\Td();
        $component->headers('id1 id2');

        $this
            ->string($component->render())
            ->match('/<td.*?>(?:.*?\n*)?<\/td>/')
            ->contains('headers="id1 id2"')
        ;
    }

    public function testGetDefaultElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Td();

        $this
            ->string($component->getDefaultElement())
            ->isEqualTo('td')
        ;
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Td();

        $this
            ->string($component->getType())
            ->isEqualTo('Dotclear\Helper\Html\Form\Td')
        ;
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Td();

        $this
            ->string($component->getElement())
            ->isEqualTo('td')
        ;
    }

    public function testGetElementWithOtherElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Td('my', 'span');

        $this
            ->string($component->getElement())
            ->isEqualTo('span')
        ;
    }
}
