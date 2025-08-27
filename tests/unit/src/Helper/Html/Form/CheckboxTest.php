<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Html\Form;

use PHPUnit\Framework\TestCase;

class CheckboxTest extends TestCase
{
    public function test(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Checkbox('my', true);
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<input type="checkbox".*?>/',
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
        $component = new \Dotclear\Helper\Html\Form\Checkbox('my');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<input type="checkbox".*?>/',
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
        $component = new \Dotclear\Helper\Html\Form\Checkbox();
        $rendered  = $component->render();

        $this->assertEquals(
            '',
            $rendered
        );
    }

    public function testWithFalsyCheckedValue(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Checkbox('my', false);
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<input type="checkbox".*?>/',
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
        $component = new \Dotclear\Helper\Html\Form\Checkbox(null, true);
        $rendered  = $component->render();

        $this->assertEquals(
            '',
            $rendered
        );
    }
}
