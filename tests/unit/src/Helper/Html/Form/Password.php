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

class Password extends atoum
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Password('my', 'value');

        $this
            ->string($component->render())
            ->match('/<input type="password".*?\/>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('value="value"')
            ->contains('autocorrect="off"')
            ->contains('spellcheck="false"')
            ->contains('autocapitalize="off"')
        ;
    }

    public function testWithoutValue()
    {
        $component = new \Dotclear\Helper\Html\Form\Password('my');

        $this
            ->string($component->render())
            ->match('/<input type="password".*?\/>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->notContains('value="value"')
        ;
    }

    public function testWithoutNameOrId()
    {
        $component = new \Dotclear\Helper\Html\Form\Password();

        $this
            ->string($component->render())
            ->isEmpty()
        ;
    }

    public function testWithoutNameOrIdAndWithAValue()
    {
        $component = new \Dotclear\Helper\Html\Form\Password(null, 'value');

        $this
            ->string($component->render())
            ->isEmpty()
        ;
    }
}
