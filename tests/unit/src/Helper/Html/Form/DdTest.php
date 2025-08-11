<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class DdTest extends TestCase
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Dd();
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<dd.*?>(?:.*?\n*)?<\/dd>/',
            $rendered
        );
    }

    public function testWithText()
    {
        $component = new \Dotclear\Helper\Html\Form\Dd();
        $component->text('Here');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<dd.*?>Here<\/dd>/',
            $rendered
        );
    }

    public function testWithId()
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

    public function testGetDefaultElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Dd();

        $this->assertEquals(
            'dd',
            $component->getDefaultElement()
        );
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Dd();

        $this->assertEquals(
            'Dotclear\Helper\Html\Form\Dd',
            $component->getType()
        );
        $this->assertEquals(
            Dotclear\Helper\Html\Form\Dd::class,
            $component->getType()
        );
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Dd();

        $this->assertEquals(
            'dd',
            $component->getElement()
        );
    }

    public function testGetElementWithOtherElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Dd('my', 'span');

        $this->assertEquals(
            'span',
            $component->getElement()
        );
    }
}
