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

class Number extends atoum
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Number('my', 0, 99, 50);

        $this
            ->string($component->render())
            ->match('/<input type="number".*?\/>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('value="50"')
            ->contains('min="0"')
            ->contains('max="99"')
            ->contains('inputmode="numeric"')
        ;
    }

    public function testWithoutValue()
    {
        $component = new \Dotclear\Helper\Html\Form\Number('my');

        $this
            ->string($component->render())
            ->match('/<input type="number".*?\/>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->notContains('value=')
            ->notContains('min=')
            ->notContains('max=')
            ->contains('inputmode="numeric"')
        ;
    }

    public function testWithMin()
    {
        $component = new \Dotclear\Helper\Html\Form\Number('my', 0);

        $this
            ->string($component->render())
            ->match('/<input type="number".*?\/>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->notContains('value=')
            ->contains('min="0"')
            ->notContains('max=')
            ->contains('inputmode="numeric"')
        ;
    }

    public function testWithMax()
    {
        $component = new \Dotclear\Helper\Html\Form\Number('my', null, 99);

        $this
            ->string($component->render())
            ->match('/<input type="number".*?\/>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->notContains('value=')
            ->notContains('min=')
            ->contains('max="99"')
            ->contains('inputmode="numeric"')
        ;
    }

    public function testWithValue()
    {
        $component = new \Dotclear\Helper\Html\Form\Number('my', null, null, 50);

        $this
            ->string($component->render())
            ->match('/<input type="number".*?\/>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('value="50"')
            ->notContains('min=')
            ->notContains('max=')
            ->contains('inputmode="numeric"')
        ;
    }

    public function testWithoutNameOrId()
    {
        $component = new \Dotclear\Helper\Html\Form\Number();

        $this
            ->string($component->render())
            ->isEmpty()
        ;
    }

    public function testWithoutNameOrIdAndWithAValue()
    {
        $component = new \Dotclear\Helper\Html\Form\Number(null, null, null, 50);

        $this
            ->string($component->render())
            ->isEmpty()
        ;
    }
}
