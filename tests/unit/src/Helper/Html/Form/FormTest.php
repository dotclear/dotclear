<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class FormTest extends TestCase
{
    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Form('my');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<form.*?>(?:.*?\n*)?<\/form>/',
            $rendered
        );
        $this->assertStringContainsString(
            'name="my"',
            $rendered
        );
        $this->assertStringContainsString(
            'id="my"',
            $rendered
        );
    }

    public function testWithElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Form('my', 'div');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>(?:.*?\n*)?<\/div>/',
            $rendered
        );
        $this->assertStringContainsString(
            'name="my"',
            $rendered
        );
        $this->assertStringContainsString(
            'id="my"',
            $rendered
        );
    }

    public function testGetDefaultElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Form('my', 'slot');

        $this->assertEquals(
            'form',
            $component->getDefaultElement()
        );
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Form('my');

        $this->assertEquals(
            'Dotclear\Helper\Html\Form\Form',
            $component->getType()
        );
        $this->assertEquals(
            Dotclear\Helper\Html\Form\Form::class,
            $component->getType()
        );
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Form('my');

        $this->assertEquals(
            'form',
            $component->getElement()
        );
    }

    public function testGetElementWithOtherElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Form('my', 'div');

        $this->assertEquals(
            'div',
            $component->getElement()
        );
    }

    public function testFields()
    {
        $component = new \Dotclear\Helper\Html\Form\Form('my');

        $field = new \Dotclear\Helper\Html\Form\Input(['myinput']);
        $component->fields([
            $field,
        ]);
        $rendered = $component->render();

        $this->assertStringContainsString(
            $field->render(),
            $rendered
        );
    }

    public function testWithoutNameOrId()
    {
        $component = new \Dotclear\Helper\Html\Form\Form();
        $rendered  = $component->render();

        $this->assertEquals(
            '',
            $rendered
        );
    }

    public function testWithoutNameOrIdAndWithAnElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Form(null, 'div');
        $rendered  = $component->render();

        $this->assertEquals(
            '',
            $rendered
        );
    }
}
