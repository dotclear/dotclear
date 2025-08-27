<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Html\Form;

use PHPUnit\Framework\TestCase;

class SummaryTest extends TestCase
{
    public function test(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Summary('My summary');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<summary*?>(?:.*?\n*)?<\/summary>/',
            $rendered
        );
        $this->assertStringContainsString(
            'My summary',
            $rendered
        );
    }

    public function testWithText(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Summary();
        $component->text('My summary');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<summary.*?>(?:.*?\n*)?<\/summary>/',
            $rendered
        );
        $this->assertStringContainsString(
            'My summary',
            $rendered
        );
    }

    public function testWithoutText(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Summary();
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<summary.*?><\/summary>/',
            $rendered
        );
    }

    public function testWithId(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Summary('My summary', 'myid');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<summary.*?>(?:.*?\n*)?<\/summary>/',
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
        $component = new \Dotclear\Helper\Html\Form\Summary('My summary');

        $this->assertEquals(
            'summary',
            $component->getDefaultElement()
        );
    }

    public function testGetType(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Summary('My summary');

        $this->assertEquals(
            'Dotclear\Helper\Html\Form\Summary',
            $component->getType()
        );
        $this->assertEquals(
            \Dotclear\Helper\Html\Form\Summary::class,
            $component->getType()
        );
    }

    public function testGetElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Summary('My summary');

        $this->assertEquals(
            'summary',
            $component->getElement()
        );
    }

    public function testGetElementWithOtherElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Summary('My summary', 'myid', 'span');

        $this->assertEquals(
            'span',
            $component->getElement()
        );
    }
}
