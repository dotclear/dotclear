<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Test the form class.
 * formSelectOptions is implicitly tested with testCombo
 */
#[CoversClass(\formSelectOption::class)]
#[CoversClass(\form::class)]
class LegacyTest extends TestCase
{
    public function testOption()
    {
        $component = new \formSelectOption('un', 1, 'classme', 'data-test="This Is A Test"');
        $rendered  = $component->render('0');

        $this->assertMatchesRegularExpression(
            '/<option.*?<\/option>/',
            $rendered
        );
        $this->assertMatchesRegularExpression(
            '/<option\svalue="1".*?>un<\/option>/',
            $rendered
        );

        $rendered = $component->render('1');

        $this->assertMatchesRegularExpression(
            '/<option.*?<\/option>/',
            $rendered
        );
        $this->assertMatchesRegularExpression(
            '/<option.*?value="1".*?>un<\/option>/',
            $rendered
        );
        $this->assertMatchesRegularExpression(
            '/<option.*?selected.*?>un<\/option>/',
            $rendered
        );
    }

    public function testOptionOpt()
    {
        $component = new \formSelectOption('deux', 2);
        $rendered  = $component->render('0');

        $this->assertMatchesRegularExpression(
            '/<option.*?<\/option>/',
            $rendered
        );
        $this->assertMatchesRegularExpression(
            '/<option\svalue="2".*?>deux<\/option>/',
            $rendered
        );

        $rendered = $component->render('2');

        $this->assertMatchesRegularExpression(
            '/<option.*?<\/option>/',
            $rendered
        );
        $this->assertMatchesRegularExpression(
            '/<option.*?value="2".*?>deux<\/option>/',
            $rendered
        );
        $this->assertMatchesRegularExpression(
            '/<option.*?selected.*?>deux<\/option>/',
            $rendered
        );
    }

    /**
     * Create a combo (select)
     */
    public function testCombo()
    {
        $rendered = \form::combo('testID', [], '', 'classme', 'atabindex', true, 'data-test="This Is A Test"');

        $this->assertStringContainsString(
            '<select',
            $rendered
        );
        $this->assertStringContainsString(
            '</select>',
            $rendered
        );
        $this->assertStringContainsString(
            'class="classme"',
            $rendered
        );
        $this->assertStringContainsString(
            'id="testID"',
            $rendered
        );
        $this->assertStringContainsString(
            'name="testID"',
            $rendered
        );
        $this->assertStringContainsString(
            'tabindex="0"',
            $rendered
        );
        $this->assertStringContainsString(
            'disabled',
            $rendered
        );
        $this->assertStringContainsString(
            'data-test="This Is A Test"',
            $rendered
        );

        $rendered = \form::combo('testID', [], '', 'classme', 'atabindex', false, 'data-test="This Is A Test"');

        $this->assertStringNotContainsString(
            'disabled',
            $rendered
        );

        $rendered = \form::combo('testID', ['one', 'two', 'three'], 'one');

        $this->assertMatchesRegularExpression(
            '/<option.*?<\/option>/',
            $rendered
        );
        $this->assertMatchesRegularExpression(
            '/<option\sselected\svalue="one".*?<\/option>/',
            $rendered
        );

        $rendered = \form::combo('testID', [
            new \formSelectOption('Un', 1),
            new \formSelectOption('Deux', 2),
        ]);

        $this->assertMatchesRegularExpression(
            '/<option.*?<\/option>/',
            $rendered
        );
        $this->assertMatchesRegularExpression(
            '/<option\svalue="2">Deux<\/option>/',
            $rendered
        );

        $rendered = \form::combo(['aName', 'anID'], []);

        $this->assertStringContainsString(
            'name="aName"',
            $rendered
        );
        $this->assertStringContainsString(
            'id="anID"',
            $rendered
        );

        $rendered = \form::combo('testID', ['onetwo' => ['one' => 'one', 'two' => 'two']]);

        $this->assertMatchesRegularExpression(
            '/<optgroup\slabel="onetwo">/',
            $rendered
        );
        $this->assertMatchesRegularExpression(
            '/<option\svalue="one">one<\/option>/',
            $rendered
        );
        $this->assertStringContainsString(
            '</optgroup',
            $rendered
        );

        $rendered = \form::combo('testID', [], [
            'tabindex' => 'atabindex',
            'disabled' => true,
        ]);

        $this->assertStringContainsString(
            'tabindex="0"',
            $rendered
        );
        $this->assertStringContainsString(
            'disabled',
            $rendered
        );
    }

    /** Test for <input type="radio"
     */
    public function testRadio()
    {
        $rendered = \form::radio('testID', 'testvalue', true, 'aclassname', 'atabindex', true, 'data-test="A test"');

        $this->assertStringContainsString(
            'type="radio"',
            $rendered
        );
        $this->assertStringContainsString(
            'name="testID"',
            $rendered
        );
        $this->assertStringContainsString(
            'id="testID"',
            $rendered
        );
        $this->assertStringContainsString(
            'checked',
            $rendered
        );
        $this->assertStringContainsString(
            'class="aclassname"',
            $rendered
        );
        $this->assertStringContainsString(
            'tabindex="0"',
            $rendered
        );
        $this->assertStringContainsString(
            'disabled',
            $rendered
        );
        $this->assertStringContainsString(
            'data-test="A test"',
            $rendered
        );

        $rendered = \form::radio(['aName', 'testID'], 'testvalue', true, 'aclassname', 'atabindex', false, 'data-test="A test"');

        $this->assertStringContainsString(
            'name="aName"',
            $rendered
        );
        $this->assertStringContainsString(
            'id="testID"',
            $rendered
        );

        $rendered = \form::radio('testID', 'testvalue', true, 'aclassname', 'atabindex', false, 'data-test="A test"');

        $this->assertStringNotContainsString(
            'disabled',
            $rendered
        );

        $rendered = \form::radio('testID', 'testvalue', [
            'tabindex' => 'atabindex',
            'disabled' => true,
        ]);

        $this->assertStringContainsString(
            'tabindex="0"',
            $rendered
        );
        $this->assertStringContainsString(
            'disabled',
            $rendered
        );
    }

    /** Test for <input type="checkbox"
     */
    public function testCheckbox()
    {
        $rendered = \form::checkbox('testID', 'testvalue', true, 'aclassname', 'atabindex', true, 'data-test="A test"');

        $this->assertStringContainsString(
            'type="checkbox"',
            $rendered
        );
        $this->assertStringContainsString(
            'name="testID"',
            $rendered
        );
        $this->assertStringContainsString(
            'id="testID"',
            $rendered
        );
        $this->assertStringContainsString(
            'checked',
            $rendered
        );
        $this->assertStringContainsString(
            'class="aclassname"',
            $rendered
        );
        $this->assertStringContainsString(
            'tabindex="0"',
            $rendered
        );
        $this->assertStringContainsString(
            'disabled',
            $rendered
        );
        $this->assertStringContainsString(
            'data-test="A test"',
            $rendered
        );

        $rendered = \form::checkbox(['aName', 'testID'], 'testvalue', true, 'aclassname', 'atabindex', false, 'data-test="A test"');

        $this->assertStringContainsString(
            'name="aName"',
            $rendered
        );
        $this->assertStringContainsString(
            'id="testID"',
            $rendered
        );

        $rendered = \form::checkbox('testID', 'testvalue', true, 'aclassname', 'atabindex', false, 'data-test="A test"');

        $this->assertStringNotContainsString(
            'disabled',
            $rendered
        );

        $rendered = \form::checkbox('testID', 'testvalue', [
            'tabindex' => 'atabindex',
            'disabled' => true,
        ]);

        $this->assertStringContainsString(
            'tabindex="0"',
            $rendered
        );
        $this->assertStringContainsString(
            'disabled',
            $rendered
        );
    }

    public function testField()
    {
        $rendered = \form::field('testID', 10, 20, 'testvalue', 'aclassname', 'atabindex', true, 'data-test="A test"', true, autocomplete: 'username');

        $this->assertStringContainsString(
            'type="text"',
            $rendered
        );
        $this->assertStringContainsString(
            'size="10"',
            $rendered
        );
        $this->assertStringContainsString(
            'maxlength="20"',
            $rendered
        );
        $this->assertStringContainsString(
            'name="testID"',
            $rendered
        );
        $this->assertStringContainsString(
            'id="testID"',
            $rendered
        );
        $this->assertStringContainsString(
            'class="aclassname"',
            $rendered
        );
        $this->assertStringContainsString(
            'tabindex="0"',
            $rendered
        );
        $this->assertStringContainsString(
            'disabled',
            $rendered
        );
        $this->assertStringContainsString(
            'data-test="A test"',
            $rendered
        );
        $this->assertStringContainsString(
            'value="testvalue"',
            $rendered
        );
        $this->assertStringContainsString(
            'required',
            $rendered
        );
        $this->assertStringContainsString(
            'autocomplete="username"',
            $rendered
        );

        $rendered = \form::field(['aName', 'testID'], 10, 20, 'testvalue', 'aclassname', 'atabindex', true, 'data-test="A test"', true);

        $this->assertStringContainsString(
            'name="aName"',
            $rendered
        );
        $this->assertStringContainsString(
            'id="testID"',
            $rendered
        );

        $rendered = \form::field('testID', 10, 20, 'testvalue', 'aclassname', 'atabindex', false, 'data-test="A test"', true);

        $this->assertStringNotContainsString(
            'disabled',
            $rendered
        );

        $rendered = \form::field('testID', 10, 20, [
            'tabindex' => 'atabindex',
            'disabled' => true,
        ]);

        $this->assertStringContainsString(
            'tabindex="0"',
            $rendered
        );
        $this->assertStringContainsString(
            'disabled',
            $rendered
        );
    }

    public function testPassword()
    {
        $rendered = \form::password('testID', 10, 20, 'testvalue', 'aclassname', 'atabindex', true, 'data-test="A test"', true, autocomplete: 'password');

        $this->assertStringContainsString(
            'type="password"',
            $rendered
        );
        $this->assertStringContainsString(
            'size="10"',
            $rendered
        );
        $this->assertStringContainsString(
            'maxlength="20"',
            $rendered
        );
        $this->assertStringContainsString(
            'name="testID"',
            $rendered
        );
        $this->assertStringContainsString(
            'id="testID"',
            $rendered
        );
        $this->assertStringContainsString(
            'class="aclassname"',
            $rendered
        );
        $this->assertStringContainsString(
            'tabindex="0"',
            $rendered
        );
        $this->assertStringContainsString(
            'disabled',
            $rendered
        );
        $this->assertStringContainsString(
            'data-test="A test"',
            $rendered
        );
        $this->assertStringContainsString(
            'value="testvalue"',
            $rendered
        );
        $this->assertStringContainsString(
            'required',
            $rendered
        );
        $this->assertStringContainsString(
            'autocomplete="password"',
            $rendered
        );

        $rendered = \form::password(['aName', 'testID'], 10, 20, 'testvalue', 'aclassname', 'atabindex', true, 'data-test="A test"', true);

        $this->assertStringContainsString(
            'name="aName"',
            $rendered
        );
        $this->assertStringContainsString(
            'id="testID"',
            $rendered
        );

        $rendered = \form::password('testID', 10, 20, 'testvalue', 'aclassname', 'atabindex', false, 'data-test="A test"', true);

        $this->assertStringNotContainsString(
            'disabled',
            $rendered
        );

        $rendered = \form::password('testID', 10, 20, [
            'tabindex' => 'atabindex',
            'disabled' => true,
        ]);

        $this->assertStringContainsString(
            'tabindex="0"',
            $rendered
        );
        $this->assertStringContainsString(
            'disabled',
            $rendered
        );
    }

    /**
     * Create a color input field
     */
    public function testColor()
    {
        $rendered = \form::color('testID', 10, 20, '#f369a3', 'aclassname', 'atabindex', true, 'data-test="A test"', true, autocomplete: 'color');

        $this->assertStringContainsString(
            'type="color"',
            $rendered
        );
        $this->assertStringContainsString(
            'size="10"',
            $rendered
        );
        $this->assertStringContainsString(
            'maxlength="20"',
            $rendered
        );
        $this->assertStringContainsString(
            'name="testID"',
            $rendered
        );
        $this->assertStringContainsString(
            'id="testID"',
            $rendered
        );
        $this->assertStringContainsString(
            'class="aclassname"',
            $rendered
        );
        $this->assertStringContainsString(
            'tabindex="0"',
            $rendered
        );
        $this->assertStringContainsString(
            'disabled',
            $rendered
        );
        $this->assertStringContainsString(
            'data-test="A test"',
            $rendered
        );
        $this->assertStringContainsString(
            'value="#f369a3"',
            $rendered
        );
        $this->assertStringContainsString(
            'required',
            $rendered
        );
        $this->assertStringContainsString(
            'autocomplete="color"',
            $rendered
        );

        $rendered = \form::color(['aName', 'testID'], 10, 20, '#f369a3', 'aclassname', 'atabindex', true, 'data-test="A test"', true);

        $this->assertStringContainsString(
            'name="aName"',
            $rendered
        );
        $this->assertStringContainsString(
            'id="testID"',
            $rendered
        );

        $rendered = \form::color('testID', 10, 20, '#f369a3', 'aclassname', 'atabindex', false, 'data-test="A test"', true);

        $this->assertStringNotContainsString(
            'disabled',
            $rendered
        );

        $rendered = \form::color('testID', [
            'tabindex' => 'atabindex',
            'disabled' => true,
        ]);

        $this->assertStringContainsString(
            'size="7"',
            $rendered
        );
        $this->assertStringContainsString(
            'maxlength="7"',
            $rendered
        );
        $this->assertStringContainsString(
            'tabindex="0"',
            $rendered
        );
        $this->assertStringContainsString(
            'disabled',
            $rendered
        );
    }

    /**
     * Create an email input field
     */
    public function testEmail()
    {
        $rendered = \form::email('testID', 10, 20, 'me@example.com', 'aclassname', 'atabindex', true, 'data-test="A test"', true, autocomplete: 'email');

        $this->assertStringContainsString(
            'type="email"',
            $rendered
        );
        $this->assertStringContainsString(
            'size="10"',
            $rendered
        );
        $this->assertStringContainsString(
            'maxlength="20"',
            $rendered
        );
        $this->assertStringContainsString(
            'name="testID"',
            $rendered
        );
        $this->assertStringContainsString(
            'id="testID"',
            $rendered
        );
        $this->assertStringContainsString(
            'class="aclassname"',
            $rendered
        );
        $this->assertStringContainsString(
            'tabindex="0"',
            $rendered
        );
        $this->assertStringContainsString(
            'disabled',
            $rendered
        );
        $this->assertStringContainsString(
            'data-test="A test"',
            $rendered
        );
        $this->assertStringContainsString(
            'value="me@example.com"',
            $rendered
        );
        $this->assertStringContainsString(
            'required',
            $rendered
        );
        $this->assertStringContainsString(
            'autocomplete="email"',
            $rendered
        );

        $rendered = \form::email(['aName', 'testID'], 10, 20, 'me@example.com', 'aclassname', 'atabindex', true, 'data-test="A test"', true);

        $this->assertStringContainsString(
            'name="aName"',
            $rendered
        );
        $this->assertStringContainsString(
            'id="testID"',
            $rendered
        );

        $rendered = \form::email('testID', 10, 20, 'me@example.com', 'aclassname', 'atabindex', false, 'data-test="A test"', true);

        $this->assertStringNotContainsString(
            'disabled',
            $rendered
        );

        $rendered = \form::email('testID', [
            'tabindex' => 'atabindex',
            'disabled' => true,
        ]);

        $this->assertStringContainsString(
            'tabindex="0"',
            $rendered
        );
        $this->assertStringContainsString(
            'disabled',
            $rendered
        );
    }

    /**
     * Create an URL input field
     */
    public function testUrl()
    {
        $rendered = \form::url('testID', 10, 20, 'https://example.com/', 'aclassname', 'atabindex', true, 'data-test="A test"', true, autocomplete: 'url');

        $this->assertStringContainsString(
            'type="url"',
            $rendered
        );
        $this->assertStringContainsString(
            'size="10"',
            $rendered
        );
        $this->assertStringContainsString(
            'maxlength="20"',
            $rendered
        );
        $this->assertStringContainsString(
            'name="testID"',
            $rendered
        );
        $this->assertStringContainsString(
            'id="testID"',
            $rendered
        );
        $this->assertStringContainsString(
            'class="aclassname"',
            $rendered
        );
        $this->assertStringContainsString(
            'tabindex="0"',
            $rendered
        );
        $this->assertStringContainsString(
            'disabled',
            $rendered
        );
        $this->assertStringContainsString(
            'data-test="A test"',
            $rendered
        );
        $this->assertStringContainsString(
            'value="https://example.com/"',
            $rendered
        );
        $this->assertStringContainsString(
            'required',
            $rendered
        );
        $this->assertStringContainsString(
            'autocomplete="url"',
            $rendered
        );

        $rendered = \form::url(['aName', 'testID'], 10, 20, 'https://example.com/', 'aclassname', 'atabindex', true, 'data-test="A test"', true);

        $this->assertStringContainsString(
            'name="aName"',
            $rendered
        );
        $this->assertStringContainsString(
            'id="testID"',
            $rendered
        );

        $rendered = \form::url('testID', 10, 20, 'https://example.com/', 'aclassname', 'atabindex', false, 'data-test="A test"', true);

        $this->assertStringNotContainsString(
            'disabled',
            $rendered
        );

        $rendered = \form::url('testID', [
            'tabindex' => 'atabindex',
            'disabled' => true,
        ]);

        $this->assertStringContainsString(
            'tabindex="0"',
            $rendered
        );
        $this->assertStringContainsString(
            'disabled',
            $rendered
        );
    }

    /**
     * Create a datetime (local) input field
     */
    public function testDatetime()
    {
        $rendered = \form::datetime('testID', 10, 20, '1962-05-13T02:15', 'aclassname', 'atabindex', true, 'data-test="A test"', true, autocomplete: 'datetime');

        $this->assertStringContainsString(
            'type="datetime-local"',
            $rendered
        );
        $this->assertStringContainsString(
            'size="10"',
            $rendered
        );
        $this->assertStringContainsString(
            'maxlength="20"',
            $rendered
        );
        $this->assertStringContainsString(
            'name="testID"',
            $rendered
        );
        $this->assertStringContainsString(
            'id="testID"',
            $rendered
        );
        $this->assertStringContainsString(
            'class="aclassname"',
            $rendered
        );
        $this->assertStringContainsString(
            'tabindex="0"',
            $rendered
        );
        $this->assertStringContainsString(
            'disabled',
            $rendered
        );
        $this->assertStringContainsString(
            'data-test="A test"',
            $rendered
        );
        $this->assertStringContainsString(
            'value="1962-05-13T02:15"',
            $rendered
        );
        $this->assertStringContainsString(
            'required',
            $rendered
        );
        $this->assertStringContainsString(
            'pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}',
            $rendered
        );
        $this->assertStringContainsString(
            'placeholder="1962-05-13T14:45"',
            $rendered
        );
        $this->assertStringContainsString(
            'autocomplete="datetime"',
            $rendered
        );

        $rendered = \form::datetime(['aName', 'testID'], 10, 20, '1962-05-13T02:15', 'aclassname', 'atabindex', true, 'data-test="A test"', true);

        $this->assertStringContainsString(
            'name="aName"',
            $rendered
        );
        $this->assertStringContainsString(
            'id="testID"',
            $rendered
        );

        $rendered = \form::datetime('testID', 10, 20, '1962-05-13T02:15', 'aclassname', 'atabindex', false, 'data-test="A test"', true);

        $this->assertStringNotContainsString(
            'disabled',
            $rendered
        );

        $rendered = \form::datetime('testID', [
            'tabindex' => 'atabindex',
            'disabled' => true,
        ]);

        $this->assertStringContainsString(
            'tabindex="0"',
            $rendered
        );
        $this->assertStringContainsString(
            'disabled',
            $rendered
        );
    }

    /**
     * Create a date input field
     */
    public function testDate()
    {
        $rendered = \form::date('testID', 10, 20, '1962-05-13', 'aclassname', 'atabindex', true, 'data-test="A test"', true, autocomplete: 'date');

        $this->assertStringContainsString(
            'type="date"',
            $rendered
        );
        $this->assertStringContainsString(
            'size="10"',
            $rendered
        );
        $this->assertStringContainsString(
            'maxlength="20"',
            $rendered
        );
        $this->assertStringContainsString(
            'name="testID"',
            $rendered
        );
        $this->assertStringContainsString(
            'id="testID"',
            $rendered
        );
        $this->assertStringContainsString(
            'class="aclassname"',
            $rendered
        );
        $this->assertStringContainsString(
            'tabindex="0"',
            $rendered
        );
        $this->assertStringContainsString(
            'disabled',
            $rendered
        );
        $this->assertStringContainsString(
            'data-test="A test"',
            $rendered
        );
        $this->assertStringContainsString(
            'value="1962-05-13"',
            $rendered
        );
        $this->assertStringContainsString(
            'required',
            $rendered
        );
        $this->assertStringContainsString(
            'pattern="[0-9]{4}-[0-9]{2}-[0-9]{2}',
            $rendered
        );
        $this->assertStringContainsString(
            'placeholder="1962-05-13"',
            $rendered
        );
        $this->assertStringContainsString(
            'autocomplete="date"',
            $rendered
        );

        $rendered = \form::date(['aName', 'testID'], 10, 20, '1962-05-13', 'aclassname', 'atabindex', true, 'data-test="A test"', true);

        $this->assertStringContainsString(
            'name="aName"',
            $rendered
        );
        $this->assertStringContainsString(
            'id="testID"',
            $rendered
        );

        $rendered = \form::date('testID', 10, 20, '1962-05-13', 'aclassname', 'atabindex', false, 'data-test="A test"', true);

        $this->assertStringNotContainsString(
            'disabled',
            $rendered
        );

        $rendered = \form::date('testID', [
            'tabindex' => 'atabindex',
            'disabled' => true,
        ]);

        $this->assertStringContainsString(
            'tabindex="0"',
            $rendered
        );
        $this->assertStringContainsString(
            'disabled',
            $rendered
        );
    }

    /**
     * Create a datetime (local) input field
     */
    public function testTime()
    {
        $rendered = \form::time('testID', 10, 20, '02:15', 'aclassname', 'atabindex', true, 'data-test="A test"', true, autocomplete: 'time');

        $this->assertStringContainsString(
            'type="time"',
            $rendered
        );
        $this->assertStringContainsString(
            'size="10"',
            $rendered
        );
        $this->assertStringContainsString(
            'maxlength="20"',
            $rendered
        );
        $this->assertStringContainsString(
            'name="testID"',
            $rendered
        );
        $this->assertStringContainsString(
            'id="testID"',
            $rendered
        );
        $this->assertStringContainsString(
            'class="aclassname"',
            $rendered
        );
        $this->assertStringContainsString(
            'tabindex="0"',
            $rendered
        );
        $this->assertStringContainsString(
            'disabled',
            $rendered
        );
        $this->assertStringContainsString(
            'data-test="A test"',
            $rendered
        );
        $this->assertStringContainsString(
            'value="02:15"',
            $rendered
        );
        $this->assertStringContainsString(
            'required',
            $rendered
        );
        $this->assertStringContainsString(
            'pattern="[0-9]{2}:[0-9]{2}',
            $rendered
        );
        $this->assertStringContainsString(
            'placeholder="14:45"',
            $rendered
        );
        $this->assertStringContainsString(
            'autocomplete="time"',
            $rendered
        );

        $rendered = \form::time(['aName', 'testID'], 10, 20, '02:15', 'aclassname', 'atabindex', true, 'data-test="A test"', true);

        $this->assertStringContainsString(
            'name="aName"',
            $rendered
        );
        $this->assertStringContainsString(
            'id="testID"',
            $rendered
        );

        $rendered = \form::time('testID', 10, 20, '02:15', 'aclassname', 'atabindex', false, 'data-test="A test"', true);

        $this->assertStringNotContainsString(
            'disabled',
            $rendered
        );

        $rendered = \form::time('testID', [
            'tabindex' => 'atabindex',
            'disabled' => true,
        ]);

        $this->assertStringContainsString(
            'tabindex="0"',
            $rendered
        );
        $this->assertStringContainsString(
            'disabled',
            $rendered
        );
    }

    /**
     * Create a file input field
     */
    public function testFile()
    {
        $rendered = \form::file('testID', 'filename.ext', 'aclassname', 'atabindex', true, 'data-test="A test"', true);

        $this->assertStringContainsString(
            'type="file"',
            $rendered
        );
        $this->assertStringContainsString(
            'name="testID"',
            $rendered
        );
        $this->assertStringContainsString(
            'id="testID"',
            $rendered
        );
        $this->assertStringContainsString(
            'class="aclassname"',
            $rendered
        );
        $this->assertStringContainsString(
            'tabindex="0"',
            $rendered
        );
        $this->assertStringContainsString(
            'disabled',
            $rendered
        );
        $this->assertStringContainsString(
            'data-test="A test"',
            $rendered
        );
        $this->assertStringContainsString(
            'value="filename.ext"',
            $rendered
        );
        $this->assertStringContainsString(
            'required',
            $rendered
        );

        $rendered = \form::file(['aName', 'testID'], 'filename.ext', 'aclassname', 'atabindex', true, 'data-test="A test"', true);

        $this->assertStringContainsString(
            'name="aName"',
            $rendered
        );
        $this->assertStringContainsString(
            'id="testID"',
            $rendered
        );

        $rendered = \form::file('testID', 'filename.ext', 'aclassname', 'atabindex', false, 'data-test="A test"', true);

        $this->assertStringNotContainsString(
            'disabled',
            $rendered
        );

        $rendered = \form::file('testID', [
            'tabindex' => 'atabindex',
            'disabled' => true,
        ]);

        $this->assertStringContainsString(
            'tabindex="0"',
            $rendered
        );
        $this->assertStringContainsString(
            'disabled',
            $rendered
        );
    }

    public function testNumber()
    {
        $rendered = \form::number('testID', 0, 99, '13', 'aclassname', 'atabindex', true, 'data-test="A test"', true, autocomplete: 'number');

        $this->assertStringContainsString(
            'type="number"',
            $rendered
        );
        $this->assertStringContainsString(
            'min="0"',
            $rendered
        );
        $this->assertStringContainsString(
            'max="99"',
            $rendered
        );
        $this->assertStringContainsString(
            'name="testID"',
            $rendered
        );
        $this->assertStringContainsString(
            'id="testID"',
            $rendered
        );
        $this->assertStringContainsString(
            'class="aclassname"',
            $rendered
        );
        $this->assertStringContainsString(
            'tabindex="0"',
            $rendered
        );
        $this->assertStringContainsString(
            'disabled',
            $rendered
        );
        $this->assertStringContainsString(
            'data-test="A test"',
            $rendered
        );
        $this->assertStringContainsString(
            'value="13"',
            $rendered
        );
        $this->assertStringContainsString(
            'required',
            $rendered
        );
        $this->assertStringContainsString(
            'autocomplete="number"',
            $rendered
        );

        $rendered = \form::number(['aName', 'testID'], 0, 99, '13', 'aclassname', 'atabindex', true, 'data-test="A test"', true);

        $this->assertStringContainsString(
            'name="aName"',
            $rendered
        );
        $this->assertStringContainsString(
            'id="testID"',
            $rendered
        );

        $rendered = \form::number('testID', 0, 99, '13', 'aclassname', 'atabindex', false, 'data-test="A test"', true);

        $this->assertStringNotContainsString(
            'disabled',
            $rendered
        );

        $rendered = \form::number('testID', [
            'tabindex' => 'atabindex',
            'disabled' => true,
        ]);

        $this->assertStringNotContainsString(
            'min=',
            $rendered
        );
        $this->assertStringNotContainsString(
            'max=',
            $rendered
        );
        $this->assertStringContainsString(
            'tabindex="0"',
            $rendered
        );
        $this->assertStringContainsString(
            'disabled',
            $rendered
        );
    }

    public function testTextArea()
    {
        $rendered = \form::textArea('testID', 10, 20, 'testvalue', 'aclassname', 'atabindex', true, 'data-test="A test"', true, autocomplete: 'none');

        $this->assertMatchesRegularExpression(
            '/<textarea.*?testvalue.*?<\/textarea>/s',
            $rendered
        );
        $this->assertStringContainsString(
            'cols="10"',
            $rendered
        );
        $this->assertStringContainsString(
            'rows="20"',
            $rendered
        );
        $this->assertStringContainsString(
            'name="testID"',
            $rendered
        );
        $this->assertStringContainsString(
            'id="testID"',
            $rendered
        );
        $this->assertStringContainsString(
            'class="aclassname"',
            $rendered
        );
        $this->assertStringContainsString(
            'tabindex="0"',
            $rendered
        );
        $this->assertStringContainsString(
            'disabled',
            $rendered
        );
        $this->assertStringContainsString(
            'data-test="A test"',
            $rendered
        );
        $this->assertStringContainsString(
            'required',
            $rendered
        );
        $this->assertStringContainsString(
            'autocomplete="none"',
            $rendered
        );

        $rendered = \form::textArea(['aName', 'testID'], 10, 20, 'testvalue', 'aclassname', 'atabindex', true, 'data-test="A test"', true);

        $this->assertStringContainsString(
            'name="aName"',
            $rendered
        );
        $this->assertStringContainsString(
            'id="testID"',
            $rendered
        );

        $rendered = \form::textArea('testID', 10, 20, 'testvalue', 'aclassname', 'atabindex', false, 'data-test="A test"', true);

        $this->assertStringNotContainsString(
            'disabled',
            $rendered
        );

        $rendered = \form::textArea('testID', 10, 20, [
            'tabindex' => 'atabindex',
            'disabled' => true,
        ]);

        $this->assertStringContainsString(
            'tabindex="0"',
            $rendered
        );
        $this->assertStringContainsString(
            'disabled',
            $rendered
        );
    }

    public function testHidden()
    {
        $rendered = \form::hidden('testID', 'testvalue');

        $this->assertStringContainsString(
            'type="hidden"',
            $rendered
        );
        $this->assertStringContainsString(
            'name="testID"',
            $rendered
        );
        $this->assertStringContainsString(
            'id="testID"',
            $rendered
        );
        $this->assertStringContainsString(
            'value="testvalue"',
            $rendered
        );

        $rendered = \form::hidden(['aName', 'testID'], 'testvalue');

        $this->assertStringContainsString(
            'name="aName"',
            $rendered
        );
        $this->assertStringContainsString(
            'id="testID"',
            $rendered
        );
    }
}
