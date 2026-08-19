<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Html\Form;

use PHPUnit\Framework\TestCase;

class AsideTest extends TestCase
{
    public function test(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Aside();
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<aside.*?>(?:.*?\n*)?<\/aside>/',
            $rendered
        );
    }

    public function testWithEmptyItems(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Aside();
        $component->items([
        ]);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<aside.*?>\n<\/aside>/',
            $rendered
        );
    }

    public function testWithItems(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Aside();
        $component->items([
            new \Dotclear\Helper\Html\Form\Text(null, '1st value'),
            new \Dotclear\Helper\Html\Form\None(),
            new \Dotclear\Helper\Html\Form\Text(null, '2nd value'),
        ]);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<aside.*?>\n1st value2nd value<\/aside>/',
            $rendered
        );
    }

    public function testWithItemsWithSeparator(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Aside();
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
            '/<aside.*?>\n1st value---2nd value<\/aside>/',
            $rendered
        );
    }

    public function testWithItemsWithFormat(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Aside();
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
            '/<aside.*?>\n\[1st value\]\[2nd value\]<\/aside>/',
            $rendered
        );
    }

    public function testWithId(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Aside('myid');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<aside.*?>(?:.*?\n*)?<\/aside>/',
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
        $component = new \Dotclear\Helper\Html\Form\Aside();

        $this->assertEquals(
            'aside',
            $component->getDefaultElement()
        );
    }

    public function testGetType(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Aside();

        $this->assertEquals(
            'Dotclear\Helper\Html\Form\Aside',
            $component->getType()
        );
        $this->assertEquals(
            \Dotclear\Helper\Html\Form\Aside::class,
            $component->getType()
        );
    }

    public function testGetElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Aside();

        $this->assertEquals(
            'aside',
            $component->getElement()
        );
    }

    public function testGetElementWithOtherElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Aside('my', 'div');

        $this->assertEquals(
            'div',
            $component->getElement()
        );
    }
}
