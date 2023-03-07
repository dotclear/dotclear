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

class Optgroup extends atoum
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Optgroup('My Group');

        $this
            ->string($component->render())
            ->match('/<optgroup.*?>(?:.*?\n*)?<\/optgroup>/')
        ;
    }

    public function testItemsText()
    {
        $component = new \Dotclear\Helper\Html\Form\Optgroup('My Group');
        $component->items([
            'one' => 1,
            'two' => '0',
            'three',
        ]);

        $this
            ->string($component->render())
            ->contains('<option value="1">one</option>')
            ->contains('<option value="0">two</option>')
            ->contains('<option value="three">0</option>')
        ;
    }

    public function testItemsOption()
    {
        $component = new \Dotclear\Helper\Html\Form\Optgroup('My Group');
        $component->items([
            new \Dotclear\Helper\Html\Form\Option('One', '1'),
        ]);

        $this
            ->string($component->render())
            ->contains('<option value="1">One</option>')
        ;
    }

    public function testItemsOptgroup()
    {
        $component = new \Dotclear\Helper\Html\Form\Optgroup('My Group');
        $component->items([
            (new \Dotclear\Helper\Html\Form\Optgroup('First'))->items([
                new \Dotclear\Helper\Html\Form\Option('One', '1'),
            ]),
        ]);

        $this
            ->string($component->render())
            ->contains('<optgroup label="First">')
            ->contains('<option value="1">One</option>')
            ->contains('</optgroup>' . "\n" . '</optgroup>')
        ;
    }

    public function testItemsArray()
    {
        $component = new \Dotclear\Helper\Html\Form\Optgroup('My Group');
        $component->items([
            'First' => [
                'one' => 1,
                'two' => '0',
                'three',
            ],
        ]);

        $this
            ->string($component->render())
            ->contains('<optgroup label="First">')
            ->contains('<option value="1">one</option>')
            ->contains('<option value="0">two</option>')
            ->contains('<option value="three">0</option>')
            ->contains('</optgroup>' . "\n" . '</optgroup>')
        ;
    }

    public function testEmptyItems()
    {
        $component = new \Dotclear\Helper\Html\Form\Optgroup('My Group');
        $component->items([]);

        $this
            ->string($component->render())
            ->notContains('<option')
        ;
    }

    public function testGetDefaultElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Optgroup('My Group');

        $this
            ->string($component->getDefaultElement())
            ->isEqualTo('optgroup')
        ;
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Optgroup('My Group');

        $this
            ->string($component->getType())
            ->isEqualTo('Dotclear\Helper\Html\Form\Optgroup')
        ;
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Optgroup('My Group');

        $this
            ->string($component->getElement())
            ->isEqualTo('optgroup')
        ;
    }

    public function testGetElementWithOtherElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Optgroup('My Group', 'span');

        $this
            ->string($component->getElement())
            ->isEqualTo('span')
        ;
    }
}
