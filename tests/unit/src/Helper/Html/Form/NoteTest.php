<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Html\Form;

use PHPUnit\Framework\TestCase;

class NoteTest extends TestCase
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Note();
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<p.*?>(?:.*?\n*)?<\/p>/',
            $rendered
        );
    }

    public function testWithText()
    {
        $component = new \Dotclear\Helper\Html\Form\Note();
        $component->text('Here');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<p.*?>Here<\/p>/',
            $rendered
        );
    }

    public function testWithId()
    {
        $component = new \Dotclear\Helper\Html\Form\Note('myid');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<p.*?>(?:.*?\n*)?<\/p>/',
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
        $component = new \Dotclear\Helper\Html\Form\Note();

        $this->assertEquals(
            'p',
            $component->getDefaultElement()
        );
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Note();

        $this->assertEquals(
            'Dotclear\Helper\Html\Form\Note',
            $component->getType()
        );
        $this->assertEquals(
            \Dotclear\Helper\Html\Form\Note::class,
            $component->getType()
        );
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Note();

        $this->assertEquals(
            'p',
            $component->getElement()
        );
    }

    public function testGetElementWithOtherElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Note('my', 'span');

        $this->assertEquals(
            'span',
            $component->getElement()
        );
    }
}
