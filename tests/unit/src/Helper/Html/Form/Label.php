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

class Label extends atoum
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Label('My Label');

        $this
            ->string($component->render())
            ->match('/<label.*?>(?:.*?\n*)?<\/label>/')
            ->contains('>My Label')
        ;
    }

    public function testInsideTextBefore()
    {
        $component = new \Dotclear\Helper\Html\Form\Label('My Label', \Dotclear\Helper\Html\Form\Label::INSIDE_TEXT_BEFORE, 'myid');

        $this
            ->string($component->render('<slot id="myid"></slot>'))
            ->contains('<label>My Label <slot id="myid"></slot></label>')
        ;
    }

    public function testInsideTextAfter()
    {
        $component = new \Dotclear\Helper\Html\Form\Label('My Label', \Dotclear\Helper\Html\Form\Label::INSIDE_TEXT_AFTER, 'myid');

        $this
            ->string($component->render('<slot id="myid"></slot>'))
            ->contains('<label><slot id="myid"></slot> My Label</label>')
        ;
    }

    public function testOutsideLabelBefore()
    {
        $component = new \Dotclear\Helper\Html\Form\Label('My Label', \Dotclear\Helper\Html\Form\Label::OUTSIDE_LABEL_BEFORE, 'myid');

        $this
            ->string($component->render('<slot id="myid"></slot>'))
            ->contains('<label for="myid">My Label</label> <slot id="myid"></slot>')
        ;
    }

    public function testOutsideLabelAfter()
    {
        $component = new \Dotclear\Helper\Html\Form\Label('My Label', \Dotclear\Helper\Html\Form\Label::OUTSIDE_LABEL_AFTER, 'myid');

        $this
            ->string($component->render('<slot id="myid"></slot>'))
            ->contains('<slot id="myid"></slot> <label for="myid">My Label</label>')
        ;
    }

    public function testOutsideLabelBeforeWithoutId()
    {
        $component = new \Dotclear\Helper\Html\Form\Label('My Label', \Dotclear\Helper\Html\Form\Label::OUTSIDE_LABEL_BEFORE);

        $this
            ->string($component->render('<slot id="myid"></slot>'))
            ->contains('<label>My Label</label> <slot id="myid"></slot>')
        ;
    }

    public function testOutsideLabelAfterWithoutId()
    {
        $component = new \Dotclear\Helper\Html\Form\Label('My Label', \Dotclear\Helper\Html\Form\Label::OUTSIDE_LABEL_AFTER);

        $this
            ->string($component->render('<slot id="myid"></slot>'))
            ->contains('<slot id="myid"></slot> <label>My Label</label>')
        ;
    }

    public function testFalsyPosition()
    {
        $component = new \Dotclear\Helper\Html\Form\Label('My Label', 99, 'myid');

        $this
            ->string($component->render('<slot id="myid"></slot>'))
            ->contains('<label>My Label <slot id="myid"></slot></label>')
        ;
    }

    public function testGetDefaultElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Label('My Label');

        $this
            ->string($component->getDefaultElement())
            ->isEqualTo('label')
        ;
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Label('My Label');

        $this
            ->string($component->getType())
            ->isEqualTo('Dotclear\Helper\Html\Form\Label')
        ;
    }

    public function testGetPosition()
    {
        $component = new \Dotclear\Helper\Html\Form\Label('My Label');

        $this
            ->integer($component->getPosition())
            ->isEqualTo(\Dotclear\Helper\Html\Form\Label::INSIDE_TEXT_BEFORE)
        ;
    }

    public function testSetPosition()
    {
        $component = new \Dotclear\Helper\Html\Form\Label('My Label');
        $component->setPosition(\Dotclear\Helper\Html\Form\Label::INSIDE_TEXT_BEFORE);

        $this
            ->integer($component->getPosition())
            ->isEqualTo(\Dotclear\Helper\Html\Form\Label::INSIDE_TEXT_BEFORE)
        ;
    }

    public function testSetFalsyPosition()
    {
        $component = new \Dotclear\Helper\Html\Form\Label('My Label');
        $component->setPosition(99);

        $this
            ->integer($component->getPosition())
            ->isEqualTo(\Dotclear\Helper\Html\Form\Label::INSIDE_TEXT_BEFORE)
        ;
    }
}
