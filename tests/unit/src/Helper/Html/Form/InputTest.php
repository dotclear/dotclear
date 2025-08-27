<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Html\Form;

use PHPUnit\Framework\TestCase;

class InputTest extends TestCase
{
    public function test(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Input('my', 'hidden');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<input type="hidden".*?>/',
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

    public function testWithoutType(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Input('my');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<input type="text".*?>/',
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
        $this->assertStringContainsString(
            'type="text"',
            $rendered
        );
    }

    public function testGetDefaultElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Input('my');

        $this->assertEquals(
            'input',
            $component->getDefaultElement()
        );
    }

    public function testAttachLabel(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Input('my');

        $label = new \Dotclear\Helper\Html\Form\Label('mylabel');
        $component->attachLabel($label);
        $rendered = $component->render();

        $this->assertStringContainsString(
            '<label>mylabel <input type="text" name="my" id="my"></label>',
            $rendered
        );
    }

    public function testAttachLabelOutside(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Input('my');

        $label = new \Dotclear\Helper\Html\Form\Label('mylabel', \Dotclear\Helper\Html\Form\Label::OUTSIDE_LABEL_BEFORE);
        $component->attachLabel($label);
        $rendered = $component->render();

        $this->assertStringContainsString(
            '<label for="my">mylabel</label> <input type="text" name="my" id="my">',
            $rendered
        );
    }

    public function testAttachLabelButWithoutRendering(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Input('my', 'test', false);

        $label = new \Dotclear\Helper\Html\Form\Label('mylabel');
        $component->attachLabel($label);
        $rendered = $component->render();

        $this->assertStringNotContainsString(
            '<label>',
            $rendered
        );
    }

    public function testDetachLabel(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Input('my');

        $label = new \Dotclear\Helper\Html\Form\Label('mylabel');
        $component->attachLabel($label);
        $component->detachLabel();

        $this->assertNull(
            $component->label()
        );
    }

    public function testWithPopovertarget(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Input('my');
        $component->popovertarget('My-Popover');
        $rendered = $component->render();

        $this->assertStringContainsString(
            'popovertarget="My-Popover"',
            $rendered
        );
    }

    public function testWithPopovertargetaction(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Input('my');
        $component->popovertargetaction('show');
        $rendered = $component->render();

        $this->assertStringContainsString(
            'popovertargetaction="show"',
            $rendered
        );
    }

    public function testWithoutNameOrId(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Input();
        $rendered  = $component->render();

        $this->assertEquals(
            '',
            $rendered
        );
    }

    public function testWithoutNameOrIdAndWithAValue(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Input(null, 'value');
        $rendered  = $component->render();

        $this->assertEquals(
            '',
            $rendered
        );
    }

    public function testNoIdOutsideLabel(): void
    {
        $component = $this->getMockBuilder(\Dotclear\Helper\Html\Form\Input::class)
            ->onlyMethods(['checkMandatoryAttributes'])
            ->enableOriginalConstructor()
            ->getMock();

        $component->method('checkMandatoryAttributes')->willReturn(true);

        $label = new \Dotclear\Helper\Html\Form\Label('mylabel', \Dotclear\Helper\Html\Form\Label::OL_TF);
        $component->attachLabel($label);
        $rendered = $component->render();

        $this->assertStringContainsString(
            '<input type="text">',
            $rendered
        );
    }

    public function testNoIdVerbose(): void
    {
        $component = $this->getMockBuilder(\Dotclear\Helper\Html\Form\Input::class)
            ->onlyMethods(['checkMandatoryAttributes', 'isVerbose'])
            ->enableOriginalConstructor()
            ->getMock();

        $component->method('checkMandatoryAttributes')->willReturn(false);
        $component->method('isVerbose')->willReturn(true);

        $rendered = $component->render();

        $this->assertStringContainsString(
            'Input (type = text) without id and name (provide at least one of them)',
            $rendered
        );
    }
}
