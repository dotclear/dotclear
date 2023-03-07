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

class Text extends atoum
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Text(null, 'TEXT');

        $this
            ->string($component->render())
            ->isEqualTo('TEXT')
        ;
    }

    public function testWithACommonAttribute()
    {
        $component = new \Dotclear\Helper\Html\Form\Text(null, 'TEXT');
        $component->setIdentifier('myid');

        $this
            ->string($component->render())
            ->match('/<span.*?>(?:.*?\n*)?<\/span>/')
            ->contains('name="myid"')
            ->contains('id="myid"')
        ;
    }

    public function testGetDefaultElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Text();

        $this
            ->string($component->getDefaultElement())
            ->isEqualTo('')
        ;
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Text();

        $this
            ->string($component->getType())
            ->isEqualTo('Dotclear\Helper\Html\Form\Text')
        ;
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Text();

        $this
            ->string($component->getElement())
            ->isEqualTo('')
        ;
    }

    public function testGetElementWithOtherElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Text('span');

        $this
            ->string($component->getElement())
            ->isEqualTo('span')
        ;
    }
}
