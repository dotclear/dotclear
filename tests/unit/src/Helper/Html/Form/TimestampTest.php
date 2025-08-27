<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Html\Form;

use PHPUnit\Framework\TestCase;

class TimestampTest extends TestCase
{
    public function test(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Timestamp('My timestamp');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<time.*?>(?:.*?\n*)?<\/time>/',
            $rendered
        );
        $this->assertStringContainsString(
            'My timestamp',
            $rendered
        );
    }

    public function testWithText(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Timestamp();
        $component->text('My timestamp');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<time.*?>(?:.*?\n*)?<\/time>/',
            $rendered
        );
        $this->assertStringContainsString(
            'My timestamp',
            $rendered
        );
    }

    public function testWithDatetime(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Timestamp();
        $component->datetime('My-Datetime');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<time.*?>(?:.*?\n*)?<\/time>/',
            $rendered
        );
        $this->assertStringContainsString(
            'datetime="My-Datetime"',
            $rendered
        );
    }

    public function testWithoutText(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Timestamp();
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<time.*?><\/time>/',
            $rendered
        );
    }

    public function testGetDefaultElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Timestamp('My timestamp');

        $this->assertEquals(
            'time',
            $component->getDefaultElement()
        );
    }

    public function testGetType(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Timestamp('My timestamp');

        $this->assertEquals(
            'Dotclear\Helper\Html\Form\Timestamp',
            $component->getType()
        );
        $this->assertEquals(
            \Dotclear\Helper\Html\Form\Timestamp::class,
            $component->getType()
        );
    }

    public function testGetElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Timestamp('My timestamp');

        $this->assertEquals(
            'time',
            $component->getElement()
        );
    }

    public function testGetElementWithOtherElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Timestamp('My timestamp', 'span');

        $this->assertEquals(
            'span',
            $component->getElement()
        );
    }
}
