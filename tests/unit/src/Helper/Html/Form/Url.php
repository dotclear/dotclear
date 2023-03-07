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

class Url extends atoum
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Url('my', 'value');

        $this
            ->string($component->render())
            ->match('/<input type="url".*?\/>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('inputmode="url"')
            ->contains('value="value"')
        ;
    }

    public function testWithoutValue()
    {
        $component = new \Dotclear\Helper\Html\Form\Url('my');

        $this
            ->string($component->render())
            ->match('/<input type="url".*?\/>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('inputmode="url"')
            ->notContains('value="value"')
        ;
    }

    public function testWithoutNameOrId()
    {
        $component = new \Dotclear\Helper\Html\Form\Url();

        $this
            ->string($component->render())
            ->isEmpty()
        ;
    }

    public function testWithoutNameOrIdAndWithAValue()
    {
        $component = new \Dotclear\Helper\Html\Form\Url(null, 'value');

        $this
            ->string($component->render())
            ->isEmpty()
        ;
    }
}
