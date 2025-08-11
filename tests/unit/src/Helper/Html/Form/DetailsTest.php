<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class DetailsTest extends TestCase
{
    public function test()
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

    public function testWithElement()
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

    public function testGetDefaultElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Details('my', 'slot');

        $this->assertEquals(
            'details',
            $component->getDefaultElement()
        );
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Details('my');

        $this->assertEquals(
            'Dotclear\Helper\Html\Form\Details',
            $component->getType()
        );
        $this->assertEquals(
            Dotclear\Helper\Html\Form\Details::class,
            $component->getType()
        );
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Details('my');

        $this->assertEquals(
            'details',
            $component->getElement()
        );
    }

    public function testGetElementWithOtherElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Details('my', 'div');

        $this->assertEquals(
            'div',
            $component->getElement()
        );
    }

    public function testAttachSummary()
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

    public function testAttachNullSummary()
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

    public function testDetachSummary()
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

    public function testFields()
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

    public function testFieldsIncludingSummary()
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

    public function testFieldsIncludingAttachedSummary()
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

    public function testWithoutNameOrId()
    {
        $component = new \Dotclear\Helper\Html\Form\Details();
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<details.*?>(?:.*?\n*)?<\/details>/',
            $rendered
        );
    }

    public function testWithoutNameOrIdAndWithAnElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Details(null, 'div');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>(?:.*?\n*)?<\/div>/',
            $rendered
        );
    }

    public function testAttributeFalseOpen()
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

    public function testAttributeTrueOpen()
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
