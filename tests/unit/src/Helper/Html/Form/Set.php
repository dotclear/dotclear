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

class Set extends atoum
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Set();

        $this
            ->string($component->render())
            ->match('/(?:.*?\n*)?/')
        ;
    }

    public function testWithEmptyItems()
    {
        $component = new \Dotclear\Helper\Html\Form\Set();
        $component->items([
        ]);

        $this
            ->string($component->render())
            ->match('//')
        ;
    }

    public function testWithItems()
    {
        $component = new \Dotclear\Helper\Html\Form\Set();
        $component->items([
            new \Dotclear\Helper\Html\Form\Text(null, '1st value'),
            new \Dotclear\Helper\Html\Form\Text(null, '2nd value'),
        ]);

        $this
            ->string($component->render())
            ->match('/1st value2nd value/')
        ;
    }

    public function testWithItemsWithSeparator()
    {
        $component = new \Dotclear\Helper\Html\Form\Set();
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
            ->match('/1st value---2nd value/')
        ;
    }

    public function testWithItemsWithFormat()
    {
        $component = new \Dotclear\Helper\Html\Form\Set();
        $component
            ->format('[%s]')
            ->items(
                [
                    new \Dotclear\Helper\Html\Form\Text(null, '1st value'),
                    new \Dotclear\Helper\Html\Form\Text(null, '2nd value'),
                ]
            );

        $this
            ->string($component->render())
            ->match('/\[1st value\]\[2nd value\]/')
        ;
    }

    public function testGetDefaultElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Set();

        $this
            ->string($component->getDefaultElement())
            ->isEqualTo('')
        ;
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Set();

        $this
            ->string($component->getType())
            ->isEqualTo('Dotclear\Helper\Html\Form\Set')
        ;
    }
}
