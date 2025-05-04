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

class Timestamp extends atoum
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Timestamp('My timestamp');

        $this
            ->string($component->render())
            ->match('/<time.*?>(?:.*?\n*)?<\/time>/')
            ->contains('My timestamp')
        ;
    }

    public function testWithText()
    {
        $component = new \Dotclear\Helper\Html\Form\Timestamp();
        $component->text('My timestamp');

        $this
            ->string($component->render())
            ->match('/<time.*?>(?:.*?\n*)?<\/time>/')
            ->contains('My timestamp')
        ;
    }

    public function testWithDatetime()
    {
        $component = new \Dotclear\Helper\Html\Form\Timestamp();
        $component->datetime('My-Datetime');

        $this
            ->string($component->render())
            ->match('/<time.*?>(?:.*?\n*)?<\/time>/')
            ->contains('datetime="My-Datetime"')
        ;
    }

    public function testWithoutText()
    {
        $component = new \Dotclear\Helper\Html\Form\Timestamp();

        $this
            ->string($component->render())
            ->match('/<time.*?><\/time>/')
        ;
    }

    public function testGetDefaultElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Timestamp('My timestamp');

        $this
            ->string($component->getDefaultElement())
            ->isEqualTo('time')
        ;
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Timestamp('My timestamp');

        $this
            ->string($component->getType())
            ->isEqualTo('Dotclear\Helper\Html\Form\Timestamp')
        ;
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Timestamp('My timestamp');

        $this
            ->string($component->getElement())
            ->isEqualTo('time')
        ;
    }

    public function testGetElementWithOtherElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Timestamp('My timestamp', 'span');

        $this
            ->string($component->getElement())
            ->isEqualTo('span')
        ;
    }
}
