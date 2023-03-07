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

class Hidden extends atoum
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Hidden('my', 'value');

        $this
            ->string($component->render())
            ->match('/<input type="hidden".*?\/>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('value="value"')
        ;
    }

    public function testWithoutValue()
    {
        $component = new \Dotclear\Helper\Html\Form\Hidden('my');

        $this
            ->string($component->render())
            ->match('/<input type="hidden".*?\/>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->notContains('value="value"')
        ;
    }

    public function testWithoutNameOrId()
    {
        $component = new \Dotclear\Helper\Html\Form\Hidden();

        $this
            ->string($component->render())
            ->isEmpty()
        ;
    }

    public function testWithoutNameOrIdAndWithAValue()
    {
        $component = new \Dotclear\Helper\Html\Form\Hidden(null, 'value');

        $this
            ->string($component->render())
            ->isEmpty()
        ;
    }
}
