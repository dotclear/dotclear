<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class EmailTest extends TestCase
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Email('my', 'value');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<input type="email".*?>/',
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
            'inputmode="email"',
            $rendered
        );
        $this->assertStringContainsString(
            'value="value"',
            $rendered
        );
    }

    public function testWithoutValue()
    {
        $component = new \Dotclear\Helper\Html\Form\Email('my');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<input type="email".*?>/',
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
            'inputmode="email"',
            $rendered
        );
        $this->assertStringNotContainsString(
            'value=',
            $rendered
        );
    }

    public function testWithoutNameOrId()
    {
        $component = new \Dotclear\Helper\Html\Form\Email();
        $rendered  = $component->render();

        $this->assertEquals(
            '',
            $rendered
        );
    }

    public function testWithoutNameOrIdAndWithAValue()
    {
        $component = new \Dotclear\Helper\Html\Form\Email(null, 'value');
        $rendered  = $component->render();

        $this->assertEquals(
            '',
            $rendered
        );
    }
}
