<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class UlTest extends TestCase
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Ul();
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<ul.*?>(?:.*?\n*)?<\/ul>/',
            $rendered
        );
    }

    public function testWithId()
    {
        $component = new \Dotclear\Helper\Html\Form\Ul('myid');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<ul.*?>(?:.*?\n*)?<\/ul>/',
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
        $component = new \Dotclear\Helper\Html\Form\Ul();

        $this->assertEquals(
            'ul',
            $component->getDefaultElement()
        );
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Ul();

        $this->assertEquals(
            'Dotclear\Helper\Html\Form\Ul',
            $component->getType()
        );
        $this->assertEquals(
            \Dotclear\Helper\Html\Form\Ul::class,
            $component->getType()
        );
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Ul();

        $this->assertEquals(
            'ul',
            $component->getElement()
        );
    }

    public function testGetElementWithOtherElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Ul('my', 'span');

        $this->assertEquals(
            'span',
            $component->getElement()
        );
    }
}
