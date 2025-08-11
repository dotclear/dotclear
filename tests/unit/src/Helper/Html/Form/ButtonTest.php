<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class ButtonTest extends TestCase
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Button('my', 'value');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<input type="button".*?>/',
            $rendered
        );
        $this->assertStringContainsString(
            'name="my"',
            $rendered
        );
        $this->assertStringContainsString(
            'id="my"',
            $rendered
        );
        $this->assertStringContainsString(
            'value="value"',
            $rendered
        );
    }

    public function testWithoutValue()
    {
        $component = new \Dotclear\Helper\Html\Form\Button('my');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<input type="button".*?>/',
            $rendered
        );
        $this->assertStringContainsString(
            'name="my"',
            $rendered
        );
        $this->assertStringContainsString(
            'id="my"',
            $rendered
        );
        $this->assertStringNotContainsString(
            'value=',
            $rendered
        );
    }

    public function testWithoutNameOrId()
    {
        $component = new \Dotclear\Helper\Html\Form\Button();
        $rendered  = $component->render();

        $this->assertEquals(
            '',
            $rendered
        );
    }

    public function testWithoutNameOrIdAndWithAValue()
    {
        $component = new \Dotclear\Helper\Html\Form\Button(null, 'value');
        $rendered  = $component->render();

        $this->assertEquals(
            '',
            $rendered
        );
    }
}
