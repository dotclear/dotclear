<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Html\Form;

use PHPUnit\Framework\TestCase;

class NumberTest extends TestCase
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Number('my', 0, 99, 50);
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<input type="number".*?>/',
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
            'value="50"',
            $rendered
        );
        $this->assertStringContainsString(
            'min="0"',
            $rendered
        );
        $this->assertStringContainsString(
            'max="99"',
            $rendered
        );
        $this->assertStringContainsString(
            'inputmode="numeric"',
            $rendered
        );
    }

    public function testWithoutValue()
    {
        $component = new \Dotclear\Helper\Html\Form\Number('my');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<input type="number".*?>/',
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
        $this->assertStringNotContainsString(
            'min=',
            $rendered
        );
        $this->assertStringNotContainsString(
            'max=',
            $rendered
        );
        $this->assertStringContainsString(
            'inputmode="numeric"',
            $rendered
        );
    }

    public function testWithMin()
    {
        $component = new \Dotclear\Helper\Html\Form\Number('my', 0);
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<input type="number".*?>/',
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
        $this->assertStringContainsString(
            'min="0"',
            $rendered
        );
        $this->assertStringNotContainsString(
            'max=',
            $rendered
        );
        $this->assertStringContainsString(
            'inputmode="numeric"',
            $rendered
        );
    }

    public function testWithMax()
    {
        $component = new \Dotclear\Helper\Html\Form\Number('my', null, 99);
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<input type="number".*?>/',
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
        $this->assertStringNotContainsString(
            'min=',
            $rendered
        );
        $this->assertStringContainsString(
            'max="99"',
            $rendered
        );
        $this->assertStringContainsString(
            'inputmode="numeric"',
            $rendered
        );
    }

    public function testWithValue()
    {
        $component = new \Dotclear\Helper\Html\Form\Number('my', null, null, 50);
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<input type="number".*?>/',
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
            'value="50"',
            $rendered
        );
        $this->assertStringNotContainsString(
            'min=',
            $rendered
        );
        $this->assertStringNotContainsString(
            'max=',
            $rendered
        );
        $this->assertStringContainsString(
            'inputmode="numeric"',
            $rendered
        );
    }

    public function testWithoutNameOrId()
    {
        $component = new \Dotclear\Helper\Html\Form\Number();
        $rendered  = $component->render();

        $this->assertEquals(
            '',
            $rendered
        );
    }

    public function testWithoutNameOrIdAndWithAValue()
    {
        $component = new \Dotclear\Helper\Html\Form\Number(null, null, null, 50);
        $rendered  = $component->render();

        $this->assertEquals(
            '',
            $rendered
        );
    }
}
