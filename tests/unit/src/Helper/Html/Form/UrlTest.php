<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class UrlTest extends TestCase
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Url('my', 'value');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<input type="url".*?>/',
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
            'inputmode="url"',
            $rendered
        );
        $this->assertStringContainsString(
            'value="value"',
            $rendered
        );
    }

    public function testWithoutValue()
    {
        $component = new \Dotclear\Helper\Html\Form\Url('my');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<input type="url".*?>/',
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
            'inputmode="url"',
            $rendered
        );
        $this->assertStringNotContainsString(
            'value="value"',
            $rendered
        );
    }

    public function testWithoutNameOrId()
    {
        $component = new \Dotclear\Helper\Html\Form\Url();
        $rendered  = $component->render();

        $this->assertSame(
            '',
            $rendered
        );
    }

    public function testWithoutNameOrIdAndWithAValue()
    {
        $component = new \Dotclear\Helper\Html\Form\Url(null, 'value');
        $rendered  = $component->render();

        $this->assertSame(
            '',
            $rendered
        );
    }
}
