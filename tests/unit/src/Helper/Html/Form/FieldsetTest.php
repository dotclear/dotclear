<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Html\Form;

use PHPUnit\Framework\TestCase;

class FieldsetTest extends TestCase
{
    public function test(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Fieldset('my');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<fieldset.*?>(?:.*?\n*)?<\/fieldset>/',
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
        $component = new \Dotclear\Helper\Html\Form\Fieldset('my', 'div');
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

    public function testGetDefaultElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Fieldset('my', 'slot');

        $this->assertEquals(
            'fieldset',
            $component->getDefaultElement()
        );
    }

    public function testGetType(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Fieldset('my');

        $this->assertEquals(
            'Dotclear\Helper\Html\Form\Fieldset',
            $component->getType()
        );
        $this->assertEquals(
            \Dotclear\Helper\Html\Form\Fieldset::class,
            $component->getType()
        );
    }

    public function testGetElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Fieldset('my');

        $this->assertEquals(
            'fieldset',
            $component->getElement()
        );
    }

    public function testGetElementWithOtherElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Fieldset('my', 'div');

        $this->assertEquals(
            'div',
            $component->getElement()
        );
    }

    public function testAttachLegend(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Fieldset();

        $legend = new \Dotclear\Helper\Html\Form\Legend('mylabel');
        $component->attachLegend($legend);
        $rendered = $component->render();

        $this->assertStringContainsString(
            $legend->render(),
            $rendered
        );
    }

    public function testAttachNullLegend(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Fieldset();

        $legend = new \Dotclear\Helper\Html\Form\Legend('mylabel');
        $component->attachLegend($legend);
        $component->attachLegend(null);
        $rendered = $component->render();

        $this->assertStringNotContainsString(
            $legend->render(),
            $rendered
        );
    }

    public function testDetachLegend(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Fieldset();

        $legend = new \Dotclear\Helper\Html\Form\Legend('mylabel');
        $component->attachLegend($legend);
        $component->detachLegend();
        $rendered = $component->render();

        $this->assertStringNotContainsString(
            $legend->render(),
            $rendered
        );
    }

    public function testFields(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Fieldset();

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

    public function testFieldsIncludingLegend(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Fieldset();

        $legend = new \Dotclear\Helper\Html\Form\Legend('mylabel');
        $field  = new \Dotclear\Helper\Html\Form\Input(['myinput']);

        $component->fields([
            $legend,
            $field,
        ]);
        $rendered = $component->render();

        $this->assertStringContainsString(
            $legend->render(),
            $rendered
        );
        $this->assertStringContainsString(
            $field->render(),
            $rendered
        );
    }

    public function testFieldsIncludingAttachedLegend(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Fieldset();

        $legend = new \Dotclear\Helper\Html\Form\Legend('mylabel');
        $component->attachLegend($legend);  // Attached legend will come first before fields

        $field = new \Dotclear\Helper\Html\Form\Input(['myinput']);

        $component->fields([
            $field,
            $legend,
        ]);
        $rendered = $component->render();

        $this->assertStringContainsString(
            $legend->render(),
            $rendered
        );
        $this->assertStringContainsString(
            $field->render(),
            $rendered
        );
        $this->assertStringContainsString(
            $legend->render() . $field->render(),
            $rendered
        );
        $this->assertStringNotContainsString(
            $field->render() . $legend->render(),
            $rendered
        );
    }

    public function testWithoutNameOrId(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Fieldset();
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<fieldset.*?>(?:.*?\n*)?<\/fieldset>/',
            $rendered
        );
    }

    public function testWithoutNameOrIdAndWithAnElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Fieldset(null, 'div');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>(?:.*?\n*)?<\/div>/',
            $rendered
        );
    }
}
