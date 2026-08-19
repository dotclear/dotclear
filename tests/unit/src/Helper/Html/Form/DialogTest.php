<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Html\Form;

use PHPUnit\Framework\TestCase;

class DialogTest extends TestCase
{
    public function test(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Dialog('my');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<dialog.*?>(?:.*?\n*)?<\/dialog>/',
            $rendered
        );
        $this->assertStringNotContainsString(
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
        $component = new \Dotclear\Helper\Html\Form\Dialog('my', 'div');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>(?:.*?\n*)?<\/div>/',
            $rendered
        );
        $this->assertStringNotContainsString(
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
        $component = new \Dotclear\Helper\Html\Form\Dialog('my', 'slot');

        $this->assertEquals(
            'dialog',
            $component->getDefaultElement()
        );
    }

    public function testGetType(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Dialog('my');

        $this->assertEquals(
            'Dotclear\Helper\Html\Form\Dialog',
            $component->getType()
        );
        $this->assertEquals(
            \Dotclear\Helper\Html\Form\Dialog::class,
            $component->getType()
        );
    }

    public function testGetElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Dialog('my');

        $this->assertEquals(
            'dialog',
            $component->getElement()
        );
    }

    public function testGetElementWithOtherElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Dialog('my', 'div');

        $this->assertEquals(
            'div',
            $component->getElement()
        );
    }

    public function testFields(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Dialog();

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

    public function testWithoutNameOrId(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Dialog();
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<dialog.*?>(?:.*?\n*)?<\/dialog>/',
            $rendered
        );
    }

    public function testWithoutNameOrIdAndWithAnElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Dialog(null, 'div');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>(?:.*?\n*)?<\/div>/',
            $rendered
        );
    }

    public function testAttributeClosedby(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Dialog();
        $component->closedby('any');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<dialog.*?>(?:.*?\n*)?<\/dialog>/',
            $rendered
        );
        $this->assertStringContainsString(
            'closedby',
            $rendered
        );
    }

    public function testAttributeFalseOpen(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Dialog();
        $component->open(false);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<dialog.*?>(?:.*?\n*)?<\/dialog>/',
            $rendered
        );
        $this->assertStringNotContainsString(
            'open',
            $rendered
        );
    }

    public function testAttributeTrueOpen(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Dialog();
        $component->open(true);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<dialog.*?>(?:.*?\n*)?<\/dialog>/',
            $rendered
        );
        $this->assertStringContainsString(
            'open',
            $rendered
        );
    }
}
