<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Html\Form;

use PHPUnit\Framework\TestCase;

class NavTest extends TestCase
{
    public function test(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Nav();
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<nav.*?>(?:.*?\n*)?<\/nav>/',
            $rendered
        );
    }

    public function testWithEmptyItems(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Nav();
        $component->items([
        ]);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<nav.*?>\n<\/nav>/',
            $rendered
        );
    }

    public function testWithItems(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Nav();
        $component->items([
            new \Dotclear\Helper\Html\Form\Text(null, '1st value'),
            new \Dotclear\Helper\Html\Form\None(),
            new \Dotclear\Helper\Html\Form\Text(null, '2nd value'),
        ]);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<nav.*?>\n1st value2nd value<\/nav>/',
            $rendered
        );
    }

    public function testWithItemsWithSeparator(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Nav();
        $component
            ->separator('---')
            ->items(
                [
                    new \Dotclear\Helper\Html\Form\Text(null, '1st value'),
                    new \Dotclear\Helper\Html\Form\Text(null, '2nd value'),
                ]
            );
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<nav.*?>\n1st value---2nd value<\/nav>/',
            $rendered
        );
    }

    public function testWithItemsWithFormat(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Nav();
        $component
            ->format('[%s]')
            ->items(
                [
                    new \Dotclear\Helper\Html\Form\Text(null, '1st value'),
                    new \Dotclear\Helper\Html\Form\Text(null, '2nd value'),
                ]
            );
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<nav.*?>\n\[1st value\]\[2nd value\]<\/nav>/',
            $rendered
        );
    }

    public function testWithId(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Nav('myid');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<nav.*?>(?:.*?\n*)?<\/nav>/',
            $rendered
        );
        $this->assertStringNotContainsString(
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
        $component = new \Dotclear\Helper\Html\Form\Nav();

        $this->assertEquals(
            'nav',
            $component->getDefaultElement()
        );
    }

    public function testGetType(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Nav();

        $this->assertEquals(
            'Dotclear\Helper\Html\Form\Nav',
            $component->getType()
        );
        $this->assertEquals(
            \Dotclear\Helper\Html\Form\Nav::class,
            $component->getType()
        );
    }

    public function testGetElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Nav();

        $this->assertEquals(
            'nav',
            $component->getElement()
        );
    }

    public function testGetElementWithOtherElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Nav('my', 'div');

        $this->assertEquals(
            'div',
            $component->getElement()
        );
    }
}
