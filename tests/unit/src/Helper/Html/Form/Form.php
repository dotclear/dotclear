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

class Form extends atoum
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Form('my');

        $this
            ->string($component->render())
            ->match('/<form.*?>(?:.*?\n*)?<\/form>/')
            ->contains('name="my"')
            ->contains('id="my"')
        ;
    }

    public function testWithElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Form('my', 'div');

        $this
            ->string($component->render())
            ->match('/<div.*?>(?:.*?\n*)?<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
        ;
    }

    public function testGetDefaultElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Form('my', 'slot');

        $this
            ->string($component->getDefaultElement())
            ->isEqualTo('form')
        ;
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Form('my');

        $this
            ->string($component->getType())
            ->isEqualTo('Dotclear\Helper\Html\Form\Form')
        ;
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Form('my');

        $this
            ->string($component->getElement())
            ->isEqualTo('form')
        ;
    }

    public function testGetElementWithOtherElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Form('my', 'div');

        $this
            ->string($component->getElement())
            ->isEqualTo('div')
        ;
    }

    public function testFields()
    {
        $component = new \Dotclear\Helper\Html\Form\Form('my');

        $field = new \Dotclear\Helper\Html\Form\Input(['myinput']);
        $component->fields([
            $field,
        ]);

        $this
            ->string($component->render())
            ->contains($field->render())
        ;
    }

    public function testWithoutNameOrId()
    {
        $component = new \Dotclear\Helper\Html\Form\Form();

        $this
            ->string($component->render())
            ->isEmpty()
        ;
    }

    public function testWithoutNameOrIdAndWithAnElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Form(null, 'div');

        $this
            ->string($component->render())
            ->isEmpty()
        ;
    }
}
