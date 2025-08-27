<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Html\Form;

use PHPUnit\Framework\TestCase;

class RadioTest extends TestCase
{
    public function test(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Radio('my', true);
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<input type="radio".*?>/',
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
            'checked',
            $rendered
        );
    }

    public function testWithoutCheckedValue(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Radio('my');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<input type="radio".*?>/',
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
            'checked',
            $rendered
        );
    }

    public function testWithoutNameOrId(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Radio();
        $rendered  = $component->render();

        $this->assertEquals(
            '',
            $rendered
        );
    }

    public function testWithFalsyCheckedValue(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Radio('my', false);
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<input type="radio".*?>/',
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
            'checked',
            $rendered
        );
    }

    public function testWithoutNameOrIdAndWithCheckedValue(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Radio(null, true);
        $rendered  = $component->render();

        $this->assertEquals(
            '',
            $rendered
        );
    }
}
