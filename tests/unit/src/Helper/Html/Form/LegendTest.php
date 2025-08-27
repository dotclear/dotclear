<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Html\Form;

use PHPUnit\Framework\TestCase;

class LegendTest extends TestCase
{
    public function test(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Legend('My Legend');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<legend.*?>(?:.*?\n*)?<\/legend>/',
            $rendered
        );
        $this->assertStringContainsString(
            'My Legend',
            $rendered
        );
    }

    public function testWithText(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Legend();
        $component->text('My Legend');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<legend.*?>(?:.*?\n*)?<\/legend>/',
            $rendered
        );
        $this->assertStringContainsString(
            'My Legend',
            $rendered
        );
    }

    public function testWithoutText(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Legend();
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<legend.*?><\/legend>/',
            $rendered
        );
    }

    public function testWithId(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Legend('My Legend', 'myid');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<legend.*?>(?:.*?\n*)?<\/legend>/',
            $rendered
        );
        $this->assertStringContainsString(
            'name="myid"',
            $rendered
        );
        $this->assertStringContainsString(
            'id="myid"',
            $rendered
        );
    }

    public function testGetDefaultElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Legend('My Legend');

        $this->assertEquals(
            'legend',
            $component->getDefaultElement()
        );
    }

    public function testGetType(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Legend('My Legend');

        $this->assertEquals(
            'Dotclear\Helper\Html\Form\Legend',
            $component->getType()
        );
        $this->assertEquals(
            \Dotclear\Helper\Html\Form\Legend::class,
            $component->getType()
        );
    }

    public function testGetElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Legend('My Legend');

        $this->assertEquals(
            'legend',
            $component->getElement()
        );
    }

    public function testGetElementWithOtherElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Legend('My Legend', 'myid', 'span');

        $this->assertEquals(
            'span',
            $component->getElement()
        );
    }
}
