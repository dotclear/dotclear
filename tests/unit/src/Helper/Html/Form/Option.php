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

class Option extends atoum
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Option('My option', 'value');

        $this
            ->string($component->render())
            ->match('/<option.*?>(?:.*?\n*)?<\/option>/')
            ->contains('value="value"')
            ->contains('>My option</option>')
        ;
    }

    public function testWithEmptyText()
    {
        $component = new \Dotclear\Helper\Html\Form\Option('', 'value');

        $this
            ->string($component->render())
            ->match('/<option.*?>(?:.*?\n*)?<\/option>/')
            ->contains('value="value"')
            ->contains('></option>')
        ;
    }

    public function testWithSelected()
    {
        $component = new \Dotclear\Helper\Html\Form\Option('text', 'value');
        $component->selected(true);

        $this
            ->string($component->render())
            ->match('/<option.*?>(?:.*?\n*)?<\/option>/')
            ->contains('value="value"')
            ->contains('selected')
            ->contains('>text</option>')
        ;
    }

    public function testWithNotSelected()
    {
        $component = new \Dotclear\Helper\Html\Form\Option('text', 'value');
        $component->selected(false);

        $this
            ->string($component->render())
            ->match('/<option.*?>(?:.*?\n*)?<\/option>/')
            ->contains('value="value"')
            ->notcontains('selected')
            ->contains('>text</option>')
        ;
    }

    public function testGetDefaultElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Option('My option', 'value');

        $this
            ->string($component->getDefaultElement())
            ->isEqualTo('option')
        ;
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Option('My option', 'value');

        $this
            ->string($component->getType())
            ->isEqualTo('Dotclear\Helper\Html\Form\Option')
        ;
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Option('My option', 'value');

        $this
            ->string($component->getElement())
            ->isEqualTo('option')
        ;
    }

    public function testGetElementWithOtherElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Option('My option', 'value', 'slot');

        $this
            ->string($component->getElement())
            ->isEqualTo('slot')
        ;
    }
}
