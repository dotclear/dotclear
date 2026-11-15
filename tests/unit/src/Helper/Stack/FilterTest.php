<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Stack;

use Exception;
use PHPUnit\Framework\TestCase;

class FilterTest extends TestCase
{
    public function test(): void
    {
        $filter = new \Dotclear\Helper\Stack\Filter('filter_id');

        // Check an unknown property
        $this->assertFalse(
            isset($filter->property_id)
        );
        $this->assertNull(
            $filter->value
        );

        // Set a property with a bad value
        $filter->set('property_id', 42);

        $this->assertFalse(
            isset($filter->property_id)
        );
        $this->assertNull(
            $filter->property_id
        );

        // Set a property with a correct value
        $filter
            ->form('none')
            ->title('title')
            ->options(['one' => 1, 'two' => 2])
            ->value(42)
            ->prime(true)
            ->html('<p></p>')
            ->param('param_one', 1)
            ->param('param_two', 2)
        ;

        $this->assertEquals(
            'html',
            $filter->form
        );
        $this->assertEquals(
            'title',
            $filter->title
        );
        $this->assertEquals(
            [
                'one' => 1,
                'two' => 2,
            ],
            $filter->options
        );
        $this->assertEquals(
            42,
            $filter->value
        );
        $this->assertEquals(
            true,
            $filter->prime
        );
        $this->assertEquals(
            '<p></p>',
            $filter->html
        );
        $this->assertEquals(
            [
                ['param_one', 1],
                ['param_two', 2],
            ],
            $filter->params
        );

        $filter->value = 13;
        $this->assertEquals(
            13,
            $filter->value
        );

        // Param without name
        $filter->param(null, 12);
        $this->assertEquals(
            [
                ['param_one', 1],
                ['param_two', 2],
                ['filter_id', 12],
            ],
            $filter->params
        );

        // Param without value
        $filter->param('param_three');
        $this->assertIsCallable(
            $filter->params[3][1]
        );
    }

    public function testBadId(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('not a valid id');
        $this->assertSame('abc~def', (new \Dotclear\Helper\Stack\Filter('abc~def'))->getId());
    }

    public function testParsingSelect(): void
    {
        $filter = new \Dotclear\Helper\Stack\Filter('filter_id');

        $filter
            ->form('select')
            ->title('title')
            ->options(['one' => 1, 'two' => 2])
        ;

        $this->assertEquals(
            'select',
            $filter->form
        );
        $this->assertEquals(
            'title',
            $filter->title
        );
        $this->assertEquals(
            [
                'one' => 1,
                'two' => 2,
            ],
            $filter->options
        );

        $filter->parse();
        $this->assertEquals(
            '<label for="filter_id" class="ib">title</label> <select name="filter_id" id="filter_id" value="">' . "\n" . '<option value="1">one</option>' . "\n" . '<option value="2">two</option>' . "\n" . '</select>',
            $filter->html
        );
    }

    public function testParsingInput(): void
    {
        $filter = new \Dotclear\Helper\Stack\Filter('filter_id');

        $filter
            ->form('input')
            ->title('title')
            ->value(42)
        ;

        $this->assertEquals(
            'input',
            $filter->form
        );
        $this->assertEquals(
            'title',
            $filter->title
        );
        $this->assertEquals(
            42,
            $filter->value
        );

        $filter->parse();
        $this->assertEquals(
            '<label for="filter_id" class="ib">title</label> <input type="text" name="filter_id" id="filter_id" value="42" maxlength="255" size="25">',
            $filter->html
        );

        $filter->value(null);
        $filter->parse();
        $this->assertEquals(
            '<label for="filter_id" class="ib">title</label> <input type="text" name="filter_id" id="filter_id" value="" maxlength="255" size="25">',
            $filter->html
        );
    }

    public function testGettersAndDynamicSetters(): void
    {
        $filter = new \Dotclear\Helper\Stack\Filter('filter_id', 7);

        $this->assertSame('filter_id', $filter->getId());
        $this->assertSame(7, $filter->getValue());
        $this->assertSame('none', $filter->getFormType());
        $this->assertFalse($filter->getPrime());
        $this->assertSame('', $filter->getTitle());
        $this->assertSame([], $filter->getOptions());
        $this->assertSame([], $filter->getValues());
        $this->assertSame('', $filter->getHtml());
        $this->assertSame([], $filter->getParams());

        $this->assertTrue(isset($filter->id));
        $this->assertTrue(isset($filter->value));
        $this->assertFalse(isset($filter->unknown));
        $this->assertSame($filter, $filter->set('unknown', 'ignored'));

        $filter->title = 'Title';
        $filter->options = ['first' => 'First'];
        $filter->values = ['first'];
        $filter->value = 'first';
        $filter->prime = true;
        $filter->html = '<p>markup</p>';
        $filter->form = 'input';

        $this->assertSame('input', $filter->getFormType());
        $this->assertSame('Title', $filter->getTitle());
        $this->assertSame(['first' => 'First'], $filter->getOptions());
        $this->assertSame(['first'], $filter->getValues());
        $this->assertSame('first', $filter->getValue());
        $this->assertTrue($filter->getPrime());
        $this->assertSame('<p>markup</p>', $filter->getHtml());

        $filter->form('unsupported');
        $filter->options(['second' => 'Second'], false);
        $filter->html('<b>raw</b>', false);

        $this->assertSame('input', $filter->getFormType());
        $this->assertSame(['second' => 'Second'], $filter->getOptions());
        $this->assertSame('<b>raw</b>', $filter->getHtml());

        $nullValueFilter = new \Dotclear\Helper\Stack\Filter('nullable');
        $nullValueFilter->set('value', 'ignored');
        $this->assertNull($nullValueFilter->getValue());
    }

    public function testParamDefaultCallback(): void
    {
        $filter = new \Dotclear\Helper\Stack\Filter('filter_id');
        $filter->param();
        $callback = $filter->getParams()[0][1];

        $this->assertSame('first', $callback(['first', 'second']));
        $this->assertSame('plain', $callback('plain'));
    }

    public function testParsingSelectFromGetAndRejectsUnknownValue(): void
    {
        $originalGet = $_GET;

        try {
            $_GET['filter_id'] = 'two';
            $filter = (new \Dotclear\Helper\Stack\Filter('filter_id'))
                ->form('select')
                ->options(['one' => 'one', 'two' => 'two']);
            $filter->parse();
            $this->assertSame('two', $filter->getValue());

            $_GET['filter_id'] = 'invalid';
            $filter = (new \Dotclear\Helper\Stack\Filter('filter_id'))
                ->form('select')
                ->options(['one' => 'one', 'two' => 'two'])
                ->values(['one', 'two']);
            $filter->parse();
            $this->assertSame('', $filter->getValue());

            $filter->value(['not', 'scalar'])->parse();
            $this->assertStringContainsString('value=""', $filter->getHtml());
        } finally {
            $_GET = $originalGet;
        }
    }

    public function testParsingInputFromGetAndNonScalarValue(): void
    {
        $originalGet = $_GET;

        try {
            $_GET['filter_id'] = '<script>';
            $filter = (new \Dotclear\Helper\Stack\Filter('filter_id'))
                ->form('input')
                ->title('Search');
            $filter->parse();

            $this->assertSame('<script>', $filter->getValue());
            $this->assertStringContainsString('&lt;script&gt;', $filter->getHtml());

            $filter->value(['not', 'scalar'])->parse();
            $this->assertStringContainsString('value=""', $filter->getHtml());

            $filter->html('<p>custom</p>')->form('html')->parse();
            $this->assertSame('<p>custom</p>', $filter->getHtml());
        } finally {
            $_GET = $originalGet;
        }
    }
}
