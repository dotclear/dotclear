<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Html\Form;

use PHPUnit\Framework\TestCase;

class LinkTest extends TestCase
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Link();
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<a.*?>(?:.*?\n*)?<\/a>/',
            $rendered
        );
    }

    public function testWithHref()
    {
        $component = new \Dotclear\Helper\Html\Form\Link();
        $component->href('#here');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<a.*?>(?:.*?\n*)?<\/a>/',
            $rendered
        );
        $this->assertStringContainsString(
            'href="#here"',
            $rendered
        );
    }

    public function testWithText()
    {
        $component = new \Dotclear\Helper\Html\Form\Link();
        $component->text('Here');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<a.*?>Here<\/a>/',
            $rendered
        );
    }

    public function testWithId()
    {
        $component = new \Dotclear\Helper\Html\Form\Link('myid');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<a.*?>(?:.*?\n*)?<\/a>/',
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
        $component = new \Dotclear\Helper\Html\Form\Link();

        $this->assertEquals(
            'a',
            $component->getDefaultElement()
        );
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Link();

        $this->assertEquals(
            'Dotclear\Helper\Html\Form\Link',
            $component->getType()
        );
        $this->assertEquals(
            \Dotclear\Helper\Html\Form\Link::class,
            $component->getType()
        );
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Link();

        $this->assertEquals(
            'a',
            $component->getElement()
        );
    }

    public function testGetElementWithOtherElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Link('my', 'slot');

        $this->assertEquals(
            'slot',
            $component->getElement()
        );
    }
}
