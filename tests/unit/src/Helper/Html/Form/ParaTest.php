<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Html\Form;

use PHPUnit\Framework\TestCase;

class ParaTest extends TestCase
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Para();
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<p.*?>(?:.*?\n*)?<\/p>/',
            $rendered
        );
    }

    public function testWithEmptyItems()
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

    public function testWithItems()
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

    public function testWithItemsWithSeparator()
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

    public function testWithItemsWithFormat()
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

    public function testWithId()
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

    public function testGetDefaultElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Para();

        $this->assertEquals(
            'p',
            $component->getDefaultElement()
        );
    }

    public function testGetType()
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

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Para();

        $this->assertEquals(
            'p',
            $component->getElement()
        );
    }

    public function testGetElementWithOtherElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Para('my', 'div');

        $this->assertEquals(
            'div',
            $component->getElement()
        );
    }
}
