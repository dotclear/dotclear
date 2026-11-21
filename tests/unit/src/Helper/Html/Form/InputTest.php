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

    public function testDefaultElementIsUsedWhenElementIsNull(): void
    {
        $component = $this->createTestInput('my', element: null);

        $this->assertSame(
            '<input type="text" name="my" id="my">',
            $component->render()
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
            // @phpstan-ignore arguments.count
            $component->getLabel()
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

    public function testWithList(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Input('my');

        $this->assertSame($component, $component->list('my-list'));
        $this->assertSame(
            '<input type="text" name="my" id="my" list="my-list">',
            $component->render()
        );
    }

    public function testWithMaxlength(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Input('my');

        $this->assertSame($component, $component->maxlength(32));
        $this->assertSame(
            '<input type="text" name="my" id="my" maxlength="32">',
            $component->render()
        );
    }

    public function testWithPatternAndPlaceholder(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Input('my');
        $component
            ->pattern('[A-Z]{3}')
            ->placeholder('ABC');

        $this->assertSame(
            '<input type="text" name="my" id="my" pattern="[A-Z]{3}" placeholder="ABC">',
            $component->render()
        );
    }

    public function testWithStep(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Input('my');

        $this->assertSame($component, $component->step('0.5'));
        $this->assertSame(
            '<input type="text" name="my" id="my" step="0.5">',
            $component->render()
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
        $component = $this->createTestInput(hasMandatoryAttributes: true);

        $label = new \Dotclear\Helper\Html\Form\Label('mylabel', \Dotclear\Helper\Html\Form\Label::OL_TF);
        $component->attachLabel($label);
        $rendered = $component->render();

        $this->assertStringContainsString(
            '<input type="text">',
            $rendered
        );
    }

    public function testNoIdOutsideLabelAfterInput(): void
    {
        $component = $this->createTestInput(hasMandatoryAttributes: true);
        $component->label(new \Dotclear\Helper\Html\Form\Label('mylabel', \Dotclear\Helper\Html\Form\Label::OL_FT));

        $this->assertSame(
            '<input type="text">',
            $component->render()
        );
    }

    public function testNoIdInsideLabel(): void
    {
        $component = $this->createTestInput(hasMandatoryAttributes: true);
        $component->label(new \Dotclear\Helper\Html\Form\Label('mylabel'));

        $this->assertSame(
            '<label>mylabel <input type="text"></label>',
            $component->render()
        );
    }

    public function testNoIdVerbose(): void
    {
        $component = $this->createTestInput(hasMandatoryAttributes: false, verbose: true);

        $rendered = $component->render();

        $this->assertStringContainsString(
            'Input (type = text) without id and name (provide at least one of them)',
            $rendered
        );
    }

    private function createTestInput(
        ?string $id = null,
        bool $hasMandatoryAttributes = true,
        bool $verbose = false,
        ?string $element = 'input'
    ): \Dotclear\Helper\Html\Form\Input {
        return new class($id, $hasMandatoryAttributes, $verbose, $element) extends \Dotclear\Helper\Html\Form\Input {
            public function __construct(
                ?string $id,
                private bool $hasMandatoryAttributes,
                private bool $verbose,
                private ?string $element
            ) {
                parent::__construct($id);
            }

            public function checkMandatoryAttributes(): bool
            {
                return $this->hasMandatoryAttributes;
            }

            public function isVerbose(): bool
            {
                return $this->verbose;
            }

            public function getElement(): ?string
            {
                return $this->element;
            }
        };
    }
}
