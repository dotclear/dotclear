<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Html\Form;

use PHPUnit\Framework\TestCase;

class TextareaTest extends TestCase
{
    public function test(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Textarea('myid');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<textarea.*?>(?:.*?\n*)?<\/textarea>/',
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

    public function testWithValue(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Textarea('myid', 'CONTENT');
        $rendered  = $component->render();

        $this->assertStringContainsString(
            '>CONTENT</textarea>',
            $rendered
        );
    }

    public function testWithoutId(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Textarea();
        $rendered  = $component->render();

        $this->assertEquals(
            '',
            $rendered
        );
    }

    public function testAttachLabel(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Textarea('my');

        $label = new \Dotclear\Helper\Html\Form\Label('mylabel');
        $component->attachLabel($label);
        $rendered = $component->render();

        $this->assertStringContainsString(
            '<label>mylabel <textarea name="my" id="my"></textarea></label>',
            $rendered
        );
    }

    public function testAttachLabelOutside(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Textarea('my');

        $label = new \Dotclear\Helper\Html\Form\Label('mylabel', \Dotclear\Helper\Html\Form\Label::OUTSIDE_LABEL_BEFORE);
        $component->attachLabel($label);
        $rendered = $component->render();

        $this->assertStringContainsString(
            '<label for="my">mylabel</label> <textarea name="my" id="my"></textarea>',
            $rendered
        );
    }

    public function testDetachLabel(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Textarea('my');

        $label = new \Dotclear\Helper\Html\Form\Label('mylabel');
        $component->attachLabel($label);
        $component->detachLabel();

        $this->assertNull(
            // @phpstan-ignore arguments.count
            $component->label()
        );
    }

    public function testGetDefaultElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Textarea('myid');

        $this->assertEquals(
            'textarea',
            $component->getDefaultElement()
        );
    }

    public function testGetType(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Textarea('myid');

        $this->assertEquals(
            'Dotclear\Helper\Html\Form\Textarea',
            $component->getType()
        );
        $this->assertEquals(
            \Dotclear\Helper\Html\Form\Textarea::class,
            $component->getType()
        );
    }

    public function testGetElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Textarea('myid');

        $this->assertEquals(
            'textarea',
            $component->getElement()
        );
    }

    public function testNoIdOutsideLabel(): void
    {
        $component = $this->getMockBuilder(\Dotclear\Helper\Html\Form\Textarea::class)
            ->onlyMethods(['checkMandatoryAttributes'])
            ->enableOriginalConstructor()
            ->getMock();

        $component->method('checkMandatoryAttributes')->willReturn(true);

        $label = new \Dotclear\Helper\Html\Form\Label('mylabel', \Dotclear\Helper\Html\Form\Label::OL_TF);
        $component->attachLabel($label);
        $rendered = $component->render();

        $this->assertStringContainsString(
            '<textarea></textarea>',
            $rendered
        );
    }

    public function testNoIdVerbose(): void
    {
        $component = $this->getMockBuilder(\Dotclear\Helper\Html\Form\Textarea::class)
            ->onlyMethods(['checkMandatoryAttributes', 'isVerbose'])
            ->enableOriginalConstructor()
            ->getMock();

        $component->method('checkMandatoryAttributes')->willReturn(false);
        $component->method('isVerbose')->willReturn(true);

        $rendered = $component->render();

        $this->assertStringContainsString(
            'Textarea without id and name (provide at least one of them)',
            $rendered
        );
    }
}
