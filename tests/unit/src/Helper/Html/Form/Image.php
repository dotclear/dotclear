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

class Image extends atoum
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Image('img.jpg', 'my');

        $this
            ->string($component->render())
            ->match('/<input type="image" .*?>/')
            ->contains('name="my"')
            ->contains('id="my"')
        ;
    }

    public function testGetDefaultElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Image('img.jpg', 'my');

        $this
            ->string($component->getDefaultElement())
            ->isEqualTo('input')
        ;
    }

    public function testWithoutNameOrId()
    {
        $component = new \Dotclear\Helper\Html\Form\Image('img.jpg');

        $this
            ->string($component->render())
            ->isEqualTo('')
        ;
    }

    public function testWithoutNameOrIdAndWithAAlt()
    {
        $component = new \Dotclear\Helper\Html\Form\Image('img.jpg', 'my');
        $component->alt('textual alternative');

        $this
            ->string($component->render())
            ->isEqualTo('<input type="image" name="my" id="my" src="img.jpg" alt="textual alternative">')
        ;
    }
}
