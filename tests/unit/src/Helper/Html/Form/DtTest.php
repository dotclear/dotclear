<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class DtTest extends TestCase
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Dt();
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<dt.*?>(?:.*?\n*)?<\/dt>/',
            $rendered
        );
    }

    public function testWithText()
    {
        $component = new \Dotclear\Helper\Html\Form\Dt();
        $component->text('Here');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<dt.*?>Here<\/dt>/',
            $rendered
        );
    }

    public function testWithId()
    {
        $component = new \Dotclear\Helper\Html\Form\Dt('myid');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<dt.*?>(?:.*?\n*)?<\/dt>/',
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
        $component = new \Dotclear\Helper\Html\Form\Dt();

        $this->assertEquals(
            'dt',
            $component->getDefaultElement()
        );
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Dt();

        $this->assertEquals(
            'Dotclear\Helper\Html\Form\Dt',
            $component->getType()
        );
        $this->assertEquals(
            Dotclear\Helper\Html\Form\Dt::class,
            $component->getType()
        );
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Dt();

        $this->assertEquals(
            'dt',
            $component->getElement()
        );
    }

    public function testGetElementWithOtherElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Dt('my', 'span');

        $this->assertEquals(
            'span',
            $component->getElement()
        );
    }
}
