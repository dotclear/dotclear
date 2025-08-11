<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Html\Form;

use PHPUnit\Framework\TestCase;

class OptionTest extends TestCase
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Option('My option', 'value');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<option.*?>(?:.*?\n*)?<\/option>/',
            $rendered
        );
        $this->assertStringContainsString(
            'value="value"',
            $rendered
        );
        $this->assertStringContainsString(
            '>My option</option>',
            $rendered
        );
    }

    public function testWithEmptyText()
    {
        $component = new \Dotclear\Helper\Html\Form\Option('', 'value');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<option.*?>(?:.*?\n*)?<\/option>/',
            $rendered
        );
        $this->assertStringContainsString(
            'value="value"',
            $rendered
        );
        $this->assertStringContainsString(
            '></option>',
            $rendered
        );
    }

    public function testWithSelected()
    {
        $component = new \Dotclear\Helper\Html\Form\Option('text', 'value');
        $component->selected(true);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<option.*?>(?:.*?\n*)?<\/option>/',
            $rendered
        );
        $this->assertStringContainsString(
            'value="value"',
            $rendered
        );
        $this->assertStringContainsString(
            'selected',
            $rendered
        );
        $this->assertStringContainsString(
            '>text</option>',
            $rendered
        );
    }

    public function testWithNotSelected()
    {
        $component = new \Dotclear\Helper\Html\Form\Option('text', 'value');
        $component->selected(false);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<option.*?>(?:.*?\n*)?<\/option>/',
            $rendered
        );
        $this->assertStringContainsString(
            'value="value"',
            $rendered
        );
        $this->assertStringNotContainsString(
            'selected',
            $rendered
        );
        $this->assertStringContainsString(
            '>text</option>',
            $rendered
        );
    }

    public function testGetDefaultElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Option('My option', 'value');

        $this->assertEquals(
            'option',
            $component->getDefaultElement()
        );
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Option('My option', 'value');

        $this->assertEquals(
            'Dotclear\Helper\Html\Form\Option',
            $component->getType()
        );
        $this->assertEquals(
            \Dotclear\Helper\Html\Form\Option::class,
            $component->getType()
        );
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Option('My option', 'value');

        $this->assertEquals(
            'option',
            $component->getElement()
        );
    }

    public function testGetElementWithOtherElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Option('My option', 'value', 'slot');

        $this->assertEquals(
            'slot',
            $component->getElement()
        );
    }
}
