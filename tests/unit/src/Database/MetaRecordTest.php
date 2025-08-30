<?php

declare(strict_types=1);

namespace Dotclear\Tests\Database;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MetaRecordExtend
{
    public static function isEditable(\Dotclear\Database\MetaRecord $rs): bool
    {
        return ($rs->index() === 0);
    }
}

class MetaRecordTest extends TestCase
{
    private function getConnection(string $driver, string $syntax): MockObject
    {
        // Build a mock handler for the driver
        $driverClass  = ucfirst($driver);
        $handlerClass = implode('\\', ['Dotclear', 'Schema', 'Database', $driverClass, 'Handler']);
        // @phpstan-ignore argument.templateType, argument.type
        $mock = $this->getMockBuilder($handlerClass)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'link',
                'select',
                'openCursor',
                'changes',
                'vacuum',
                'escapeStr',
                'driver',
                'syntax',
                'db_result_seek',
                'db_fetch_assoc',
            ])
            ->getMock();

        // Common return values

        $info = [
            'con'  => $mock,
            'cols' => 0,
            'rows' => 0,
            'info' => [
                'name' => [],
                'type' => [],
            ],
        ];

        $mock->method('link')->willReturn($mock);
        $mock->method('select')->willReturn(
            $driver !== 'sqlite' ?
            // @phpstan-ignore argument.type
            new \Dotclear\Database\Record([], $info) :
            // @phpstan-ignore argument.type
            new \Dotclear\Database\StaticRecord([], $info)
        );
        // @phpstan-ignore argument.type
        $mock->method('openCursor')->willReturn(new \Dotclear\Database\Cursor($mock, 'dc_table'));
        $mock->method('changes')->willReturn(1);
        $mock->method('escapeStr')->willReturnCallback(fn ($str) => addslashes((string) $str));
        $mock->method('driver')->willReturn($driver);
        $mock->method('syntax')->willReturn($syntax);

        return $mock;
    }

    /**
     * @param  array<array-key, mixed>  &$rows
     * @param  array{con: mixed, cols: int, rows: int, info: array{name: list<string>, type: list<string>}}  &$info
     */
    private function createRecord(string $driver, string $syntax, ?array &$rows, array &$info, bool &$valid, int &$pointer, bool $static = false): \Dotclear\Database\MetaRecord
    {
        $con = $this->getConnection($driver, $syntax);

        $info['con'] = $con;

        $con->method('db_result_seek')->willReturnCallback(function ($res, $row) use (&$valid, &$pointer) {
            $valid   = ($row >= 0 && $row < 2);
            $pointer = $valid ? $row : 0;

            return $valid;
        });

        $con->method('db_fetch_assoc')->willReturnCallback(function ($res) use (&$valid, &$pointer, $rows) {
            // @phpstan-ignore offsetAccess.notFound
            $ret = $valid ? $rows[$pointer] : false;
            $pointer++;
            $valid   = ($pointer >= 0 && $pointer < 2);
            $pointer = $valid ? $pointer : 0;

            return $ret;
        });

        $record = new \Dotclear\Database\MetaRecord(
            $static ?
            // @phpstan-ignore argument.type
            new \Dotclear\Database\StaticRecord($rows, $info) :
            // @phpstan-ignore argument.type
            new \Dotclear\Database\Record($rows, $info)
        );

        return $record;
    }

    protected function setUp(): void
    {
        set_error_handler(static function (int $errno, string $errstr): never {
            throw new Exception($errstr, $errno);
        }, E_USER_WARNING);
    }

    protected function tearDown(): void
    {
        restore_error_handler();
    }

    #[DataProvider('dataProviderTest')]
    public function test(string $driver, string $syntax): void
    {
        // Sample data
        $rows = [
            [
                'Name' => 'Dotclear',
                'Town' => 'Paris',
                'Age'  => 42,
            ],
            [
                'Name' => 'Wordpress',
                'Town' => 'Chicago',
                'Age'  => 13,
            ],
        ];

        // Mock db_result_seek and db_fetch_assoc
        $valid   = true;
        $pointer = 0;

        $info = [
            'con'  => null,
            'cols' => 3,
            'rows' => 2,
            'info' => [
                'name' => [
                    'Name',
                    'Town',
                    'Age',
                ],
                'type' => [
                    'string',
                    'string',
                    'int',
                ],
            ],
        ];

        $record = $this->createRecord($driver, $syntax, $rows, $info, $valid, $pointer);

        // Initial index
        $this->assertEquals(
            0,
            $record->index()
        );

        // First row
        $this->assertTrue(
            $record->fetch()
        );
        $this->assertEquals(
            0,
            $record->index()
        );

        // Second row
        $this->assertTrue(
            $record->fetch()
        );
        $this->assertEquals(
            1,
            $record->index()
        );

        // Back to beginning
        $record->next();
        $this->assertEquals(
            0,
            $record->key()
        );
        $this->assertFalse(
            $record->valid()
        );

        // Rewind to start
        $record->rewind();
        $this->assertEquals(
            0,
            $record->index()
        );
        $this->assertTrue(
            $record->valid()
        );

        // Fields
        $this->assertEquals(
            'Dotclear',
            $record->f('Name')
        );
        $this->assertFalse(
            $record->exists('name')
        );
        $this->assertNull(
            $record->f('name')
        );

        // Info
        $this->assertEquals(
            2,
            $record->count()
        );
        $this->assertEquals(
            [
                'Name',
                'Town',
                'Age',
            ],
            $record->columns()
        );
        $this->assertFalse(
            $record->isEmpty()
        );

        // Various
        $record->rewind();

        $this->assertEquals(
            [
                'Name' => 'Dotclear',
                'Town' => 'Paris',
                'Age'  => 42,
                'Dotclear',
                'Paris',
                42,
            ],
            $record->row()
        );
        $this->assertEquals(
            $record,
            $record->current()
        );
        $this->assertEquals(
            'Dotclear',
            $record->Name
        );
        $this->assertTrue(
            isset($record->Age)
        );

        // Moves
        $record->moveEnd();
        $this->assertEquals(
            1,
            $record->index()
        );
        $this->assertTrue(
            $record->isEnd()
        );
        $this->assertFalse(
            $record->isStart()
        );
        $record->moveNext();
        $this->assertEquals(
            1,
            $record->index()
        );
        $this->assertTrue(
            $record->isEnd()
        );
        $this->assertFalse(
            $record->isStart()
        );
        $record->moveStart();
        $this->assertEquals(
            0,
            $record->index()
        );
        $this->assertFalse(
            $record->isEnd()
        );
        $this->assertTrue(
            $record->isStart()
        );
        $record->moveNext();
        $this->assertEquals(
            1,
            $record->index()
        );
        $record->moveNext();
        $this->assertEquals(
            1,
            $record->index()
        );
        $record->movePrev();
        $this->assertEquals(
            0,
            $record->index()
        );
        $record->movePrev();
        $this->assertEquals(
            0,
            $record->index()
        );

        // Test some specific static record methods
        $this->assertFalse(
            $record->hasStatic()
        );
        $this->assertTrue(
            $record->hasDynamic()
        );

        $record->rewind();
        $record->set('Age', 43);    // Should not be taken into account on dynamic record only

        $this->assertEquals(
            42,
            $record->Age
        );
    }

    #[DataProvider('dataProviderTest')]
    public function testToStatic(string $driver, string $syntax): void
    {
        // Sample data
        $rows = [
            [
                'Name' => 'Dotclear',
                'Town' => 'Paris',
                'Age'  => 42,
            ],
            [
                'Name' => 'Wordpress',
                'Town' => 'Chicago',
                'Age'  => 13,
            ],
        ];

        // Mock db_result_seek and db_fetch_assoc
        $valid   = true;
        $pointer = 0;

        $info = [
            'con'  => null,
            'cols' => 3,
            'rows' => 2,
            'info' => [
                'name' => [
                    'Name',
                    'Town',
                    'Age',
                ],
                'type' => [
                    'string',
                    'string',
                    'int',
                ],
            ],
        ];

        $record = $this->createRecord($driver, $syntax, $rows, $info, $valid, $pointer);

        $static = $record->toStatic();
        $double = $static->toExtStatic();

        // Info
        $this->assertTrue(
            $record->hasStatic()
        );
        $this->assertTrue(
            $record->hasDynamic()
        );
        $this->assertEquals(
            2,
            $static->count()
        );
        $this->assertEquals(
            $static,
            $double
        );
        ;
    }

    #[DataProvider('dataProviderTest')]
    public function testExtend(string $driver, string $syntax): void
    {
        // Sample data
        $rows = [
            [
                'Name' => 'Dotclear',
                'Town' => 'Paris',
                'Age'  => 42,
            ],
            [
                'Name' => 'Wordpress',
                'Town' => 'Chicago',
                'Age'  => 13,
            ],
        ];

        // Mock db_result_seek and db_fetch_assoc
        $valid   = true;
        $pointer = 0;

        $info = [
            'con'  => null,
            'cols' => 3,
            'rows' => 2,
            'info' => [
                'name' => [
                    'Name',
                    'Town',
                    'Age',
                ],
                'type' => [
                    'string',
                    'string',
                    'int',
                ],
            ],
        ];

        $record = $this->createRecord($driver, $syntax, $rows, $info, $valid, $pointer);

        // Initial index
        $this->assertEquals(
            0,
            $record->index()
        );

        // First row
        $this->assertTrue(
            $record->fetch()
        );
        $this->assertEquals(
            0,
            $record->index()
        );

        // Extend
        $record->extend(\Dotclear\Tests\Database\MetaRecordExtend::class);

        $this->assertEquals(
            [
                'isEditable' => [
                    \Dotclear\Tests\Database\MetaRecordExtend::class,
                    'isEditable',
                ],
            ],
            $record->extensions()
        );
        $this->assertTrue(
            $record->isEditable()
        );

        // Second row
        $this->assertTrue(
            $record->fetch()
        );
        $this->assertEquals(
            1,
            $record->index()
        );
        $this->assertFalse(
            $record->isEditable()
        );

        // Rewind to start
        $record->rewind();
        $this->assertEquals(
            0,
            $record->index()
        );
        $this->assertTrue(
            $record->valid()
        );
        $this->assertTrue(
            $record->isEditable()
        );

        // Extend error

        $record->extend('unknown');

        $this->assertEquals(
            1,
            count($record->extensions())
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Call to undefined method unknown()');

        $record->unknown();
    }

    #[DataProvider('dataProviderTest')]
    public function testRows(string $driver, string $syntax): void
    {
        // Sample data
        $rows = [
            [
                'Name' => 'Dotclear',
                'Town' => 'Paris',
                'Age'  => 42,
            ],
            [
                'Name' => 'Wordpress',
                'Town' => 'Chicago',
                'Age'  => 13,
            ],
        ];

        // Mock db_result_seek and db_fetch_assoc
        $valid   = true;
        $pointer = 0;

        $info = [
            'con'  => null,
            'cols' => 3,
            'rows' => 2,
            'info' => [
                'name' => [
                    'Name',
                    'Town',
                    'Age',
                ],
                'type' => [
                    'string',
                    'string',
                    'int',
                ],
            ],
        ];

        $record = $this->createRecord($driver, $syntax, $rows, $info, $valid, $pointer);

        // Rows/GetData
        $this->assertEquals(
            2,
            $record->count()
        );
        $this->assertEquals(
            [
                [
                    'Name' => 'Dotclear',
                    'Town' => 'Paris',
                    'Age'  => 42,
                    'Dotclear',
                    'Paris',
                    42,
                ],
                [
                    'Name' => 'Wordpress',
                    'Town' => 'Chicago',
                    'Age'  => 13,
                    'Wordpress',
                    'Chicago',
                    13,
                ],
            ],
            $record->rows()
        );
    }

    #[DataProvider('dataProviderTest')]
    public function testStatic(string $driver, string $syntax): void
    {
        // Sample data
        $rows = [
            [
                'Name' => 'Dotclear',
                'Town' => 'Paris',
                'Age'  => 42,
            ],
            [
                'Name' => 'Wordpress',
                'Town' => 'Chicago',
                'Age'  => 13,
            ],
        ];

        // Mock db_result_seek and db_fetch_assoc
        $valid   = true;
        $pointer = 0;

        $info = [
            'con'  => null,
            'cols' => 3,
            'rows' => 2,
            'info' => [
                'name' => [
                    'Name',
                    'Town',
                    'Age',
                ],
                'type' => [
                    'string',
                    'string',
                    'int',
                ],
            ],
        ];

        $record = $this->createRecord($driver, $syntax, $rows, $info, $valid, $pointer, true);

        $this->assertTrue(
            $record->hasStatic()
        );
        $this->assertFalse(
            $record->hasDynamic()
        );
        $this->assertEquals(
            2,
            $record->count()
        );

        // Initial index
        $this->assertEquals(
            0,
            $record->index()
        );

        // First row
        $this->assertTrue(
            $record->fetch()
        );
        $this->assertEquals(
            0,
            $record->index()
        );

        // Second row
        $this->assertTrue(
            $record->fetch()
        );
        $this->assertEquals(
            1,
            $record->index()
        );

        // Back to beginning
        $record->next();
        $this->assertEquals(
            0,
            $record->key()
        );
        $this->assertFalse(
            $record->valid()
        );

        // Rewind to start
        $record->rewind();
        $this->assertEquals(
            0,
            $record->index()
        );
        $this->assertTrue(
            $record->valid()
        );

        // Fields
        $this->assertEquals(
            'Dotclear',
            $record->f('Name')
        );
        $this->assertFalse(
            $record->exists('name')
        );
        $this->assertNull(
            $record->f('name')
        );

        // Info
        $this->assertEquals(
            2,
            $record->count()
        );
        $this->assertEquals(
            [
                'Name',
                'Town',
                'Age',
            ],
            $record->columns()
        );
        $this->assertFalse(
            $record->isEmpty()
        );

        // Various
        $record->rewind();

        $this->assertEquals(
            [
                'Name' => 'Dotclear',
                'Town' => 'Paris',
                'Age'  => 42,
            ],
            $record->row()
        );
        $this->assertEquals(
            $record,
            $record->current()
        );
        $this->assertEquals(
            'Dotclear',
            $record->Name
        );

        // Moves
        $record->moveEnd();
        $this->assertEquals(
            1,
            $record->index()
        );
        $this->assertTrue(
            $record->isEnd()
        );
        $this->assertFalse(
            $record->isStart()
        );
        $record->moveNext();
        $this->assertEquals(
            1,
            $record->index()
        );
        $this->assertTrue(
            $record->isEnd()
        );
        $this->assertFalse(
            $record->isStart()
        );
        $record->moveStart();
        $this->assertEquals(
            0,
            $record->index()
        );
        $this->assertFalse(
            $record->isEnd()
        );
        $this->assertTrue(
            $record->isStart()
        );
        $record->moveNext();
        $this->assertEquals(
            1,
            $record->index()
        );
        $record->moveNext();
        $this->assertEquals(
            1,
            $record->index()
        );
        $record->movePrev();
        $this->assertEquals(
            0,
            $record->index()
        );
        $record->movePrev();
        $this->assertEquals(
            0,
            $record->index()
        );

        // Extend
        $record->extend(\Dotclear\Tests\Database\MetaRecordExtend::class);

        $this->assertEquals(
            [
                'isEditable' => [
                    \Dotclear\Tests\Database\MetaRecordExtend::class,
                    'isEditable',
                ],
            ],
            $record->extensions()
        );
        $this->assertTrue(
            $record->isEditable()
        );

        // Info
        $this->assertEquals(
            2,
            $record->count()
        );
        $this->assertEquals(
            [
                'Name',
                'Town',
                'Age',
            ],
            $record->columns()
        );
        $this->assertFalse(
            $record->isEmpty()
        );
        $this->assertEquals(
            'Dotclear',
            $record->Name
        );
        $this->assertFalse(
            $record->exists('Country')
        );
        $this->assertEquals(
            0,
            $record->index()
        );
        $this->assertFalse(
            $record->index(99)
        );
        $this->assertTrue(
            $record->index(1)
        );
        $this->assertEquals(
            [
                [
                    'Name' => 'Dotclear',
                    'Town' => 'Paris',
                    'Age'  => 42,
                ],
                [
                    'Name' => 'Wordpress',
                    'Town' => 'Chicago',
                    'Age'  => 13,
                ],
            ],
            $record->rows()
        );
        $this->assertEquals(
            13,
            $record->Age
        );

        $record->set('Age', 14);

        $this->assertEquals(
            14,
            $record->Age
        );

        $record->sort('Age', 'asc');
        $record->index(0);

        $this->assertEquals(
            'Wordpress',
            $record->Name
        );

        $record->sort('Age', 'desc');
        $record->index(0);

        $this->assertEquals(
            'Dotclear',
            $record->Name
        );

        $record->sort('Name', 'asc');
        $record->index(0);

        $this->assertEquals(
            'Dotclear',
            $record->Name
        );

        $record->lexicalSort('Name', 'asc');
        $record->index(0);

        $this->assertEquals(
            'Dotclear',
            $record->Name
        );

        $record->lexicalSort('Name', 'desc');
        $record->index(0);

        $this->assertEquals(
            'Wordpress',
            $record->Name
        );

        $record->lexicalSort('Age', 'asc');
        $record->index(0);

        $this->assertEquals(
            'Wordpress',
            $record->Name
        );
        ;

        // From array

        $record = new \Dotclear\Database\MetaRecord(\Dotclear\Database\StaticRecord::newFromArray($rows));

        $this->assertEquals(
            2,
            $record->count()
        );
        $this->assertFalse(
            $record->isEmpty()
        );
        $this->assertEquals(
            'Dotclear',
            $record->Name
        );
        $this->assertFalse(
            $record->exists('Country')
        );
        $this->assertEquals(
            0,
            $record->index()
        );
        $this->assertEquals(
            [
                [
                    'Name' => 'Dotclear',
                    'Town' => 'Paris',
                    'Age'  => 42,
                ],
                [
                    'Name' => 'Wordpress',
                    'Town' => 'Chicago',
                    'Age'  => 13,
                ],
            ],
            $record->rows()
        );

        // Direct from array

        $record = \Dotclear\Database\MetaRecord::newFromArray($rows);

        $this->assertEquals(
            2,
            $record->count()
        );
        $this->assertFalse(
            $record->isEmpty()
        );
        $this->assertEquals(
            'Dotclear',
            $record->Name
        );
        $this->assertFalse(
            $record->exists('Country')
        );
        $this->assertEquals(
            0,
            $record->index()
        );
        $this->assertEquals(
            [
                [
                    'Name' => 'Dotclear',
                    'Town' => 'Paris',
                    'Age'  => 42,
                ],
                [
                    'Name' => 'Wordpress',
                    'Town' => 'Chicago',
                    'Age'  => 13,
                ],
            ],
            $record->rows()
        );

        // From null

        $record = new \Dotclear\Database\MetaRecord(\Dotclear\Database\StaticRecord::newFromArray(null));

        $this->assertEquals(
            0,
            $record->count()
        );
        $this->assertTrue(
            $record->isEmpty()
        );
        $this->assertNull(
            $record->Name
        );
        $this->assertFalse(
            $record->exists('Country')
        );
        $this->assertEquals(
            0,
            $record->index()
        );
        $this->assertEquals(
            [],
            $record->rows()
        );

        // Extend error

        $record->extend('unknown');

        $this->assertEquals(
            0,
            count($record->extensions())
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Call to undefined method unknown()');

        $record->unknown();
    }

    /**
     * @return list<array>
     */
    public static function dataProviderTest(): array
    {
        return [
            // driver, syntax
            ['mysqli', 'mysql'],
            ['mysqlimb4', 'mysql'],
            ['pgsql', 'postgresql'],
            ['sqlite', 'sqlite'],
        ];
    }
}
