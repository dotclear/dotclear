<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class TextTest extends TestCase
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Text(null, 'TEXT');
        $rendered  = $component->render();

        $this->assertEquals(
            'TEXT',
            $rendered
        );
    }

    public function testWithACommonAttribute()
    {
        $component = new \Dotclear\Helper\Html\Form\Text(null, 'TEXT');
        $component->setIdentifier('myid');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<span.*?>(?:.*?\n*)?<\/span>/',
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
        $component = new \Dotclear\Helper\Html\Form\Text();

        $this->assertEquals(
            '',
            $component->getDefaultElement()
        );
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Text();

        $this->assertEquals(
            'Dotclear\Helper\Html\Form\Text',
            $component->getType()
        );
        $this->assertEquals(
            \Dotclear\Helper\Html\Form\Text::class,
            $component->getType()
        );
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Text();

        $this->assertEquals(
            '',
            $component->getElement()
        );
    }

    public function testGetElementWithOtherElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Text('span');

        $this->assertEquals(
            'span',
            $component->getElement()
        );
    }

    public function testWithItems()
    {
        $component = new \Dotclear\Helper\Html\Form\Text(null, 'TEXT');
        $component
            ->separator(' - ')
            ->items([
                (new \Dotclear\Helper\Html\Form\Span('FIRST')),
                (new \Dotclear\Helper\Html\Form\Span('SECOND')),
            ]);
        $rendered = $component->render();

        $this->assertEquals(
            'TEXT<span>FIRST</span> - <span>SECOND</span>',
            $rendered
        );
    }

    public function testWithItemsAndOtherElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Text('var', 'TEXT');
        $component
            ->separator(' - ')
            ->items([
                (new \Dotclear\Helper\Html\Form\Span('FIRST')),
                (new \Dotclear\Helper\Html\Form\Span('SECOND')),
            ]);
        $rendered = $component->render();

        $this->assertEquals(
            '<var>TEXT<span>FIRST</span> - <span>SECOND</span></var>',
            $rendered
        );
    }
}
