<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class SingleTest extends TestCase
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Single('hr');
        $rendered  = $component->render();

        $this->assertEquals(
            '<hr>',
            $rendered
        );
    }

    public function testWithEmptyElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Single('');
        $rendered  = $component->render();

        $this->assertEquals(
            '',
            $rendered
        );
    }

    public function testWithACommonAttribute()
    {
        $component = new \Dotclear\Helper\Html\Form\Single('hr');
        $component->setIdentifier('myid');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<hr.*?>/',
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
        $component = new \Dotclear\Helper\Html\Form\Single('');

        $this->assertEquals(
            '',
            $component->getDefaultElement()
        );
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Single('');

        $this->assertEquals(
            'Dotclear\Helper\Html\Form\Single',
            $component->getType()
        );
        $this->assertEquals(
            \Dotclear\Helper\Html\Form\Single::class,
            $component->getType()
        );
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Single('');

        $this->assertEquals(
            '',
            $component->getElement()
        );
    }

    public function testGetElementWithOtherElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Single('br');

        $this->assertEquals(
            'br',
            $component->getElement()
        );
    }
}
