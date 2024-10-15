<?php
/**
 * Unit tests
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */
declare(strict_types=1);

namespace tests\unit\Dotclear\Helper\Html\Form;

require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', '..', 'bootstrap.php']);

use atoum;

class Div extends atoum
{
    // This class is used to test all generic Component abstract class methods

    public function test()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
        ;
    }

    public function testStatic()
    {
        $component = \Dotclear\Helper\Html\Form\Div::init('my');

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
        ;
    }

    public function testMagicInvoke()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');

        $this
            ->string($component())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
        ;
    }

    public function testWithAnotherHtmlElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my', 'slot');

        $this
            ->string($component->render())
            ->match('/<slot.*?>\n<\/slot>/')
            ->contains('name="my"')
            ->contains('id="my"')
        ;
    }

    public function testGetDefaultElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my', 'slot');

        $this
            ->string($component->getDefaultElement())
            ->isEqualTo('div')
        ;
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

        $this
            ->string($component->render())
            ->contains('</p>' . "\n" . ' <p name="secondpara" id="secondpara">')
        ;
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

        $this
            ->string($component->render())
            ->contains('</p>' . "\n" . '</div><div><p name="secondpara" id="secondpara">')
        ;
    }

    public function testWithoutNameOrId()
    {
        $component = new \Dotclear\Helper\Html\Form\Div();

        $this
            ->string($component->render())
            ->notContains('name=')
            ->notContains('id=')
        ;
    }

    public function testGetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');

        $this
            ->string($component->getType())
            ->isEqualTo('Dotclear\Helper\Html\Form\Div')
        ;
    }

    public function testSetType()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->setType(\Dotclear\Helper\Html\Form\Text::class);

        $this
            ->string($component->getType())
            ->isEqualTo('Dotclear\Helper\Html\Form\Text')
        ;
    }

    public function testGetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');

        $this
            ->string($component->getElement())
            ->isEqualTo('div')
        ;
    }

    public function testSetElement()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->setElement('slot');

        $this
            ->string($component->getElement())
            ->isEqualTo('slot')
        ;
    }

    public function testAttachLabel()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');

        $label = new \Dotclear\Helper\Html\Form\Label('mylabel');
        $component->attachLabel($label);

        $this
            ->string($component->label()->render())
            ->isEqualTo('<label>mylabel</label>')
        ;
    }

    public function testAttachNullLabel()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');

        $label = new \Dotclear\Helper\Html\Form\Label('mylabel');
        $component->attachLabel($label);
        $component->attachLabel(null);

        $this
            ->string($component->render())
            ->notContains('<label>mylabel</label>')
        ;
    }

    public function testDetachLabel()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');

        $label = new \Dotclear\Helper\Html\Form\Label('mylabel');
        $component->attachLabel($label);
        $component->detachLabel();

        $this
            ->variable($component->label())
            ->isNull()
        ;
    }

    public function testNameOnly()
    {
        $component = new \Dotclear\Helper\Html\Form\Div(['my']);

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->notContains('id="my"')
        ;
    }

    public function testNameAndId()
    {
        $component = new \Dotclear\Helper\Html\Form\Div(['myname', 'myid']);

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="myname"')
            ->contains('id="myid"')
        ;
    }

    public function testIntegerNameAndId()
    {
        $component = new \Dotclear\Helper\Html\Form\Div([42, 'myid']);

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="42"')
            ->contains('id="myid"')
        ;
    }

    public function testNameAndIntegerId()
    {
        $component = new \Dotclear\Helper\Html\Form\Div(['myname', 17]);

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="myname"')
            ->contains('id="17"')
        ;
    }

    public function testIntegerNameAndIntegerId()
    {
        $component = new \Dotclear\Helper\Html\Form\Div([42, 17]);

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="42"')
            ->contains('id="17"')
        ;
    }

    public function testNamedArrayAndId()
    {
        $component = new \Dotclear\Helper\Html\Form\Div(['myname[]', 'myid']);

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="myname[]"')
            ->contains('id="myid"')
        ;
    }

    public function testCommonAttributeType()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->type('magic');

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('type="magic"')
        ;
    }

    public function testCommonAttributeValue()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->value('magic');

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('value="magic"')
        ;
    }

    public function testCommonAttributeIntegerValue()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->value(142);

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('value="142"')
        ;
    }

    public function testCommonAttributeNullValue()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->value(null);

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('value=""')
        ;
    }

    public function testCommonAttributeEmptyValue()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->value('');

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('value=""')
        ;
    }

    public function testCommonAttributeZeroValue()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->value(0);

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('value="0"')
        ;
    }

    public function testCommonAttributeFalseValue()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->value(false);

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('value=""')
        ;
    }

    public function testCommonAttributeTrueValue()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->value(true);

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('value="1"')
        ;
    }

    public function testCommonAttributeDefaultValue()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->default('magic');

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('value="magic"')
        ;
    }

    public function testCommonAttributeNoDefaultValue()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->default('magic');

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('value="magic"')
        ;

        $this
            ->string($component->renderCommonAttributes(false))
            ->notContains('value="magic"')
        ;
    }

    public function testCommonAttributeFalseChecked()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->checked(false);

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->notContains('checked')
        ;
    }

    public function testCommonAttributeTrueChecked()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->checked(true);

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('checked')
        ;
    }

    public function testCommonAttributeAccesskey()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->accesskey('s');

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('accesskey="s"')
        ;
    }

    public function testCommonAttributeAutocapitalize()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->autocapitalize('words');

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('autocapitalize="words"')
        ;
    }

    public function testCommonAttributeAutocomplete()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->autocomplete('off');

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('autocomplete="off"')
        ;
    }

    public function testCommonAttributeAutocorrect()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->autocorrect('on');

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('autocorrect="on"')
        ;
    }

    public function testCommonAttributeAutofocus()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->autofocus(true);

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('autofocus')
        ;
    }

    public function testCommonAttributeNoAutofocus()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->autofocus(false);

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->notContains('autofocus')
        ;
    }

    public function testCommonAttributeClass()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->class('myclass');

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('class="myclass"')
        ;
    }

    public function testCommonAttributeClasses()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->class(['myfirstclass', 'mysecondclass']);

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('class="myfirstclass mysecondclass"')
        ;
    }

    public function testCommonAttributeEmptyClasses()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->class([]);

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('class=""')
        ;
    }

    public function testCommonAttributeContenteditable()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->contenteditable(true);

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('contenteditable')
        ;
    }

    public function testCommonAttributeNoContenteditable()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->contenteditable(false);

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->notContains('contenteditable')
        ;
    }

    public function testCommonAttributeDir()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->dir('ltr');

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('dir="ltr"')
        ;
    }

    public function testCommonAttributeDisabled()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->disabled(true);

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('disabled')
        ;
    }

    public function testCommonAttributeNoDisabled()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->disabled(false);

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->notContains('disabled')
        ;
    }

    public function testCommonAttributeForm()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->form('myform');

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('form="myform"')
        ;
    }

    public function testCommonAttributeInert()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->inert(true);

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('inert')
        ;
    }

    public function testCommonAttributeNoInert()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->inert(false);

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->notContains('inert')
        ;
    }

    public function testCommonAttributeInputmode()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->inputmode('numeric');

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('inputmode="numeric"')
        ;
    }

    public function testCommonAttributeLang()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->lang('fr');

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('lang="fr"')
        ;
    }

    public function testCommonAttributeList()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->list('mylist');

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('list="mylist"')
        ;
    }

    public function testCommonAttributeMax()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->max(13);

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('max="13"')
        ;
    }

    public function testCommonAttributeZeroMax()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->max(0);

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('max="0"')
        ;
    }

    public function testCommonAttributeNullMax()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->max(null);

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->notContains('max')
        ;
    }

    public function testCommonAttributeStringMax()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->max('13');

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('max="13"')
        ;
    }

    public function testCommonAttributeNegativeMax()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->max(-13);

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('max="-13"')
        ;
    }

    public function testCommonAttributeMaxlength()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->maxlength(13);

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('maxlength="13"')
        ;
    }

    public function testCommonAttributeZeroMaxlength()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->maxlength(0);

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('maxlength="0"')
        ;
    }

    public function testCommonAttributeNullMaxlength()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->maxlength(null);

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->notContains('maxlength')
        ;
    }

    public function testCommonAttributeStringMaxlength()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->maxlength('13');

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('maxlength="13"')
        ;
    }

    public function testCommonAttributeMin()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->min(13);

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('min="13"')
        ;
    }

    public function testCommonAttributeZeroMin()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->min(0);

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('min="0"')
        ;
    }

    public function testCommonAttributeNullMin()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->min(null);

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->notContains('min')
        ;
    }

    public function testCommonAttributeStringMin()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->min('13');

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('min="13"')
        ;
    }

    public function testCommonAttributeNegativeMin()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->min(-13);

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('min="-13"')
        ;
    }

    public function testCommonAttributePattern()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->pattern('HH:MM');

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('pattern="HH:MM"')
        ;
    }

    public function testCommonAttributePlaceholder()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->placeholder('Dotclear');

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('placeholder="Dotclear"')
        ;
    }

    public function testCommonAttributePopover()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->popover(true);

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('popover')
        ;
    }

    public function testCommonAttributeNoPopover()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->popover(false);

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->notContains('popover')
        ;
    }

    public function testCommonAttributeReadonly()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->readonly(true);

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('readonly')
        ;
    }

    public function testCommonAttributeNoReadonly()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->readonly(false);

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->notContains('readonly')
        ;
    }

    public function testCommonAttributeRequired()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->required(true);

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('required')
        ;
    }

    public function testCommonAttributeNoRequired()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->required(false);

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->notContains('required')
        ;
    }

    public function testCommonAttributeRole()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->role('banner');

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('role="banner"')
        ;
    }

    public function testCommonAttributeNoRole()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->role('');

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->notContains('role')
        ;
    }

    public function testCommonAttributeSize()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->size(13);

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('size="13"')
        ;
    }

    public function testCommonAttributeZeroSize()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->size(0);

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('size="0"')
        ;
    }

    public function testCommonAttributeNullSize()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->size(null);

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->notContains('size')
        ;
    }

    public function testCommonAttributeStringSize()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->size('13');

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('size="13"')
        ;
    }

    public function testCommonAttributeSpellcheck()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->spellcheck(true);

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('spellcheck="true"')
        ;
    }

    public function testCommonAttributeNoSpellcheck()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->spellcheck(false);

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('spellcheck="false"')
        ;
    }

    public function testCommonAttributeTabindex()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->tabindex(13);

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('tabindex="13"')
        ;
    }

    public function testCommonAttributeZeroTabindex()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->tabindex(0);

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('tabindex="0"')
        ;
    }

    public function testCommonAttributeNullTabindex()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->tabindex(null);

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->notContains('tabindex')
        ;
    }

    public function testCommonAttributeStringTabindex()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->tabindex('13');

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('tabindex="13"')
        ;
    }

    public function testCommonAttributeNegativeTabindex()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->tabindex(-13);

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('tabindex="-13"')
        ;
    }

    public function testCommonAttributeTitle()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->title('My Title');

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('title="My Title"')
        ;
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

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('data-key="value"')
            ->contains('data-0="1"')
            ->contains('data-bool=""')
            ->contains('data-ok="1"')
            ->contains('data-null=""')
        ;
    }

    public function testCommonAttributeExtra()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->extra('extra="1"');

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('extra="1"')
        ;
    }

    public function testCommonAttributeExtras()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->extra([
            'extra="1"',
            'bis="2"',
        ]);

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->contains('extra="1"')
            ->contains('bis="2"')
        ;
    }

    public function testCommonAttributeUnknown()
    {
        $component = new \Dotclear\Helper\Html\Form\Div('my');
        $component->unknown('unknown');

        $this
            ->string($component->render())
            ->match('/<div.*?>\n<\/div>/')
            ->contains('name="my"')
            ->contains('id="my"')
            ->notContains('unknown')
        ;
    }
}
