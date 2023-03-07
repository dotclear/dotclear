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

class Input extends atoum
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Input('my', 'hidden');

        $this
            ->string($component->render())
            ->match('/<input type="hidden".*?\/>/')
            ->contains('name="my"')
            ->contains('id="my"')
        ;
    }

    public function testWithoutType()
    {
        $component = new \Dotclear\Helper\Html\Form\Input('my');

        $this
            ->string($component->render())
            ->match('/<input type="text".*?\/>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('type="text"')
        ;
    }

    public function testGetDefaultElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Input('my');

        $this
            ->string($component->getDefaultElement())
            ->isEqualTo('input')
        ;
    }

    public function testAttachLabel()
    {
        $component = new \Dotclear\Helper\Html\Form\Input('my');

        $label = new \Dotclear\Helper\Html\Form\Label('mylabel');
        $component->attachLabel($label);

        $this
            ->string($component->render())
            ->contains('<label>mylabel <input type="text" name="my" id="my"/></label>')
        ;
    }

    public function testAttachLabelOutside()
    {
        $component = new \Dotclear\Helper\Html\Form\Input('my');

        $label = new \Dotclear\Helper\Html\Form\Label('mylabel', \Dotclear\Helper\Html\Form\Label::OUTSIDE_LABEL_BEFORE);
        $component->attachLabel($label);

        $this
            ->string($component->render())
            ->contains('<label for="my">mylabel</label> <input type="text" name="my" id="my"/>')
        ;
    }

    public function testAttachLabelButWithoutRendering()
    {
        $component = new \Dotclear\Helper\Html\Form\Input('my', 'test', false);

        $label = new \Dotclear\Helper\Html\Form\Label('mylabel');
        $component->attachLabel($label);

        $this
            ->string($component->render())
            ->notContains('<label>')
        ;
    }

    public function testDetachLabel()
    {
        $component = new \Dotclear\Helper\Html\Form\Input('my');

        $label = new \Dotclear\Helper\Html\Form\Label('mylabel');
        $component->attachLabel($label);
        $component->detachLabel();

        $this
            ->variable($component->label())
            ->isNull()
        ;
    }

    public function testWithoutNameOrId()
    {
        $component = new \Dotclear\Helper\Html\Form\Input();

        $this
            ->string($component->render())
            ->isEmpty()
        ;
    }

    public function testWithoutNameOrIdAndWithAValue()
    {
        $component = new \Dotclear\Helper\Html\Form\Input(null, 'value');

        $this
            ->string($component->render())
            ->isEmpty()
        ;
    }
}
