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

class Fieldset extends atoum
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Fieldset('my');

        $this
            ->string($component->render())
            ->match('/<fieldset.*?>(?:.*?\n*)?<\/fieldset>/')
            ->contains('name="my"')
            ->contains('id="my"')
        ;
    }

    public function testWithElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Fieldset('my', 'div');

        $this
            ->string($component->render())
            ->match('/<div.*?>(?:.*?\n*)?<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
        ;
    }

    public function testGetDefaultElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Fieldset('my', 'slot');

        $this
            ->string($component->getDefaultElement())
            ->isEqualTo('fieldset')
        ;
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Fieldset('my');

        $this
            ->string($component->getType())
            ->isEqualTo('Dotclear\Helper\Html\Form\Fieldset')
        ;
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Fieldset('my');

        $this
            ->string($component->getElement())
            ->isEqualTo('fieldset')
        ;
    }

    public function testGetElementWithOtherElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Fieldset('my', 'div');

        $this
            ->string($component->getElement())
            ->isEqualTo('div')
        ;
    }

    public function testAttachLegend()
    {
        $component = new \Dotclear\Helper\Html\Form\Fieldset();

        $legend = new \Dotclear\Helper\Html\Form\Legend('mylabel');
        $component->attachLegend($legend);

        $this
            ->string($component->render())
            ->contains($legend->render())
        ;
    }

    public function testAttachNullLegend()
    {
        $component = new \Dotclear\Helper\Html\Form\Fieldset();

        $legend = new \Dotclear\Helper\Html\Form\Legend('mylabel');
        $component->attachLegend($legend);
        $component->attachLegend(null);

        $this
            ->string($component->render())
            ->notContains($legend->render())
        ;
    }

    public function testDetachLegend()
    {
        $component = new \Dotclear\Helper\Html\Form\Fieldset();

        $legend = new \Dotclear\Helper\Html\Form\Legend('mylabel');
        $component->attachLegend($legend);
        $component->detachLegend();

        $this
            ->string($component->render())
            ->notContains($legend->render())
        ;
    }

    public function testFields()
    {
        $component = new \Dotclear\Helper\Html\Form\Fieldset();

        $field = new \Dotclear\Helper\Html\Form\Input(['myinput']);
        $component->fields([
            $field,
        ]);

        $this
            ->string($component->render())
            ->contains($field->render())
        ;
    }

    public function testFieldsIncludingLegend()
    {
        $component = new \Dotclear\Helper\Html\Form\Fieldset();

        $legend = new \Dotclear\Helper\Html\Form\Legend('mylabel');
        $field  = new \Dotclear\Helper\Html\Form\Input(['myinput']);

        $component->fields([
            $legend,
            $field,
        ]);

        $this
            ->string($component->render())
            ->contains($legend->render())
            ->contains($field->render())
        ;
    }

    public function testFieldsIncludingAttachedLegend()
    {
        $component = new \Dotclear\Helper\Html\Form\Fieldset();

        $legend = new \Dotclear\Helper\Html\Form\Legend('mylabel');
        $component->attachLegend($legend);  // Attached legend will come first before fields

        $field = new \Dotclear\Helper\Html\Form\Input(['myinput']);

        $component->fields([
            $field,
            $legend,
        ]);

        $this
            ->string($component->render())
            ->contains($legend->render())
            ->contains($field->render())
            ->contains($legend->render() . $field->render())
            ->notContains($field->render() . $legend->render())
        ;
    }

    public function testWithoutNameOrId()
    {
        $component = new \Dotclear\Helper\Html\Form\Fieldset();

        $this
            ->string($component->render())
            ->match('/<fieldset.*?>(?:.*?\n*)?<\/fieldset>/')
        ;
    }

    public function testWithoutNameOrIdAndWithAnElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Fieldset(null, 'div');

        $this
            ->string($component->render())
            ->match('/<div.*?>(?:.*?\n*)?<\/div>/')
        ;
    }
}
