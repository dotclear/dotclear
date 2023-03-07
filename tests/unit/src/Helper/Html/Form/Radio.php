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

class Radio extends atoum
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Radio('my', true);

        $this
            ->string($component->render())
            ->match('/<input type="radio".*?\/>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('checked')
        ;
    }

    public function testWithoutCheckedValue()
    {
        $component = new \Dotclear\Helper\Html\Form\Radio('my');

        $this
            ->string($component->render())
            ->match('/<input type="radio".*?\/>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->notContains('checked')
        ;
    }

    public function testWithoutNameOrId()
    {
        $component = new \Dotclear\Helper\Html\Form\Radio();

        $this
            ->string($component->render())
            ->isEmpty()
        ;
    }

    public function testWithFalsyCheckedValue()
    {
        $component = new \Dotclear\Helper\Html\Form\Radio('my', false);

        $this
            ->string($component->render())
            ->match('/<input type="radio".*?\/>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->notContains('checked')
        ;
    }

    public function testWithoutNameOrIdAndWithCheckedValue()
    {
        $component = new \Dotclear\Helper\Html\Form\Radio(null, true);

        $this
            ->string($component->render())
            ->isEmpty()
        ;
    }
}
