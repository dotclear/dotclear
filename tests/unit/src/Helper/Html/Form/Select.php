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

class Select extends atoum
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Select('myid');

        $this
            ->string($component->render())
            ->match('/<select.*?>(?:.*?\n*)?<\/select>/')
            ->contains('name="myid"')
            ->contains('id="myid"')
        ;
    }

    public function testItemsText()
    {
        $component = new \Dotclear\Helper\Html\Form\Select('myid');
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
        $component = new \Dotclear\Helper\Html\Form\Select('myid');
        $component->items([
            new \Dotclear\Helper\Html\Form\Option('One', '1'),
        ]);

        $this
            ->string($component->render())
            ->contains('<option value="1">One</option>')
        ;
    }

    public function testItemsSelect()
    {
        $component = new \Dotclear\Helper\Html\Form\Select('myid');
        $component->items([
            (new \Dotclear\Helper\Html\Form\Optgroup('First'))->items([
                new \Dotclear\Helper\Html\Form\Option('One', '1'),
            ]),
        ]);

        $this
            ->string($component->render())
            ->contains('<optgroup label="First">')
            ->contains('<option value="1">One</option>')
            ->contains('</optgroup>' . "\n" . '</select>')
        ;
    }

    public function testItemsArray()
    {
        $component = new \Dotclear\Helper\Html\Form\Select('myid');
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
            ->contains('</optgroup>' . "\n" . '</select>')
        ;
    }

    public function testEmptyItems()
    {
        $component = new \Dotclear\Helper\Html\Form\Select('myid');
        $component->items([]);

        $this
            ->string($component->render())
            ->notContains('<option')
        ;
    }

    public function testWithoutId()
    {
        $component = new \Dotclear\Helper\Html\Form\Select();

        $this
            ->string($component->render())
            ->isEmpty()
        ;
    }

    public function testAttachLabel()
    {
        $component = new \Dotclear\Helper\Html\Form\Select('my');

        $label = new \Dotclear\Helper\Html\Form\Label('mylabel');
        $component->attachLabel($label);

        $this
            ->string($component->render())
            ->contains('<label>mylabel <select name="my" id="my">' . "\n" . '</select>' . "\n" . '</label>')
        ;
    }

    public function testAttachLabelOutside()
    {
        $component = new \Dotclear\Helper\Html\Form\Select('my');

        $label = new \Dotclear\Helper\Html\Form\Label('mylabel', \Dotclear\Helper\Html\Form\Label::OUTSIDE_LABEL_BEFORE);
        $component->attachLabel($label);

        $this
            ->string($component->render())
            ->contains('<label for="my">mylabel</label> <select name="my" id="my">' . "\n" . '</select>' . "\n")
        ;
    }

    public function testAttachLabelButWithoutRendering()
    {
        $component = new \Dotclear\Helper\Html\Form\Select('my', null, false);

        $label = new \Dotclear\Helper\Html\Form\Label('mylabel');
        $component->attachLabel($label);

        $this
            ->string($component->render())
            ->notContains('<label>')
        ;
    }

    public function testDetachLabel()
    {
        $component = new \Dotclear\Helper\Html\Form\Select('my');

        $label = new \Dotclear\Helper\Html\Form\Label('mylabel');
        $component->attachLabel($label);
        $component->detachLabel();

        $this
            ->variable($component->label())
            ->isNull()
        ;
    }

    public function testGetDefaultElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Select('myid');

        $this
            ->string($component->getDefaultElement())
            ->isEqualTo('select')
        ;
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Select('myid');

        $this
            ->string($component->getType())
            ->isEqualTo('Dotclear\Helper\Html\Form\Select')
        ;
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Select('myid');

        $this
            ->string($component->getElement())
            ->isEqualTo('select')
        ;
    }

    public function testGetElementWithOtherElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Select('myid', 'div');

        $this
            ->string($component->getElement())
            ->isEqualTo('div')
        ;
    }
}
