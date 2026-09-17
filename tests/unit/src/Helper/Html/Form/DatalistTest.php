<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Html\Form;

use PHPUnit\Framework\TestCase;

class DatalistTest extends TestCase
{
    public function test(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Datalist('My datalist');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<datalist.*?>(?:.*?\n*)?<\/datalist>/',
            $rendered
        );
    }

    public function testItemsText(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Datalist('My datalist');
        // @phpstan-ignore argument.type
        $component->items([
            'one' => 1,
            'two' => '0',
            'three',
        ]);
        $rendered = $component->render();

        $this->assertStringContainsString(
            '<option value="1"></option>',
            $rendered
        );
        $this->assertStringContainsString(
            '<option value="0"></option>',
            $rendered
        );
        $this->assertStringContainsString(
            '<option value="three"></option>',
            $rendered
        );
    }

    public function testItemsOption(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Datalist('My datalist');
        $component->items([
            new \Dotclear\Helper\Html\Form\Option('One', '1'),
            new \Dotclear\Helper\Html\Form\None(),
        ]);
        $rendered = $component->render();

        $this->assertStringContainsString(
            '<option value="1"></option>',
            $rendered
        );
    }

    public function testItemsArray(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Datalist('My Group');
        // @phpstan-ignore argument.type
        $component->items([
            'one' => 1,
            'two' => '0',
            'three',
        ]);
        $rendered = $component->render();

        $this->assertStringContainsString(
            '<datalist id="My Group">',
            $rendered
        );
        $this->assertStringContainsString(
            '<option value="1"></option>',
            $rendered
        );
        $this->assertStringContainsString(
            '<option value="0"></option>',
            $rendered
        );
        $this->assertStringContainsString(
            '<option value="three"></option>',
            $rendered
        );
        $this->assertStringContainsString(
            "\n" . '</datalist>',
            $rendered
        );
    }

    public function testEmptyItems(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Datalist('My datalist');
        $component->items([]);
        $rendered = $component->render();

        $this->assertStringNotContainsString(
            '<option',
            $rendered
        );
    }

    public function testGetDefaultElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Datalist('My datalist');

        $this->assertEquals(
            'datalist',
            $component->getDefaultElement()
        );
    }

    public function testGetType(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Datalist('My datalist');

        $this->assertEquals(
            'Dotclear\Helper\Html\Form\Datalist',
            $component->getType()
        );
        $this->assertEquals(
            \Dotclear\Helper\Html\Form\Datalist::class,
            $component->getType()
        );
    }

    public function testGetElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Datalist('My datalist');

        $this->assertEquals(
            'datalist',
            $component->getElement()
        );
    }

    public function testGetElementWithOtherElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Datalist('My datalist', 'span');

        $this->assertEquals(
            'span',
            $component->getElement()
        );
    }
}
