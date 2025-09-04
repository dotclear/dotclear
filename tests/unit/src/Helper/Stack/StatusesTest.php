<?php

declare(strict_types=1);

namespace Dotclear\Tests\Helper\Stack;

use PHPUnit\Framework\TestCase;

class StatusesTest extends TestCase
{
    public function test(): void
    {
        $statuses = new \Dotclear\Helper\Stack\Statuses('column');

        $this->assertEquals(
            'column',
            $statuses->column()
        );
        $this->assertEquals(
            [],
            $statuses->statuses()
        );
        $this->assertEquals(
            [],
            $statuses->combo()
        );
        $this->assertEquals(
            [],
            $statuses->action()
        );
        $this->assertEquals(
            [],
            $statuses->dump()
        );
        $this->assertEquals(
            [],
            $statuses->dump(false)
        );
        $filter = $statuses->filter();
        $filter->parse();
        $this->assertEquals(
            '<label for="status" class="ib">Status:</label> <select name="status" id="status" value="">' . "\n" . '<option selected value="">-</option>' . "\n" . '</select>',
            $filter->html
        );
        $this->assertEquals(
            '<img src="">',
            $statuses->image('status_one')->render()
        );
        $this->assertEquals(
            '',
            $statuses->image('status_one', true)->render()
        );
        $this->assertEquals(
            0,
            $statuses->threshold()
        );
    }

    public function testStatuses(): void
    {
        $list = [
            (new \Dotclear\Helper\Stack\Status(-1, 'one', 'first', 'firsts', 'icon_one', 'icon_one_dark', false)),
            (new \Dotclear\Helper\Stack\Status(0, 'two', 'second', 'seconds', 'icon_two', 'icon_two_dark', false)),
            (new \Dotclear\Helper\Stack\Status(1, 'three', 'third', 'thirds', 'icon_three', 'icon_three_dark', true)),
        ];
        $statuses = new \Dotclear\Helper\Stack\Statuses('column', $list, 1);

        $this->assertEquals(
            'column',
            $statuses->column()
        );
        $this->assertEquals(
            1,
            $statuses->threshold()
        );
        $this->assertEquals(
            [
                0  => 'second',
                1  => 'third',
                -1 => 'first',
            ],
            $statuses->statuses()
        );
        $this->assertEquals(
            [
                'first'  => -1,
                'second' => 0,
                'third'  => 1,
            ],
            $statuses->combo()
        );
        $this->assertEquals(
            [
                'first'  => 'one',
                'second' => 'two',
            ],
            $statuses->action()
        );
        $this->assertEquals(
            $list,
            $statuses->dump()
        );
        $this->assertEquals(
            array_filter($list, fn ($status) => !$status->hidden()),
            $statuses->dump(false)
        );
        $filter = $statuses->filter();
        $filter->parse();
        $this->assertEquals(
            '<label for="status" class="ib">Status:</label> <select name="status" id="status" value="">' . "\n" . '<option selected value="">-</option>' . "\n" . '<option value="-1">first</option>' . "\n" . '<option value="0">second</option>' . "\n" . '<option value="1">third</option>' . "\n" . '</select>',
            $filter->html
        );
        $this->assertEquals(
            '<img src="icon_one" alt="first" class="mark mark-one light-only"><img src="icon_one_dark" alt="first" class="mark mark-one dark-only">',
            $statuses->image('one')->render()
        );
        $this->assertEquals(
            '<img src="icon_one" alt="first" class="mark mark-one light-only"><img src="icon_one_dark" alt="first" class="mark mark-one dark-only"> first',
            $statuses->image('one', true)->render()
        );
    }

    public function testStatusesVarious(): void
    {
        $list = [
            (new \Dotclear\Helper\Stack\Status(-1, 'one', 'first', 'firsts', 'icon_one', '', false)),
            (new \Dotclear\Helper\Stack\Status(0, 'two', 'second', 'seconds', 'icon_two', 'icon_two_dark', false)),
            (new \Dotclear\Helper\Stack\Status(1, 'three', 'third', 'thirds', 'icon_three', 'icon_three_dark', true)),
            (new \Dotclear\Helper\Stack\Status(2, 'four', 'fourth', 'fourths', 'icon_four', 'icon_four_dark', false)),
        ];
        $statuses = new \Dotclear\Helper\Stack\Statuses('column', $list, 1);

        // Try to set an already known status
        // Same id and same level
        $this->assertFalse(
            $statuses->set((new \Dotclear\Helper\Stack\Status(-1, 'one', 'first', 'firsts', 'icon_one', '', false)))
        );
        // Same id and different levels
        $this->assertFalse(
            $statuses->set((new \Dotclear\Helper\Stack\Status(-2, 'one', 'first', 'firsts', 'icon_one', '', false)))
        );
        // Same level and different ids
        $this->assertFalse(
            $statuses->set((new \Dotclear\Helper\Stack\Status(-1, 'two', 'first', 'firsts', 'icon_one', '', false)))
        );

        // Test default get
        $this->assertEquals(
            $list[2],
            $statuses->get('five')
        );

        // Test restricted
        $this->assertTrue(
            $statuses->isRestricted('one')
        );
        $this->assertTrue(
            $statuses->isRestricted('two')
        );
        $this->assertTrue(
            $statuses->isRestricted('three')
        );
        $this->assertFalse(
            $statuses->isRestricted('four')
        );

        // Test level
        $this->assertEquals(
            1,
            $statuses->level('three')
        );

        // Test id
        $this->assertEquals(
            'three',
            $statuses->id(-2)
        );

        // Test name
        $this->assertEquals(
            'third',
            $statuses->name(-2)
        );

        // Test icon
        $this->assertEquals(
            'icon_four',
            $statuses->icon('four')
        );

        // Test iconDark
        $this->assertEquals(
            'icon_four_dark',
            $statuses->iconDark('four')
        );

        // Test image with no dark icon
        $this->assertEquals(
            '<img src="icon_one" alt="first" class="mark mark-one">',
            $statuses->image('one')->render()
        );
        $this->assertEquals(
            '<img src="icon_one" alt="first" class="mark mark-one"> first',
            $statuses->image('one', true)->render()
        );
    }
}
