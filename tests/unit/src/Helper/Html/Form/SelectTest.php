<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Html\Form;

use PHPUnit\Framework\TestCase;

class SelectTest extends TestCase
{
    public function test(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Select('myid');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<select.*?>(?:.*?\n*)?<\/select>/',
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

    public function testItemsText(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Select('myid');
        $component->items([
            'one' => 1,
            'two' => '0',
            'three',
        ]);
        $rendered = $component->render();

        $this->assertStringContainsString(
            '<option value="1">one</option>',
            $rendered
        );
        $this->assertStringContainsString(
            '<option value="0">two</option>',
            $rendered
        );
        $this->assertStringContainsString(
            '<option value="three">0</option>',
            $rendered
        );
    }

    public function testItemsOption(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Select('myid');
        $component->items([
            new \Dotclear\Helper\Html\Form\Option('One', '1'),
            new \Dotclear\Helper\Html\Form\None(),
        ]);
        $rendered = $component->render();

        $this->assertStringContainsString(
            '<option value="1">One</option>',
            $rendered
        );
    }

    public function testItemsSelect(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Select('myid');
        $component->items([
            (new \Dotclear\Helper\Html\Form\Optgroup('First'))->items([
                new \Dotclear\Helper\Html\Form\Option('One', '1'),
            ]),
        ]);
        $rendered = $component->render();

        $this->assertStringContainsString(
            '<optgroup label="First">',
            $rendered
        );
        $this->assertStringContainsString(
            '<option value="1">One</option>',
            $rendered
        );
        $this->assertStringContainsString(
            '</optgroup>' . "\n" . '</select>',
            $rendered
        );
    }

    public function testItemsSelectWithDefault(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Select('myid');
        $component->items([
            (new \Dotclear\Helper\Html\Form\Optgroup('First'))->items([
                new \Dotclear\Helper\Html\Form\Option('One', '1'),
            ]),
        ]);
        $rendered = $component->render('1');

        $this->assertStringContainsString(
            '<optgroup label="First">',
            $rendered
        );
        $this->assertStringContainsString(
            '<option selected value="1">One</option>',
            $rendered
        );
        $this->assertStringContainsString(
            '</optgroup>' . "\n" . '</select>',
            $rendered
        );
    }

    public function testItemsArray(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Select('myid');
        $component->items([
            'First' => [
                'one' => 1,
                'two' => '0',
                'three',
            ],
        ]);
        $rendered = $component->render();

        $this->assertStringContainsString(
            '<optgroup label="First">',
            $rendered
        );
        $this->assertStringContainsString(
            '<option value="1">one</option>',
            $rendered
        );
        $this->assertStringContainsString(
            '<option value="0">two</option>',
            $rendered
        );
        $this->assertStringContainsString(
            '<option value="three">0</option>',
            $rendered
        );
        $this->assertStringContainsString(
            '</optgroup>' . "\n" . '</select>',
            $rendered
        );
    }

    public function testEmptyItems(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Select('myid');
        $component->items([]);
        $rendered = $component->render();

        $this->assertStringNotContainsString(
            '<option',
            $rendered
        );
    }

    public function testWithoutId(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Select();
        $rendered  = $component->render();

        $this->assertEquals(
            '',
            $rendered
        );
    }

    public function testAttachLabel(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Select('my');

        $label = new \Dotclear\Helper\Html\Form\Label('mylabel');
        $component->attachLabel($label);
        $rendered = $component->render();

        $this->assertStringContainsString(
            '<label>mylabel <select name="my" id="my">' . "\n" . '</select></label>',
            $rendered
        );
    }

    public function testAttachLabelOutside(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Select('my');

        $label = new \Dotclear\Helper\Html\Form\Label('mylabel', \Dotclear\Helper\Html\Form\Label::OUTSIDE_LABEL_BEFORE);
        $component->attachLabel($label);
        $rendered = $component->render();

        $this->assertStringContainsString(
            '<label for="my">mylabel</label> <select name="my" id="my">' . "\n" . '</select>',
            $rendered
        );
    }

    public function testAttachLabelButWithoutRendering(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Select('my', null, false);

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
        $component = new \Dotclear\Helper\Html\Form\Select('my');

        $label = new \Dotclear\Helper\Html\Form\Label('mylabel');
        $component->attachLabel($label);
        $component->detachLabel();

        $this->assertNull(
            // @phpstan-ignore arguments.count
            $component->label()
        );
    }

    public function testNoIdOutsideLabel(): void
    {
        $component = $this->getMockBuilder(\Dotclear\Helper\Html\Form\Select::class)
            ->onlyMethods(['checkMandatoryAttributes'])
            ->enableOriginalConstructor()
            ->getMock();

        $component->method('checkMandatoryAttributes')->willReturn(true);

        $label = new \Dotclear\Helper\Html\Form\Label('mylabel', \Dotclear\Helper\Html\Form\Label::OL_TF);
        $component->attachLabel($label);
        $rendered = $component->render();

        $this->assertStringContainsString(
            '<select>' . "\n" . '</select>',
            $rendered
        );
    }

    public function testGetDefaultElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Select('myid');

        $this->assertEquals(
            'select',
            $component->getDefaultElement()
        );
    }

    public function testGetType(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Select('myid');

        $this->assertEquals(
            'Dotclear\Helper\Html\Form\Select',
            $component->getType()
        );
        $this->assertEquals(
            \Dotclear\Helper\Html\Form\Select::class,
            $component->getType()
        );
    }

    public function testGetElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Select('myid');

        $this->assertEquals(
            'select',
            $component->getElement()
        );
    }

    public function testGetElementWithOtherElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Select('myid', 'div');

        $this->assertEquals(
            'div',
            $component->getElement()
        );
    }

    public function testNoIdVerbose(): void
    {
        $component = $this->getMockBuilder(\Dotclear\Helper\Html\Form\Select::class)
            ->onlyMethods(['checkMandatoryAttributes', 'isVerbose'])
            ->enableOriginalConstructor()
            ->getMock();

        $component->method('checkMandatoryAttributes')->willReturn(false);
        $component->method('isVerbose')->willReturn(true);

        $rendered = $component->render();

        $this->assertStringContainsString(
            'Select without id and name (provide at least one of them)',
            $rendered
        );
    }
}
