<?php

declare(strict_types=1);

use Dotclear\Helper\Html\Form\Label;
use PHPUnit\Framework\TestCase;

class DivTest extends TestCase
{
    // This class is used to test all generic Component abstract class methods

    public function test()
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

    public function testStatic()
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

    public function testMagicInvoke()
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

    public function testWithAnotherHtmlElement()
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

    public function testGetDefaultElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my', 'slot');

        $this->assertEquals(
            'div',
            $component->getDefaultElement()
        );
    }

    public function testItemsWithSeparator()
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

    public function testItemsWithFormat()
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

    public function testWithoutNameOrId()
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

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');

        $this->assertEquals(
            'Dotclear\Helper\Html\Form\Div',
            $component->getType()
        );
        $this->assertEquals(
            Dotclear\Helper\Html\Form\Div::class,
            $component->getType()
        );
    }

    public function testSetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->setType(\Dotclear\Helper\Html\Form\Text::class);

        $this->assertEquals(
            'Dotclear\Helper\Html\Form\Text',
            $component->getType()
        );
        $this->assertEquals(
            Dotclear\Helper\Html\Form\Text::class,
            $component->getType()
        );
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');

        $this->assertEquals(
            'div',
            $component->getElement()
        );
    }

    public function testSetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->setElement('slot');

        $this->assertEquals(
            'slot',
            $component->getElement()
        );
    }

    public function testAttachLabel()
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

    public function testAttachLabelWithPosition()
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

    public function testAttachNullLabel()
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

    public function testDetachLabel()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');

        $label = new \Dotclear\Helper\Html\Form\Label('mylabel');
        $component->attachLabel($label);
        $component->detachLabel();

        $this->assertNull(
            $component->label()
        );
    }

    public function testNameOnly()
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

    public function testNameAndId()
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

    public function testIntegerNameAndId()
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

    public function testNameAndIntegerId()
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

    public function testIntegerNameAndIntegerId()
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

    public function testNamedArrayAndId()
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

    public function testCommonAttributeType()
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

    public function testCommonAttributeValue()
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

    public function testCommonAttributeIntegerValue()
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

    public function testCommonAttributeNullValue()
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

    public function testCommonAttributeEmptyValue()
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

    public function testCommonAttributeZeroValue()
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

    public function testCommonAttributeFalseValue()
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

    public function testCommonAttributeTrueValue()
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

    public function testCommonAttributeDefaultValue()
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

    public function testCommonAttributeNoDefaultValue()
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

    public function testCommonAttributeFalseChecked()
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

    public function testCommonAttributeTrueChecked()
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

    public function testCommonAttributeAccesskey()
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

    public function testCommonAttributeAutocapitalize()
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

    public function testCommonAttributeAutocomplete()
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

    public function testCommonAttributeAutocorrect()
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

    public function testCommonAttributeAutofocus()
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

    public function testCommonAttributeNoAutofocus()
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

    public function testCommonAttributeClass()
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

    public function testCommonAttributeClasses()
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

    public function testCommonAttributeEmptyClasses()
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

    public function testCommonAttributeContenteditable()
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

    public function testCommonAttributeNoContenteditable()
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

    public function testCommonAttributeDir()
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

    public function testCommonAttributeDisabled()
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

    public function testCommonAttributeNoDisabled()
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

    public function testCommonAttributeForm()
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

    public function testCommonAttributeInert()
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

    public function testCommonAttributeNoInert()
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

    public function testCommonAttributeInputmode()
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

    public function testCommonAttributeLang()
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

    public function testCommonAttributeList()
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

    public function testCommonAttributeMax()
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

    public function testCommonAttributeZeroMax()
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

    public function testCommonAttributeNullMax()
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

    public function testCommonAttributeStringMax()
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

    public function testCommonAttributeNegativeMax()
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

    public function testCommonAttributeMaxlength()
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

    public function testCommonAttributeZeroMaxlength()
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

    public function testCommonAttributeNullMaxlength()
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

    public function testCommonAttributeStringMaxlength()
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

    public function testCommonAttributeMin()
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

    public function testCommonAttributeZeroMin()
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

    public function testCommonAttributeNullMin()
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

    public function testCommonAttributeStringMin()
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

    public function testCommonAttributeNegativeMin()
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

    public function testCommonAttributePattern()
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

    public function testCommonAttributePlaceholder()
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

    public function testCommonAttributePopover()
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

    public function testCommonAttributeNoPopover()
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

    public function testCommonAttributeReadonly()
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

    public function testCommonAttributeNoReadonly()
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

    public function testCommonAttributeRequired()
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

    public function testCommonAttributeNoRequired()
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

    public function testCommonAttributeRole()
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

    public function testCommonAttributeNoRole()
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

    public function testCommonAttributeSize()
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

    public function testCommonAttributeZeroSize()
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

    public function testCommonAttributeNullSize()
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

    public function testCommonAttributeStringSize()
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

    public function testCommonAttributeSpellcheck()
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

    public function testCommonAttributeNoSpellcheck()
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

    public function testCommonAttributeTabindex()
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

    public function testCommonAttributeZeroTabindex()
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

    public function testCommonAttributeNullTabindex()
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

    public function testCommonAttributeStringTabindex()
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

    public function testCommonAttributeNegativeTabindex()
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

    public function testCommonAttributeTitle()
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

    public function testCommonAttributeData()
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

    public function testCommonAttributeExtra()
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

    public function testCommonAttributeExtras()
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

    public function testCommonAttributeUnknown()
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
}
