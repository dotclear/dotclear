<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Html\Form;

use Dotclear\Helper\Html\Form\Label;
use PHPUnit\Framework\TestCase;

class DivTest extends TestCase
{
    // This class is used to test all generic Component abstract class methods

    public function test(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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

    public function testStatic(): void
    {
        $component = \Dotclear\Helper\Html\Form\Div::init('my');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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

    public function testMagicInvoke(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $rendered  = $component();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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

    public function testWithAnotherHtmlElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my', 'slot');
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<slot.*?>\n<\/slot>/',
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
        $component = new \Dotclear\Helper\Html\Form\Div('my', 'slot');

        $this->assertEquals(
            'div',
            $component->getDefaultElement()
        );
    }

    public function testItemsWithSeparator(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component
            ->separator(' ')
            ->items([
                new \Dotclear\Helper\Html\Form\Para('firstpara'),
                new \Dotclear\Helper\Html\Form\Para('secondpara'),
            ])
        ;
        $rendered = $component->render();

        $this->assertStringContainsString(
            '</p>' . "\n" . ' <p name="secondpara" id="secondpara">',
            $rendered
        );
    }

    public function testItemsWithFormat(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component
            ->format('<div>%s</div>')
            ->items([
                new \Dotclear\Helper\Html\Form\Para('firstpara'),
                new \Dotclear\Helper\Html\Form\Para('secondpara'),
            ])
        ;
        $rendered = $component->render();

        $this->assertStringContainsString(
            '</p>' . "\n" . '</div><div><p name="secondpara" id="secondpara">',
            $rendered
        );
    }

    public function testWithoutNameOrId(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div();
        $rendered  = $component->render();

        $this->assertStringNotContainsString(
            'name=',
            $rendered
        );
        $this->assertStringNotContainsString(
            'id=',
            $rendered
        );
    }

    public function testGetType(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');

        $this->assertEquals(
            'Dotclear\Helper\Html\Form\Div',
            $component->getType()
        );
        $this->assertEquals(
            \Dotclear\Helper\Html\Form\Div::class,
            $component->getType()
        );
    }

    public function testSetType(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->setType(\Dotclear\Helper\Html\Form\Text::class);

        $this->assertEquals(
            'Dotclear\Helper\Html\Form\Text',
            $component->getType()
        );
        $this->assertEquals(
            \Dotclear\Helper\Html\Form\Text::class,
            $component->getType()
        );
    }

    public function testGetElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');

        $this->assertEquals(
            'div',
            $component->getElement()
        );
    }

    public function testSetElement(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->setElement('slot');

        $this->assertEquals(
            'slot',
            $component->getElement()
        );
    }

    public function testAttachLabel(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');

        $label = new \Dotclear\Helper\Html\Form\Label('mylabel');
        $component->attachLabel($label);
        $rendered = $component->label()->render();

        $this->assertEquals(
            '<label>mylabel</label>',
            $rendered
        );
    }

    public function testAttachLabelWithPosition(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');

        $label = new \Dotclear\Helper\Html\Form\Label('mylabel');
        $component->attachLabel($label, Label::IL_TF);
        $rendered = $component->label()->render();

        $this->assertStringContainsString(
            '<label>mylabel</label>',
            $rendered
        );
    }

    public function testAttachNullLabel(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');

        $label = new \Dotclear\Helper\Html\Form\Label('mylabel');
        $component->attachLabel($label);
        $component->attachLabel(null);
        $rendered = $component->render();

        $this->assertStringNotContainsString(
            '<label>mylabel</label>',
            $rendered
        );
    }

    public function testDetachLabel(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');

        $label = new \Dotclear\Helper\Html\Form\Label('mylabel');
        $component->attachLabel($label);
        $component->detachLabel();

        $this->assertNull(
            $component->label()
        );
    }

    public function testNameOnly(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div(['my']);
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
            $rendered
        );
        $this->assertStringContainsString(
            'name="my"',
            $rendered
        );
        $this->assertStringNotContainsString(
            'id="my"',
            $rendered
        );
    }

    public function testNameAndId(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div(['myname', 'myid']);
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
            $rendered
        );
        $this->assertStringContainsString(
            'name="myname"',
            $rendered
        );
        $this->assertStringContainsString(
            'id="myid"',
            $rendered
        );
    }

    public function testIntegerNameAndId(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div([42, 'myid']);
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
            $rendered
        );
        $this->assertStringContainsString(
            'name="42"',
            $rendered
        );
        $this->assertStringContainsString(
            'id="myid"',
            $rendered
        );
    }

    public function testNameAndIntegerId(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div(['myname', 17]);
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
            $rendered
        );
        $this->assertStringContainsString(
            'name="myname"',
            $rendered
        );
        $this->assertStringContainsString(
            'id="17"',
            $rendered
        );
    }

    public function testIntegerNameAndIntegerId(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div([42, 17]);
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
            $rendered
        );
        $this->assertStringContainsString(
            'name="42"',
            $rendered
        );
        $this->assertStringContainsString(
            'id="17"',
            $rendered
        );
    }

    public function testNamedArrayAndId(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div(['myname[]', 'myid']);
        $rendered  = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
            $rendered
        );
        $this->assertStringContainsString(
            'name="myname[]"',
            $rendered
        );
        $this->assertStringContainsString(
            'id="myid"',
            $rendered
        );
    }

    public function testCommonAttributeType(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->type('magic');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
            'type="magic"',
            $rendered
        );
    }

    public function testCommonAttributeValue(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->value('magic');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
            'value="magic"',
            $rendered
        );
    }

    public function testCommonAttributeIntegerValue(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->value(142);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
            'value="142"',
            $rendered
        );
    }

    public function testCommonAttributeNullValue(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->value(null);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
            'value=""',
            $rendered
        );
    }

    public function testCommonAttributeEmptyValue(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->value('');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
            'value=""',
            $rendered
        );
    }

    public function testCommonAttributeZeroValue(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->value(0);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
            'value="0"',
            $rendered
        );
    }

    public function testCommonAttributeFalseValue(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->value(false);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
            'value=""',
            $rendered
        );
    }

    public function testCommonAttributeTrueValue(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->value(true);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
            'value="1"',
            $rendered
        );
    }

    public function testCommonAttributeDefaultValue(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->default('magic');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
            'value="magic"',
            $rendered
        );
    }

    public function testCommonAttributeNoDefaultValue(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->default('magic');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
            'value="magic"',
            $rendered
        );

        $this->assertStringNotContainsString(
            'value="magic"',
            $component->renderCommonAttributes(false)
        );
    }

    public function testCommonAttributeFalseChecked(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->checked(false);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
        $this->assertStringNotContainsString(
            'checked',
            $rendered
        );
    }

    public function testCommonAttributeTrueChecked(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->checked(true);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
            'checked',
            $rendered
        );
    }

    public function testCommonAttributeAccesskey(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->accesskey('s');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
            'accesskey="s"',
            $rendered
        );
    }

    public function testCommonAttributeAutocapitalize(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->autocapitalize('words');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
            'autocapitalize="words"',
            $rendered
        );
    }

    public function testCommonAttributeAutocomplete(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->autocomplete('off');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
            'autocomplete="off"',
            $rendered
        );
    }

    public function testCommonAttributeAutocorrect(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->autocorrect('on');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
            'autocorrect="on"',
            $rendered
        );
    }

    public function testCommonAttributeAutofocus(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->autofocus(true);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
            'autofocus',
            $rendered
        );
    }

    public function testCommonAttributeNoAutofocus(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->autofocus(false);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
        $this->assertStringNotContainsString(
            'autofocus',
            $rendered
        );
    }

    public function testCommonAttributeClass(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->class('myclass');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
            'class="myclass"',
            $rendered
        );
    }

    public function testCommonAttributeClasses(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->class(['myfirstclass', 'mysecondclass', '']);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
            'class="myfirstclass mysecondclass"',
            $rendered
        );
    }

    public function testCommonAttributeEmptyClasses(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->class([]);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
        $this->assertStringNotContainsString(
            'class=',
            $rendered
        );
    }

    public function testCommonAttributeContenteditable(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->contenteditable(true);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
            'contenteditable',
            $rendered
        );
    }

    public function testCommonAttributeNoContenteditable(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->contenteditable(false);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
        $this->assertStringNotContainsString(
            'contenteditable',
            $rendered
        );
    }

    public function testCommonAttributeDir(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->dir('ltr');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
            'dir="ltr"',
            $rendered
        );
    }

    public function testCommonAttributeDisabled(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->disabled(true);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
            'disabled',
            $rendered
        );
    }

    public function testCommonAttributeNoDisabled(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->disabled(false);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
        $this->assertStringNotContainsString(
            'disabled',
            $rendered
        );
    }

    public function testCommonAttributeForm(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->form('myform');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
            'form="myform"',
            $rendered
        );
    }

    public function testCommonAttributeInert(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->inert(true);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
            'inert',
            $rendered
        );
    }

    public function testCommonAttributeNoInert(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->inert(false);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
        $this->assertStringNotContainsString(
            'inert',
            $rendered
        );
    }

    public function testCommonAttributeInputmode(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->inputmode('numeric');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
            'inputmode="numeric"',
            $rendered
        );
    }

    public function testCommonAttributeLang(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->lang('fr');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
            'lang="fr"',
            $rendered
        );
    }

    public function testCommonAttributeList(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->list('mylist');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
            'list="mylist"',
            $rendered
        );
    }

    public function testCommonAttributeMax(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->max(13);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
            'max="13"',
            $rendered
        );
    }

    public function testCommonAttributeZeroMax(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->max(0);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
            'max="0"',
            $rendered
        );
    }

    public function testCommonAttributeNullMax(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->max(null);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
        $this->assertStringNotContainsString(
            'max',
            $rendered
        );
    }

    public function testCommonAttributeStringMax(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->max('13');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
            'max="13"',
            $rendered
        );
    }

    public function testCommonAttributeNegativeMax(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->max(-13);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
            'max="-13"',
            $rendered
        );
    }

    public function testCommonAttributeMaxlength(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->maxlength(13);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
            'maxlength="13"',
            $rendered
        );
    }

    public function testCommonAttributeZeroMaxlength(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->maxlength(0);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
            'maxlength="0"',
            $rendered
        );
    }

    public function testCommonAttributeNullMaxlength(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->maxlength(null);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
        $this->assertStringNotContainsString(
            'maxlength',
            $rendered
        );
    }

    public function testCommonAttributeStringMaxlength(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->maxlength('13');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
            'maxlength="13"',
            $rendered
        );
    }

    public function testCommonAttributeMin(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->min(13);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
            'min="13"',
            $rendered
        );
    }

    public function testCommonAttributeZeroMin(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->min(0);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
            'min="0"',
            $rendered
        );
    }

    public function testCommonAttributeNullMin(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->min(null);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
        $this->assertStringNotContainsString(
            'min',
            $rendered
        );
    }

    public function testCommonAttributeStringMin(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->min('13');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
            'min="13"',
            $rendered
        );
    }

    public function testCommonAttributeNegativeMin(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->min(-13);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
            'min="-13"',
            $rendered
        );
    }

    public function testCommonAttributePattern(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->pattern('HH:MM');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
            'pattern="HH:MM"',
            $rendered
        );
    }

    public function testCommonAttributePlaceholder(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->placeholder('Dotclear');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
            'placeholder="Dotclear"',
            $rendered
        );
    }

    public function testCommonAttributePopover(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->popover(true);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
            'popover',
            $rendered
        );
    }

    public function testCommonAttributeNoPopover(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->popover(false);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
        $this->assertStringNotContainsString(
            'popover',
            $rendered
        );
    }

    public function testCommonAttributeReadonly(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->readonly(true);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
            'readonly',
            $rendered
        );
    }

    public function testCommonAttributeNoReadonly(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->readonly(false);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
        $this->assertStringNotContainsString(
            'readonly',
            $rendered
        );
    }

    public function testCommonAttributeRequired(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->required(true);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
            'required',
            $rendered
        );
    }

    public function testCommonAttributeNoRequired(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->required(false);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
        $this->assertStringNotContainsString(
            'required',
            $rendered
        );
    }

    public function testCommonAttributeRole(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->role('banner');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
            'role="banner"',
            $rendered
        );
    }

    public function testCommonAttributeNoRole(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->role('');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
        $this->assertStringNotContainsString(
            'role',
            $rendered
        );
    }

    public function testCommonAttributeSize(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->size(13);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
            'size="13"',
            $rendered
        );
    }

    public function testCommonAttributeZeroSize(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->size(0);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
            'size="0"',
            $rendered
        );
    }

    public function testCommonAttributeNullSize(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->size(null);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
        $this->assertStringNotContainsString(
            'size',
            $rendered
        );
    }

    public function testCommonAttributeStringSize(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->size('13');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
            'size="13"',
            $rendered
        );
    }

    public function testCommonAttributeSpellcheck(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->spellcheck(true);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
            'spellcheck="true"',
            $rendered
        );
    }

    public function testCommonAttributeNoSpellcheck(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->spellcheck(false);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
            'spellcheck="false"',
            $rendered
        );
    }

    public function testCommonAttributeTabindex(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->tabindex(13);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
            'tabindex="13"',
            $rendered
        );
    }

    public function testCommonAttributeZeroTabindex(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->tabindex(0);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
            'tabindex="0"',
            $rendered
        );
    }

    public function testCommonAttributeNullTabindex(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->tabindex(null);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
        $this->assertStringNotContainsString(
            'tabindex',
            $rendered
        );
    }

    public function testCommonAttributeStringTabindex(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->tabindex('13');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
            'tabindex="13"',
            $rendered
        );
    }

    public function testCommonAttributeNegativeTabindex(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->tabindex(-13);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
            'tabindex="-13"',
            $rendered
        );
    }

    public function testCommonAttributeTitle(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->title('My Title');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
            'title="My Title"',
            $rendered
        );
    }

    public function testCommonAttributeData(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->data([
            'key'  => 'value',
            '0'    => '1',
            'bool' => false,
            'ok'   => true,
            'null' => null,
        ]);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
            'data-key="value"',
            $rendered
        );
        $this->assertStringContainsString(
            'data-0="1"',
            $rendered
        );
        $this->assertStringContainsString(
            'data-bool=""',
            $rendered
        );
        $this->assertStringContainsString(
            'data-ok="1"',
            $rendered
        );
        $this->assertStringContainsString(
            'data-null=""',
            $rendered
        );
    }

    public function testCommonAttributeExtra(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->extra('extra="1"');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
            'extra="1"',
            $rendered
        );
    }

    public function testCommonAttributeExtras(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->extra([
            'extra="1"',
            'bis="2"',
        ]);
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
            'extra="1"',
            $rendered
        );
        $this->assertStringContainsString(
            'bis="2"',
            $rendered
        );
    }

    public function testCommonAttributeUnknown(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->unknown('unknown');
        $rendered = $component->render();

        $this->assertMatchesRegularExpression(
            '/<div.*?>\n<\/div>/',
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
        $this->assertStringNotContainsString(
            'unknown',
            $rendered
        );
    }

    public function testIsVerbose(): void
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');

        $this->assertFalse(
            $component->isVerbose()
        );
    }
}
