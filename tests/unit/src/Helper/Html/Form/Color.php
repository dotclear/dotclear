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

class Color extends atoum
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Color('my', 'value');

        $this
            ->string($component->render())
            ->match('/<input type="color".*?\/>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('value="value"')
            ->contains('size="7"')
            ->contains('maxlength="7"')
        ;
    }

    public function testWithoutValue()
    {
        $component = new \Dotclear\Helper\Html\Form\Color('my');

        $this
            ->string($component->render())
            ->match('/<input type="color".*?\/>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->notContains('value="value"')
            ->contains('size="7"')
            ->contains('maxlength="7"')
        ;
    }

    public function testWithoutNameOrId()
    {
        $component = new \Dotclear\Helper\Html\Form\Color();

        $this
            ->string($component->render())
            ->isEmpty()
        ;
    }

    public function testWithoutNameOrIdAndWithAValue()
    {
        $component = new \Dotclear\Helper\Html\Form\Color(null, 'value');

        $this
            ->string($component->render())
            ->isEmpty()
        ;
    }

    public function testSize()
    {
        $component = new \Dotclear\Helper\Html\Form\Color(['myname','myid'], 'value');
        $component->size(13);

        $this
            ->string($component->render())
            ->match('/<input type="color".*?\/>/')
            ->contains('name="myname"')
            ->contains('id="myid"')
            ->contains('value="value"')
            ->contains('size="13"')
            ->contains('maxlength="7"')
        ;
    }

    public function testMaxlength()
    {
        $component = new \Dotclear\Helper\Html\Form\Color(['myname','myid'], 'value');
        $component->maxlength(21);

        $this
            ->string($component->render())
            ->match('/<input type="color".*?\/>/')
            ->contains('name="myname"')
            ->contains('id="myid"')
            ->contains('value="value"')
            ->contains('size="7"')
            ->contains('maxlength="21"')
        ;
    }
}
