<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class CaptureTest extends TestCase
{
    public function echoing(string $buffer = 'Buffer')
    {
        echo $buffer;
    }

    private function error()
    {
        throw new Exception('Error Processing Request');
    }

    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Capture($this->echoing(...));
        $rendered  = $component->render();

        $this->assertEquals(
            'Buffer',
            $rendered
        );
    }

    public function testWithParam()
    {
        $component = new \Dotclear\Helper\Html\Form\Capture($this->echoing(...), ['Output']);
        $rendered  = $component->render();

        $this->assertEquals(
            'Output',
            $rendered
        );
    }

    public function testGetDefaultElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Capture($this->echoing(...));

        $this->assertEquals(
            '',
            $component->getDefaultElement()
        );
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Capture($this->echoing(...));

        $this->assertEquals(
            'Dotclear\Helper\Html\Form\Capture',
            $component->getType()
        );
        $this->assertEquals(
            Dotclear\Helper\Html\Form\Capture::class,
            $component->getType()
        );
    }

    public function testException()
    {
        $component = new \Dotclear\Helper\Html\Form\Capture($this->error(...));
        $rendered  = $component->render();

        $this->assertEquals(
            '',
            $rendered
        );
    }
}
