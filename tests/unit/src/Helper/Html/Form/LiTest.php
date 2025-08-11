<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Html\Form;

use PHPUnit\Framework\TestCase;

class LiTest extends TestCase
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Li();
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<li.*?>(?:.*?\n*)?<\/li>/',
            $rendered
        );
    }

    public function testWithText()
    {
        $component = new \Dotclear\Helper\Html\Form\Li();
        $component->text('Here');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<li.*?>Here<\/li>/',
            $rendered
        );
    }

    public function testWithId()
    {
        $component = new \Dotclear\Helper\Html\Form\Li('myid');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<li.*?>(?:.*?\n*)?<\/li>/',
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

    public function testWithType()
    {
        $component = new \Dotclear\Helper\Html\Form\Li();
        $component->type('I');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<li.*?>(?:.*?\n*)?<\/li>/',
            $rendered
        );
        $this->assertStringContainsString(
            'type="I"',
            $rendered
        );
    }

    public function testGetDefaultElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Li();

        $this->assertEquals(
            'li',
            $component->getDefaultElement()
        );
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Li();

        $this->assertEquals(
            'Dotclear\Helper\Html\Form\Li',
            $component->getType()
        );
        $this->assertEquals(
            \Dotclear\Helper\Html\Form\Li::class,
            $component->getType()
        );
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Li();

        $this->assertEquals(
            'li',
            $component->getElement()
        );
    }

    public function testGetElementWithOtherElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Li('my', 'span');

        $this->assertEquals(
            'span',
            $component->getElement()
        );
    }
}
