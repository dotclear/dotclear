<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class OptgroupTest extends TestCase
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Optgroup('My Group');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<optgroup.*?>(?:.*?\n*)?<\/optgroup>/',
            $rendered
        );
    }

    public function testItemsText()
    {
        $component = new \Dotclear\Helper\Html\Form\Optgroup('My Group');
        $component->items([
            'one' => 1,
            'two' => '0',
            'three',
        ]);
        $rendered = $component->render();

        $this->assertStringContainsString(
            '<option value="1">one</option>',
            $rendered
        );
        $this->assertStringContainsString(
            '<option value="0">two</option>',
            $rendered
        );
        $this->assertStringContainsString(
            '<option value="three">0</option>',
            $rendered
        );
    }

    public function testItemsOption()
    {
        $component = new \Dotclear\Helper\Html\Form\Optgroup('My Group');
        $component->items([
            new \Dotclear\Helper\Html\Form\Option('One', '1'),
            new \Dotclear\Helper\Html\Form\None(),
        ]);
        $rendered = $component->render();

        $this->assertStringContainsString(
            '<option value="1">One</option>',
            $rendered
        );
    }

    public function testItemsOptgroup()
    {
        $component = new \Dotclear\Helper\Html\Form\Optgroup('My Group');
        $component->items([
            (new \Dotclear\Helper\Html\Form\Optgroup('First'))->items([
                new \Dotclear\Helper\Html\Form\Option('One', '1'),
            ]),
        ]);
        $rendered = $component->render();

        $this->assertStringContainsString(
            '<optgroup label="First">',
            $rendered
        );
        $this->assertStringContainsString(
            '<option value="1">One</option>',
            $rendered
        );
        $this->assertStringContainsString(
            '</optgroup>' . "\n" . '</optgroup>',
            $rendered
        );
    }

    public function testItemsArray()
    {
        $component = new \Dotclear\Helper\Html\Form\Optgroup('My Group');
        $component->items([
            'First' => [
                'one' => 1,
                'two' => '0',
                'three',
            ],
        ]);
        $rendered = $component->render();

        $this->assertStringContainsString(
            '<optgroup label="First">',
            $rendered
        );
        $this->assertStringContainsString(
            '<option value="1">one</option>',
            $rendered
        );
        $this->assertStringContainsString(
            '<option value="0">two</option>',
            $rendered
        );
        $this->assertStringContainsString(
            '<option value="three">0</option>',
            $rendered
        );
        $this->assertStringContainsString(
            '</optgroup>' . "\n" . '</optgroup>',
            $rendered
        );
    }

    public function testEmptyItems()
    {
        $component = new \Dotclear\Helper\Html\Form\Optgroup('My Group');
        $component->items([]);
        $rendered = $component->render();

        $this->assertStringNotContainsString(
            '<option',
            $rendered
        );
    }

    public function testGetDefaultElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Optgroup('My Group');

        $this->assertEquals(
            'optgroup',
            $component->getDefaultElement()
        );
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Optgroup('My Group');

        $this->assertEquals(
            'Dotclear\Helper\Html\Form\Optgroup',
            $component->getType()
        );
        $this->assertEquals(
            \Dotclear\Helper\Html\Form\Optgroup::class,
            $component->getType()
        );
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Optgroup('My Group');

        $this->assertEquals(
            'optgroup',
            $component->getElement()
        );
    }

    public function testGetElementWithOtherElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Optgroup('My Group', 'span');

        $this->assertEquals(
            'span',
            $component->getElement()
        );
    }
}
