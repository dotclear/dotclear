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

class Caption extends atoum
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Caption('My Caption');

        $this
            ->string($component->render())
            ->match('/<caption.*?>(?:.*?\n*)?<\/caption>/')
            ->contains('My Caption')
        ;
    }

    public function testWithText()
    {
        $component = new \Dotclear\Helper\Html\Form\Caption();
        $component->text('My Caption');

        $this
            ->string($component->render())
            ->match('/<caption.*?>(?:.*?\n*)?<\/caption>/')
            ->contains('My Caption')
        ;
    }

    public function testWithoutText()
    {
        $component = new \Dotclear\Helper\Html\Form\Caption();

        $this
            ->string($component->render())
            ->match('/<caption.*?><\/caption>/')
        ;
    }

    public function testWithId()
    {
        $component = new \Dotclear\Helper\Html\Form\Caption('My Caption', 'myid');

        $this
            ->string($component->render())
            ->match('/<caption.*?>(?:.*?\n*)?<\/caption>/')
            ->contains('name="myid"')
            ->contains('id="myid"')
        ;
    }

    public function testGetDefaultElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Caption('My Caption');

        $this
            ->string($component->getDefaultElement())
            ->isEqualTo('caption')
        ;
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Caption('My Caption');

        $this
            ->string($component->getType())
            ->isEqualTo('Dotclear\Helper\Html\Form\Caption')
        ;
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Caption('My Caption');

        $this
            ->string($component->getElement())
            ->isEqualTo('caption')
        ;
    }

    public function testGetElementWithOtherElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Caption('My Caption', 'myid', 'span');

        $this
            ->string($component->getElement())
            ->isEqualTo('span')
        ;
    }
}
