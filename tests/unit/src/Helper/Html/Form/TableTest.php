<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Html\Form;

use PHPUnit\Framework\TestCase;

class TableTest extends TestCase
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Table('my');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<table.*?>(?:.*?\n*)?<\/table>/',
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

    public function testWithElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Table('my', 'div');
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
        $component = new \Dotclear\Helper\Html\Form\Table('my', 'slot');

        $this->assertEquals(
            'table',
            $component->getDefaultElement()
        );
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Table('my');

        $this->assertEquals(
            'Dotclear\Helper\Html\Form\Table',
            $component->getType()
        );
        $this->assertEquals(
            \Dotclear\Helper\Html\Form\Table::class,
            $component->getType()
        );
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Table('my');

        $this->assertEquals(
            'table',
            $component->getElement()
        );
    }

    public function testGetElementWithOtherElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Table('my', 'div');

        $this->assertEquals(
            'div',
            $component->getElement()
        );
    }

    public function testAttachCaption()
    {
        $component = new \Dotclear\Helper\Html\Form\Table();

        $caption = new \Dotclear\Helper\Html\Form\Caption('mylabel');
        $component->attachCaption($caption);
        $rendered = $component->render();

        $this->assertStringContainsString(
            $caption->render(),
            $rendered
        );
    }

    public function testAttachNullCaption()
    {
        $component = new \Dotclear\Helper\Html\Form\Table();

        $caption = new \Dotclear\Helper\Html\Form\Caption('mylabel');
        $component->attachCaption($caption);
        $component->attachCaption(null);
        $rendered = $component->render();

        $this->assertStringNotContainsString(
            $caption->render(),
            $rendered
        );
    }

    public function testDetachCaption()
    {
        $component = new \Dotclear\Helper\Html\Form\Table();

        $caption = new \Dotclear\Helper\Html\Form\Caption('mylabel');
        $component->attachCaption($caption);
        $component->detachCaption();
        $rendered = $component->render();

        $this->assertStringNotContainsString(
            $caption->render(),
            $rendered
        );
    }

    public function testItems()
    {
        $component = new \Dotclear\Helper\Html\Form\Table();

        $item = new \Dotclear\Helper\Html\Form\Tbody(['myinput']);
        $component->items([
            $item,
        ]);
        $rendered = $component->render();

        $this->assertStringContainsString(
            $item->render(),
            $rendered
        );
    }

    public function testParts()
    {
        $component = new \Dotclear\Helper\Html\Form\Table();

        $caption = new \Dotclear\Helper\Html\Form\Caption('mylabel');
        $thead   = new \Dotclear\Helper\Html\Form\Thead();
        $tbody   = new \Dotclear\Helper\Html\Form\Tbody();
        $tfoot   = new \Dotclear\Helper\Html\Form\Tfoot();

        $component->items([
            $caption,
            $thead,
            $tbody,
            $tfoot,
            (new \Dotclear\Helper\Html\Form\None()),
        ]);
        $rendered = $component->render();

        $this->assertStringContainsString(
            $caption->render(),
            $rendered
        );
        $this->assertStringContainsString(
            $thead->render(),
            $rendered
        );
        $this->assertStringContainsString(
            $tbody->render(),
            $rendered
        );
        $this->assertStringContainsString(
            $tfoot->render(),
            $rendered
        );
    }

    public function testPartsUnordered()
    {
        $component = new \Dotclear\Helper\Html\Form\Table();

        $caption = new \Dotclear\Helper\Html\Form\Caption('mylabel');
        $thead   = new \Dotclear\Helper\Html\Form\Thead();
        $tbody   = new \Dotclear\Helper\Html\Form\Tbody();
        $tfoot   = new \Dotclear\Helper\Html\Form\Tfoot();

        $component->items([
            $tfoot,
            $tbody,
            $thead,
            $caption,
        ]);
        $rendered = $component->render();

        $this->assertStringContainsString(
            $caption->render(),
            $rendered
        );
        $this->assertStringContainsString(
            $thead->render(),
            $rendered
        );
        $this->assertStringContainsString(
            $tbody->render(),
            $rendered
        );
        $this->assertStringContainsString(
            $tfoot->render(),
            $rendered
        );
    }

    public function testDirectParts()
    {
        $component = new \Dotclear\Helper\Html\Form\Table();

        $caption = new \Dotclear\Helper\Html\Form\Caption('mylabel');
        $thead   = new \Dotclear\Helper\Html\Form\Thead();
        $tbody   = new \Dotclear\Helper\Html\Form\Tbody();
        $tfoot   = new \Dotclear\Helper\Html\Form\Tfoot();

        $component
            ->caption($caption)
            ->thead($thead)
            ->tbody($tbody)
            ->tfoot($tfoot)
        ;
        $rendered = $component->render();

        $this->assertStringContainsString(
            $caption->render(),
            $rendered
        );
        $this->assertStringContainsString(
            $thead->render(),
            $rendered
        );
        $this->assertStringContainsString(
            $tbody->render(),
            $rendered
        );
        $this->assertStringContainsString(
            $tfoot->render(),
            $rendered
        );
    }

    public function testDirectAndIndirectParts()
    {
        $component = new \Dotclear\Helper\Html\Form\Table();

        $caption = new \Dotclear\Helper\Html\Form\Caption('mylabel');
        $thead   = new \Dotclear\Helper\Html\Form\Thead();
        $tbody   = new \Dotclear\Helper\Html\Form\Tbody();
        $tfoot   = new \Dotclear\Helper\Html\Form\Tfoot();

        $component
            ->caption($caption)
            ->thead($thead)
            ->tbody($tbody)
            ->tfoot($tfoot)
        ;
        $component->items([
            $tfoot,
            $tbody,
            $thead,
            $caption,
        ]);
        $rendered = $component->render();

        $this->assertStringContainsString(
            $caption->render(),
            $rendered
        );
        $this->assertStringContainsString(
            $thead->render(),
            $rendered
        );
        $this->assertStringContainsString(
            $tbody->render(),
            $rendered
        );
        $this->assertStringContainsString(
            $tfoot->render(),
            $rendered
        );
    }

    public function testItemsIncludingCaption()
    {
        $component = new \Dotclear\Helper\Html\Form\Table();

        $caption = new \Dotclear\Helper\Html\Form\Caption('mylabel');
        $item    = new \Dotclear\Helper\Html\Form\Tbody(['myinput']);

        $component->items([
            $caption,
            $item,
        ]);
        $rendered = $component->render();

        $this->assertStringContainsString(
            $caption->render(),
            $rendered
        );
    }

    public function testFieldsIncludingAttachedCaption()
    {
        $component = new \Dotclear\Helper\Html\Form\Table();

        $caption = new \Dotclear\Helper\Html\Form\Caption('mylabel');
        $component->attachCaption($caption);  // Attached caption will come first before items

        $item = new \Dotclear\Helper\Html\Form\Tbody(['myinput']);

        $component->items([
            $item,
            $caption,
        ]);

        $rendered = $component->render();

        $this->assertStringContainsString(
            $caption->render(),
            $rendered
        );
        $this->assertStringContainsString(
            $item->render(),
            $rendered
        );
        $this->assertStringContainsString(
            $caption->render() . $item->render(),
            $rendered
        );
        $this->assertStringNotContainsString(
            $item->render() . $caption->render(),
            $rendered
        );
    }

    public function testWithoutNameOrId()
    {
        $component = new \Dotclear\Helper\Html\Form\Table();
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<table.*?>(?:.*?\n*)?<\/table>/',
            $rendered
        );
    }

    public function testWithoutNameOrIdAndWithAnElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Table(null, 'div');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>(?:.*?\n*)?<\/div>/',
            $rendered
        );
    }
}
