<?php

declare(strict_types=1);

namespace Dotclear\Tests\Database;

use Dotclear\Database\StaticRecord;
use PHPUnit\Framework\TestCase;

class StaticRecordEdgeTest extends TestCase
{
    public function testEmptyAndIrregularRows(): void
    {
        $empty = StaticRecord::newFromArray(null);
        $this->assertFalse($empty->set('id', 1));
        $empty->sort('id');
        $empty->lexicalSort('id');
        $this->assertSame([], $empty->rows());

        $scalars = StaticRecord::newFromArray([2, 1]);
        $scalars->lexicalSort('id');
        $this->assertSame([2, 1], $scalars->rows());

        $irregular = StaticRecord::newFromArray([
            ['value' => 1],
            2,
        ]);
        $irregular->sort('value');
        $this->assertSame([['value' => 1], 2], $irregular->rows());

        $missing = StaticRecord::newFromArray([
            ['value' => 'first'],
            ['other' => 'second'],
        ]);
        $missing->sort('value');
        $this->assertSame([['value' => 'first'], ['other' => 'second']], $missing->rows());

        $mixed = StaticRecord::newFromArray([
            ['value' => 2],
            ['value' => 'text'],
        ]);
        $mixed->sort('value');
        $mixed->lexicalSort('value');
        $this->assertSame([['value' => 2], ['value' => 'text']], $mixed->rows());

        $missing = StaticRecord::newFromArray([
            ['id' => 'first'],
            ['other' => 'second'],
        ]);
        $missing->lexicalSort('id');
        $this->assertSame([['id' => 'first'], ['other' => 'second']], $missing->rows());
    }
}
