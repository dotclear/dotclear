<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Html\Form;

use PHPUnit\Framework\TestCase;

class CaptionTest extends TestCase
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Caption('My Caption');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<caption.*?>(?:.*?\n*)?<\/caption>/',
            $rendered
        );
        $this->assertStringContainsString(
            'My Caption',
            $rendered
        );
    }

    public function testWithText()
    {
        $component = new \Dotclear\Helper\Html\Form\Caption();
        $component->text('My Caption');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<caption.*?>(?:.*?\n*)?<\/caption>/',
            $rendered
        );
        $this->assertStringContainsString(
            'My Caption',
            $rendered
        );
    }

    public function testWithoutText()
    {
        $component = new \Dotclear\Helper\Html\Form\Caption();
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<caption.*?><\/caption>/',
            $rendered
        );
    }

    public function testWithId()
    {
        $component = new \Dotclear\Helper\Html\Form\Caption('My Caption', 'myid');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<caption.*?>(?:.*?\n*)?<\/caption>/',
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
        $component = new \Dotclear\Helper\Html\Form\Caption('My Caption');

        $this->assertEquals(
            'caption',
            $component->getDefaultElement()
        );
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Caption('My Caption');

        $this->assertEquals(
            'Dotclear\Helper\Html\Form\Caption',
            $component->getType()
        );
        $this->assertEquals(
            \Dotclear\Helper\Html\Form\Caption::class,
            $component->getType()
        );
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Caption('My Caption');

        $this->assertEquals(
            'caption',
            $component->getElement()
        );
    }

    public function testGetElementWithOtherElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Caption('My Caption', 'myid', 'span');

        $this->assertEquals(
            'span',
            $component->getElement()
        );
    }
}
