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
        $filter = new \Dotclear\Helper\Stack\Filter('abc~def');
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
            '<label for="filter_id" class="ib">title</label> <input type="text" name="filter_id" id="filter_id" value="42" maxlength="255" size="20">',
            $filter->html
        );

        $filter->value(null);
        $filter->parse();
        $this->assertEquals(
            '<label for="filter_id" class="ib">title</label> <input type="text" name="filter_id" id="filter_id" value="" maxlength="255" size="20">',
            $filter->html
        );
    }
}
