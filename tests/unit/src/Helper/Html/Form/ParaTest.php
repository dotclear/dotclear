<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Html\Form;

use PHPUnit\Framework\TestCase;

class ParaTest extends TestCase
{
    public function test(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Para();
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<p.*?>(?:.*?\n*)?<\/p>/',
            $rendered
        );
    }

    public function testWithEmptyItems(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Para();
        $component->items([
        ]);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<p.*?><\/p>/',
            $rendered
        );
    }

    public function testWithItems(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Para();
        $component->items([
            new \Dotclear\Helper\Html\Form\Text(null, '1st value'),
            new \Dotclear\Helper\Html\Form\None(),
            new \Dotclear\Helper\Html\Form\Text(null, '2nd value'),
        ]);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<p.*?>1st value2nd value<\/p>/',
            $rendered
        );
    }

    public function testWithItemsWithSeparator(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Para();
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
            '/<p.*?>1st value---2nd value<\/p>/',
            $rendered
        );
    }

    public function testWithItemsWithFormat(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Para();
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
            '/<p.*?>\[1st value\]\[2nd value\]<\/p>/',
            $rendered
        );
    }

    public function testWithId(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Para('myid');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<p.*?>(?:.*?\n*)?<\/p>/',
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
        $component = new \Dotclear\Helper\Html\Form\Para();

        $this->assertEquals(
            'p',
            $component->getDefaultElement()
        );
    }

    public function testGetType(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Para();

        $this->assertEquals(
            'Dotclear\Helper\Html\Form\Para',
            $component->getType()
        );
        $this->assertEquals(
            \Dotclear\Helper\Html\Form\Para::class,
            $component->getType()
        );
    }

    public function testGetElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Para();

        $this->assertEquals(
            'p',
            $component->getElement()
        );
    }

    public function testGetElementWithOtherElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Para('my', 'div');

        $this->assertEquals(
            'div',
            $component->getElement()
        );
    }
}
