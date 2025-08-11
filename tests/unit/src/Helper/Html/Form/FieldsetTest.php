<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class FieldsetTest extends TestCase
{
    public function test()
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

    public function testWithElement()
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

    public function testGetDefaultElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Fieldset('my', 'slot');

        $this->assertEquals(
            'fieldset',
            $component->getDefaultElement()
        );
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Fieldset('my');

        $this->assertEquals(
            'Dotclear\Helper\Html\Form\Fieldset',
            $component->getType()
        );
        $this->assertEquals(
            Dotclear\Helper\Html\Form\Fieldset::class,
            $component->getType()
        );
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Fieldset('my');

        $this->assertEquals(
            'fieldset',
            $component->getElement()
        );
    }

    public function testGetElementWithOtherElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Fieldset('my', 'div');

        $this->assertEquals(
            'div',
            $component->getElement()
        );
    }

    public function testAttachLegend()
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

    public function testAttachNullLegend()
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

    public function testDetachLegend()
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

    public function testFields()
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

    public function testFieldsIncludingLegend()
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

    public function testFieldsIncludingAttachedLegend()
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

    public function testWithoutNameOrId()
    {
        $component = new \Dotclear\Helper\Html\Form\Fieldset();
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<fieldset.*?>(?:.*?\n*)?<\/fieldset>/',
            $rendered
        );
    }

    public function testWithoutNameOrIdAndWithAnElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Fieldset(null, 'div');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>(?:.*?\n*)?<\/div>/',
            $rendered
        );
    }
}
