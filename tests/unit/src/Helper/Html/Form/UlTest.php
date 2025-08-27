<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Html\Form;

use PHPUnit\Framework\TestCase;

class UlTest extends TestCase
{
    public function test(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Ul();
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<ul.*?>(?:.*?\n*)?<\/ul>/',
            $rendered
        );
    }

    public function testWithId(): void
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

    public function testGetDefaultElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Ul();

        $this->assertEquals(
            'ul',
            $component->getDefaultElement()
        );
    }

    public function testGetType(): void
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

    public function testGetElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Ul();

        $this->assertEquals(
            'ul',
            $component->getElement()
        );
    }

    public function testGetElementWithOtherElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Ul('my', 'span');

        $this->assertEquals(
            'span',
            $component->getElement()
        );
    }
}
