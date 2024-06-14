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

class Capture extends atoum
{
    public function echoing(string $buffer = 'Buffer')
    {
        echo $buffer;
    }

    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Capture($this->echoing(...));

        $this
            ->string($component->render())
            ->isEqualTo('Buffer')
        ;
    }

    public function testWithParam()
    {
        $component = new \Dotclear\Helper\Html\Form\Capture($this->echoing(...), ['Output']);

        $this
            ->string($component->render())
            ->isEqualTo('Output')
        ;
    }

    public function testGetDefaultElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Capture($this->echoing(...));

        $this
            ->string($component->getDefaultElement())
            ->isEqualTo('')
        ;
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Capture($this->echoing(...));

        $this
            ->string($component->getType())
            ->isEqualTo('Dotclear\Helper\Html\Form\Capture')
        ;
    }
}
