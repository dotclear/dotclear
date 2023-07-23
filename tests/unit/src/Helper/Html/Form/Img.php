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

class Img extends atoum
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Img('img.jpg', 'my');

        $this
            ->string($component->render())
            ->match('/<img .*?\/>/')
            ->contains('name="my"')
            ->contains('id="my"')
        ;
    }

    public function testGetDefaultElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Img('img.jpg');

        $this
            ->string($component->getDefaultElement())
            ->isEqualTo('img')
        ;
    }

    public function testWithoutNameOrId()
    {
        $component = new \Dotclear\Helper\Html\Form\Img('img.jpg');

        $this
            ->string($component->render())
            ->isEqualTo('<img src="img.jpg"/>')
        ;
    }

    public function testWithoutNameOrIdAndWithAAlt()
    {
        $component = new \Dotclear\Helper\Html\Form\Img('img.jpg');
        $component->alt('textual alternative');

        $this
            ->string($component->render())
            ->isEqualTo('<img src="img.jpg" alt="textual alternative"/>')
        ;
    }
}
