<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Html\Form;

use PHPUnit\Framework\TestCase;

class DetailsTest extends TestCase
{
    public function test(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Details('my');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<details.*?>(?:.*?\n*)?<\/details>/',
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
            'open',
            $rendered
        );
    }

    public function testWithElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Details('my', 'div');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>(?:.*?\n*)?<\/div>/',
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
    }

    public function testGetDefaultElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Details('my', 'slot');

        $this->assertEquals(
            'details',
            $component->getDefaultElement()
        );
    }

    public function testGetType(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Details('my');

        $this->assertEquals(
            'Dotclear\Helper\Html\Form\Details',
            $component->getType()
        );
        $this->assertEquals(
            \Dotclear\Helper\Html\Form\Details::class,
            $component->getType()
        );
    }

    public function testGetElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Details('my');

        $this->assertEquals(
            'details',
            $component->getElement()
        );
    }

    public function testGetElementWithOtherElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Details('my', 'div');

        $this->assertEquals(
            'div',
            $component->getElement()
        );
    }

    public function testAttachSummary(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Details();

        $summary = new \Dotclear\Helper\Html\Form\Summary('mylabel');
        $component->attachSummary($summary);
        $rendered = $component->render();

        $this->assertStringContainsString(
            $summary->render(),
            $rendered
        );
    }

    public function testAttachNullSummary(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Details();

        $summary = new \Dotclear\Helper\Html\Form\Summary('mylabel');
        $component->attachSummary($summary);
        $component->attachSummary(null);
        $rendered = $component->render();

        $this->assertStringNotContainsString(
            $summary->render(),
            $rendered
        );
    }

    public function testDetachSummary(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Details();

        $summary = new \Dotclear\Helper\Html\Form\Summary('mylabel');
        $component->attachSummary($summary);
        $component->detachSummary();
        $rendered = $component->render();

        $this->assertStringNotContainsString(
            $summary->render(),
            $rendered
        );
    }

    public function testFields(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Details();

        $field = new \Dotclear\Helper\Html\Form\Input(['myinput']);
        $component->items([
            $field,
        ]);
        $rendered = $component->render();

        $this->assertStringContainsString(
            $field->render(),
            $rendered
        );
    }

    public function testFieldsIncludingSummary(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Details();

        $summary = new \Dotclear\Helper\Html\Form\Summary('mylabel');
        $item    = new \Dotclear\Helper\Html\Form\Input(['myinput']);

        $component->items([
            $summary,
            $item,
        ]);
        $rendered = $component->render();

        $this->assertStringContainsString(
            $summary->render(),
            $rendered
        );
        $this->assertStringContainsString(
            $item->render(),
            $rendered
        );
    }

    public function testFieldsIncludingAttachedSummary(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Details();

        $summary = new \Dotclear\Helper\Html\Form\Summary('mylabel');
        $component->attachSummary($summary);  // Attached summary will come first before items

        $item = new \Dotclear\Helper\Html\Form\Input(['myinput']);

        $component->items([
            $item,
            $summary,
        ]);
        $rendered = $component->render();

        $this->assertStringContainsString(
            $summary->render(),
            $rendered
        );
        $this->assertStringContainsString(
            $item->render(),
            $rendered
        );
        $this->assertStringContainsString(
            $summary->render() . $item->render(),
            $rendered
        );
        $this->assertStringNotContainsString(
            $item->render() . $summary->render(),
            $rendered
        );
    }

    public function testWithoutNameOrId(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Details();
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<details.*?>(?:.*?\n*)?<\/details>/',
            $rendered
        );
    }

    public function testWithoutNameOrIdAndWithAnElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Details(null, 'div');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>(?:.*?\n*)?<\/div>/',
            $rendered
        );
    }

    public function testAttributeFalseOpen(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Details();
        $component->open(false);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<details.*?>(?:.*?\n*)?<\/details>/',
            $rendered
        );
        $this->assertStringNotContainsString(
            'open',
            $rendered
        );
    }

    public function testAttributeTrueOpen(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Details();
        $component->open(true);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<details.*?>(?:.*?\n*)?<\/details>/',
            $rendered
        );
        $this->assertStringContainsString(
            'open',
            $rendered
        );
    }
}
