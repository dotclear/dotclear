<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class OlTest extends TestCase
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Ol();
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<ol.*?>(?:.*?\n*)?<\/ol>/',
            $rendered
        );
    }

    public function testWithId()
    {
        $component = new \Dotclear\Helper\Html\Form\Ol('myid');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<ol.*?>(?:.*?\n*)?<\/ol>/',
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

    public function testWithStart()
    {
        $component = new \Dotclear\Helper\Html\Form\Ol();
        $component->start('3');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<ol.*?>(?:.*?\n*)?<\/ol>/',
            $rendered
        );
        $this->assertStringContainsString(
            'start="3"',
            $rendered
        );
    }

    public function testWithType()
    {
        $component = new \Dotclear\Helper\Html\Form\Ol();
        $component->type('I');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<ol.*?>(?:.*?\n*)?<\/ol>/',
            $rendered
        );
        $this->assertStringContainsString(
            'type="I"',
            $rendered
        );
    }

    public function testWithReversed()
    {
        $component = new \Dotclear\Helper\Html\Form\Ol();
        $component->reversed(true);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<ol.*?>(?:.*?\n*)?<\/ol>/',
            $rendered
        );
        $this->assertStringContainsString(
            'reversed',
            $rendered
        );

        $component->reversed(false);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<ol.*?>(?:.*?\n*)?<\/ol>/',
            $rendered
        );
        $this->assertStringNotContainsString(
            'reversed',
            $rendered
        );
    }

    public function testGetDefaultElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Ol();

        $this->assertEquals(
            'ol',
            $component->getDefaultElement()
        );
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Ol();

        $this->assertEquals(
            'Dotclear\Helper\Html\Form\Ol',
            $component->getType()
        );
        $this->assertEquals(
            \Dotclear\Helper\Html\Form\Ol::class,
            $component->getType()
        );
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Ol();

        $this->assertEquals(
            'ol',
            $component->getElement()
        );
    }

    public function testGetElementWithOtherElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Ol('my', 'span');

        $this->assertEquals(
            'span',
            $component->getElement()
        );
    }
}
