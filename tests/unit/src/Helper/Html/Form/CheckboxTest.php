<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class CheckboxTest extends TestCase
{
    public function test()
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

    public function testWithoutCheckedValue()
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

    public function testWithoutNameOrId()
    {
        $component = new \Dotclear\Helper\Html\Form\Checkbox();
        $rendered  = $component->render();

        $this->assertEquals(
            '',
            $rendered
        );
    }

    public function testWithFalsyCheckedValue()
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

    public function testWithoutNameOrIdAndWithCheckedValue()
    {
        $component = new \Dotclear\Helper\Html\Form\Checkbox(null, true);
        $rendered  = $component->render();

        $this->assertEquals(
            '',
            $rendered
        );
    }
}
