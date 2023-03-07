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

class Para extends atoum
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Para();

        $this
            ->string($component->render())
            ->match('/<p.*?>(?:.*?\n*)?<\/p>/')
        ;
    }

    public function testWithEmptyItems()
    {
        $component = new \Dotclear\Helper\Html\Form\Para();
        $component->items([
        ]);

        $this
            ->string($component->render())
            ->match('/<p.*?><\/p>/')
        ;
    }

    public function testWithItems()
    {
        $component = new \Dotclear\Helper\Html\Form\Para();
        $component->items([
            new \Dotclear\Helper\Html\Form\Text(null, '1st value'),
            new \Dotclear\Helper\Html\Form\Text(null, '2nd value'),
        ]);

        $this
            ->string($component->render())
            ->match('/<p.*?>1st value2nd value<\/p>/')
        ;
    }

    public function testWithItemsWithSeparator()
    {
        $component = new \Dotclear\Helper\Html\Form\Para();
        $component
            ->separator('---')
            ->items(
                [
                    new \Dotclear\Helper\Html\Form\Text(null, '1st value'),
                    new \Dotclear\Helper\Html\Form\Text(null, '2nd value'),
                ]
            );

        $this
            ->string($component->render())
            ->match('/<p.*?>1st value---2nd value<\/p>/')
        ;
    }

    public function testWithId()
    {
        $component = new \Dotclear\Helper\Html\Form\Para('myid');

        $this
            ->string($component->render())
            ->match('/<p.*?>(?:.*?\n*)?<\/p>/')
            ->contains('name="myid"')
            ->contains('id="myid"')
        ;
    }

    public function testGetDefaultElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Para();

        $this
            ->string($component->getDefaultElement())
            ->isEqualTo('p')
        ;
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Para();

        $this
            ->string($component->getType())
            ->isEqualTo('Dotclear\Helper\Html\Form\Para')
        ;
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Para();

        $this
            ->string($component->getElement())
            ->isEqualTo('p')
        ;
    }

    public function testGetElementWithOtherElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Para('my', 'div');

        $this
            ->string($component->getElement())
            ->isEqualTo('div')
        ;
    }
}
