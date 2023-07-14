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

class Th extends atoum
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Th();

        $this
            ->string($component->render())
            ->match('/<th.*?>(?:.*?\n*)?<\/th>/')
        ;
    }

    public function testWithText()
    {
        $component = new \Dotclear\Helper\Html\Form\Th();
        $component->text('Here');

        $this
            ->string($component->render())
            ->match('/<th.*?>Here<\/th>/')
        ;
    }

    public function testWithId()
    {
        $component = new \Dotclear\Helper\Html\Form\Th('myid');

        $this
            ->string($component->render())
            ->match('/<th.*?>(?:.*?\n*)?<\/th>/')
            ->contains('name="myid"')
            ->contains('id="myid"')
        ;
    }

    public function testWithColspan()
    {
        $component = new \Dotclear\Helper\Html\Form\Th();
        $component->colspan(2);

        $this
            ->string($component->render())
            ->match('/<th.*?>(?:.*?\n*)?<\/th>/')
            ->contains('colspan=2')
        ;
    }

    public function testWithRowspan()
    {
        $component = new \Dotclear\Helper\Html\Form\Th();
        $component->rowspan(4);

        $this
            ->string($component->render())
            ->match('/<th.*?>(?:.*?\n*)?<\/th>/')
            ->contains('rowspan=4')
        ;
    }

    public function testWithHeaders()
    {
        $component = new \Dotclear\Helper\Html\Form\Th();
        $component->headers('id1 id2');

        $this
            ->string($component->render())
            ->match('/<th.*?>(?:.*?\n*)?<\/th>/')
            ->contains('headers="id1 id2"')
        ;
    }

    public function testWithScope()
    {
        $component = new \Dotclear\Helper\Html\Form\Th();
        $component->scope('row');

        $this
            ->string($component->render())
            ->match('/<th.*?>(?:.*?\n*)?<\/th>/')
            ->contains('scope="row"')
        ;
    }

    public function testGetDefaultElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Th();

        $this
            ->string($component->getDefaultElement())
            ->isEqualTo('th')
        ;
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Th();

        $this
            ->string($component->getType())
            ->isEqualTo('Dotclear\Helper\Html\Form\Th')
        ;
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Th();

        $this
            ->string($component->getElement())
            ->isEqualTo('th')
        ;
    }

    public function testGetElementWithOtherElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Th('my', 'span');

        $this
            ->string($component->getElement())
            ->isEqualTo('span')
        ;
    }
}
