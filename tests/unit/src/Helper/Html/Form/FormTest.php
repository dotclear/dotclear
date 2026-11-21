<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Html\Form;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class FormTest extends TestCase
{
    public function test(): void
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

    public function testWithElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Form('my', 'div');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>(?:.*?\n*)?<\/div>/',
            $rendered
        );
        $this->assertStringNotContainsString(
            'name="my"',
            $rendered
        );
        $this->assertStringContainsString(
            'id="my"',
            $rendered
        );
    }

    public function testGetDefaultElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Form('my', 'slot');

        $this->assertEquals(
            'form',
            $component->getDefaultElement()
        );
    }

    public function testGetType(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Form('my');

        $this->assertEquals(
            'Dotclear\Helper\Html\Form\Form',
            $component->getType()
        );
        $this->assertEquals(
            \Dotclear\Helper\Html\Form\Form::class,
            $component->getType()
        );
    }

    public function testGetElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Form('my');

        $this->assertEquals(
            'form',
            $component->getElement()
        );
    }

    public function testGetElementWithOtherElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Form('my', 'div');

        $this->assertEquals(
            'div',
            $component->getElement()
        );
    }

    public function testFields(): void
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

    public function testWithActionMethodAndEnctype(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Form('my');
        $component
            ->action('/submit')
            ->method('POST')
            ->enctype('multipart/form-data');

        $this->assertSame(
            '<form action="/submit" method="POST" enctype="multipart/form-data" name="my" id="my">' . "\n" . '</form>' . "\n",
            $component->render()
        );
    }

    public function testWithoutNameOrId(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Form();
        $rendered  = $component->render();

        $this->assertEquals(
            '',
            $rendered
        );
    }

    public function testWithoutNameOrIdAndWithAnElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Form(null, 'div');
        $rendered  = $component->render();

        $this->assertEquals(
            '',
            $rendered
        );
    }

    public function testNoIdVerbose(): void
    {
        $component = $this->getVerboseForm(false);

        $rendered = $component->render();

        $this->assertStringContainsString(
            'Form without id and name (provide at least one of them)',
            $rendered
        );
    }

    public function testNoMethodVerbose(): void
    {
        $component = $this->getVerboseForm(true);

        $component->action(null);

        $rendered = $component->render();

        $this->assertStringContainsString(
            'Form without action or method, is this deliberate?',
            $rendered
        );
    }

    private function getVerboseForm(bool $hasMandatoryAttributes): \Dotclear\Helper\Html\Form\Form
    {
        return new class($hasMandatoryAttributes) extends \Dotclear\Helper\Html\Form\Form {
            public function __construct(private bool $hasMandatoryAttributes)
            {
                parent::__construct('my');
            }

            public function checkMandatoryAttributes(): bool
            {
                return $this->hasMandatoryAttributes;
            }

            public function isVerbose(): bool
            {
                return true;
            }
        };
    }
}
