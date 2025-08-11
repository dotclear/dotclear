<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Html\Form;

use PHPUnit\Framework\TestCase;

class LegendTest extends TestCase
{
    public function test()
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

    public function testWithText()
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

    public function testWithoutText()
    {
        $component = new \Dotclear\Helper\Html\Form\Legend();
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<legend.*?><\/legend>/',
            $rendered
        );
    }

    public function testWithId()
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

    public function testGetDefaultElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Legend('My Legend');

        $this->assertEquals(
            'legend',
            $component->getDefaultElement()
        );
    }

    public function testGetType()
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

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Legend('My Legend');

        $this->assertEquals(
            'legend',
            $component->getElement()
        );
    }

    public function testGetElementWithOtherElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Legend('My Legend', 'myid', 'span');

        $this->assertEquals(
            'span',
            $component->getElement()
        );
    }
}
