<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Html\Form;

use PHPUnit\Framework\TestCase;

class CaptureTest extends TestCase
{
    public function echoing(string $buffer = 'Buffer'): void
    {
        echo $buffer;
    }

    private function error(): void
    {
        throw new \Exception('Error Processing Request');
    }

    private function echoInNestedBuffers(): void
    {
        echo 'Outer';
        ob_start();
        echo 'Middle';
        ob_start();
        echo 'Inner';
    }

    private function throwWithNestedBuffer(): void
    {
        echo 'Before exception';
        ob_start();
        echo 'Discarded';

        throw new \RuntimeException('Error Processing Request');
    }

    public function test(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Capture($this->echoing(...));
        $rendered  = $component->render();

        $this->assertEquals(
            'Buffer',
            $rendered
        );
    }

    public function testWithParam(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Capture($this->echoing(...), ['Output']);
        $rendered  = $component->render();

        $this->assertEquals(
            'Output',
            $rendered
        );
    }

    public function testGetDefaultElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Capture($this->echoing(...));

        $this->assertEquals(
            '',
            $component->getDefaultElement()
        );
    }

    public function testGetType(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Capture($this->echoing(...));

        $this->assertEquals(
            'Dotclear\Helper\Html\Form\Capture',
            $component->getType()
        );
        $this->assertEquals(
            \Dotclear\Helper\Html\Form\Capture::class,
            $component->getType()
        );
    }

    public function testException(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Capture($this->error(...));
        $rendered  = $component->render();

        $this->assertEquals(
            '',
            $rendered
        );
    }

    public function testNestedOutputBuffers(): void
    {
        $initialLevel = ob_get_level();
        ob_start();

        try {
            echo 'Caller';
            $callerLevel = ob_get_level();
            $component   = new \Dotclear\Helper\Html\Form\Capture($this->echoInNestedBuffers(...));

            $this->assertSame('OuterMiddleInner', $component->render());
            $this->assertSame($callerLevel, ob_get_level());
            $this->assertSame('Caller', ob_get_contents());
        } finally {
            ob_end_clean();
        }

        $this->assertSame($initialLevel, ob_get_level());
    }

    public function testExceptionCleansNestedOutputBuffers(): void
    {
        $initialLevel = ob_get_level();
        ob_start();

        try {
            echo 'Caller';
            $callerLevel = ob_get_level();
            $component   = new \Dotclear\Helper\Html\Form\Capture($this->throwWithNestedBuffer(...));

            $this->assertSame('', $component->render());
            $this->assertSame($callerLevel, ob_get_level());
            $this->assertSame('Caller', ob_get_contents());
        } finally {
            ob_end_clean();
        }

        $this->assertSame($initialLevel, ob_get_level());
    }

    public function testErrorRestoresOutputBufferLevel(): void
    {
        $initialLevel = ob_get_level();
        ob_start();
        $callerLevel = ob_get_level();

        try {
            echo 'Caller';

            $this->expectException(\Error::class);
            new \Dotclear\Helper\Html\Form\Capture(static function (): void {
                echo 'Before error';
                ob_start();
                echo 'Discarded';

                throw new \Error('Error Processing Request');
            });
        } finally {
            $restoredLevel = ob_get_level();
            $callerContent = ob_get_contents();
            ob_end_clean();

            $this->assertSame($callerLevel, $restoredLevel);
            $this->assertSame('Caller', $callerContent);
            $this->assertSame($initialLevel, ob_get_level());
        }
    }
}
