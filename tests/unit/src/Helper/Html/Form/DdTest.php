<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Html\Form;

use PHPUnit\Framework\TestCase;

class DdTest extends TestCase
{
    public function test(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Dd();
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<dd.*?>(?:.*?\n*)?<\/dd>/',
            $rendered
        );
    }

    public function testWithText(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Dd();
        $component->text('Here');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<dd.*?>Here<\/dd>/',
            $rendered
        );
    }

    public function testWithId(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Dd('myid');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<dd.*?>(?:.*?\n*)?<\/dd>/',
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
        $component = new \Dotclear\Helper\Html\Form\Dd();

        $this->assertEquals(
            'dd',
            $component->getDefaultElement()
        );
    }

    public function testGetType(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Dd();

        $this->assertEquals(
            'Dotclear\Helper\Html\Form\Dd',
            $component->getType()
        );
        $this->assertEquals(
            \Dotclear\Helper\Html\Form\Dd::class,
            $component->getType()
        );
    }

    public function testGetElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Dd();

        $this->assertEquals(
            'dd',
            $component->getElement()
        );
    }

    public function testGetElementWithOtherElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Dd('my', 'span');

        $this->assertEquals(
            'span',
            $component->getElement()
        );
    }
}
