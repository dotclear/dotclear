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

class Textarea extends atoum
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Textarea('myid');

        $this
            ->string($component->render())
            ->match('/<textarea.*?>(?:.*?\n*)?<\/textarea>/')
            ->contains('name="myid"')
            ->contains('id="myid"')
        ;
    }

    public function testWithValue()
    {
        $component = new \Dotclear\Helper\Html\Form\Textarea('myid', 'CONTENT');

        $this
            ->string($component->render())
            ->contains('>CONTENT</textarea>')
        ;
    }

    public function testWithoutId()
    {
        $component = new \Dotclear\Helper\Html\Form\Textarea();

        $this
            ->string($component->render())
            ->isEmpty()
        ;
    }

    public function testAttachLabel()
    {
        $component = new \Dotclear\Helper\Html\Form\Textarea('my');

        $label = new \Dotclear\Helper\Html\Form\Label('mylabel');
        $component->attachLabel($label);

        $this
            ->string($component->render())
            ->contains('<label>mylabel <textarea name="my" id="my"></textarea>' . "\n" . '</label>')
        ;
    }

    public function testAttachLabelOutside()
    {
        $component = new \Dotclear\Helper\Html\Form\Textarea('my');

        $label = new \Dotclear\Helper\Html\Form\Label('mylabel', \Dotclear\Helper\Html\Form\Label::OUTSIDE_LABEL_BEFORE);
        $component->attachLabel($label);

        $this
            ->string($component->render())
            ->contains('<label for="my">mylabel</label> <textarea name="my" id="my"></textarea>' . "\n")
        ;
    }

    public function testDetachLabel()
    {
        $component = new \Dotclear\Helper\Html\Form\Textarea('my');

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
        $component = new \Dotclear\Helper\Html\Form\Textarea('myid');

        $this
            ->string($component->getDefaultElement())
            ->isEqualTo('textarea')
        ;
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Textarea('myid');

        $this
            ->string($component->getType())
            ->isEqualTo('Dotclear\Helper\Html\Form\Textarea')
        ;
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Textarea('myid');

        $this
            ->string($component->getElement())
            ->isEqualTo('textarea')
        ;
    }
}
