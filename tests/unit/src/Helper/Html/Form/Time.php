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

class Time extends atoum
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Time('my', 'value');

        $this
            ->string($component->render())
            ->match('/<input type="time".*?\/>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('value="value"')
            ->contains('size="5"')
            ->contains('maxlength="5"')
            ->contains('pattern="[0-9]{2}:[0-9]{2}"')
            ->contains('placeholder="14:45"')
        ;
    }

    public function testWithoutValue()
    {
        $component = new \Dotclear\Helper\Html\Form\Time('my');

        $this
            ->string($component->render())
            ->match('/<input type="time".*?\/>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->notContains('value="value"')
            ->contains('size="5"')
            ->contains('maxlength="5"')
            ->contains('pattern="[0-9]{2}:[0-9]{2}"')
            ->contains('placeholder="14:45"')
        ;
    }

    public function testWithoutNameOrId()
    {
        $component = new \Dotclear\Helper\Html\Form\Time();

        $this
            ->string($component->render())
            ->isEmpty()
        ;
    }

    public function testWithoutNameOrIdAndWithAValue()
    {
        $component = new \Dotclear\Helper\Html\Form\Time(null, 'value');

        $this
            ->string($component->render())
            ->isEmpty()
        ;
    }

    public function testSize()
    {
        $component = new \Dotclear\Helper\Html\Form\Time(['myname','myid'], 'value');
        $component->size(13);

        $this
            ->string($component->render())
            ->match('/<input type="time".*?\/>/')
            ->contains('name="myname"')
            ->contains('id="myid"')
            ->contains('value="value"')
            ->contains('size="13"')
            ->contains('maxlength="5"')
            ->contains('pattern="[0-9]{2}:[0-9]{2}"')
            ->contains('placeholder="14:45"')
        ;
    }

    public function testMaxlength()
    {
        $component = new \Dotclear\Helper\Html\Form\Time(['myname','myid'], 'value');
        $component->maxlength(21);

        $this
            ->string($component->render())
            ->match('/<input type="time".*?\/>/')
            ->contains('name="myname"')
            ->contains('id="myid"')
            ->contains('value="value"')
            ->contains('size="5"')
            ->contains('maxlength="21"')
            ->contains('pattern="[0-9]{2}:[0-9]{2}"')
            ->contains('placeholder="14:45"')
        ;
    }

    public function testPattern()
    {
        $component = new \Dotclear\Helper\Html\Form\Time(['myname','myid'], 'value');
        $component->pattern('[0-9]{2}');

        $this
            ->string($component->render())
            ->match('/<input type="time".*?\/>/')
            ->contains('name="myname"')
            ->contains('id="myid"')
            ->contains('value="value"')
            ->contains('size="5"')
            ->contains('maxlength="5"')
            ->contains('pattern="[0-9]{2}"')
            ->contains('placeholder="14:45"')
        ;
    }

    public function testPlaceholder()
    {
        $component = new \Dotclear\Helper\Html\Form\Time(['myname','myid'], 'value');
        $component->placeholder('12:15');

        $this
            ->string($component->render())
            ->match('/<input type="time".*?\/>/')
            ->contains('name="myname"')
            ->contains('id="myid"')
            ->contains('value="value"')
            ->contains('size="5"')
            ->contains('maxlength="5"')
            ->contains('pattern="[0-9]{2}:[0-9]{2}"')
            ->contains('placeholder="12:15"')
        ;
    }
}
