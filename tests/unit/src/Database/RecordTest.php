<?php

declare(strict_types=1);

namespace Dotclear\Tests\Database;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RecordExtend
{
    public static function isEditable(\Dotclear\Database\Record $rs): bool
    {
        return ($rs->index() === 0);
    }
}

class RecordTest extends TestCase
{
    private function getConnection(string $driver, string $driver_folder, string $syntax): MockObject
    {
        // Build a mock handler for the driver
        $handlerClass = implode('\\', ['Dotclear', 'Schema', 'Database', $driver_folder, 'Handler']);
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
    private function createRecord(string $driver, string $driver_folder, string $syntax, ?array &$rows, array &$info, bool &$valid, int &$pointer): \Dotclear\Database\Record
    {
        $con = $this->getConnection($driver, $driver_folder, $syntax);

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

        // @phpstan-ignore argument.type
        $record = new \Dotclear\Database\Record($rows, $info);

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
    public function test(string $driver, string $driver_folder, string $syntax): void
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

        $record = $this->createRecord($driver, $driver_folder, $syntax, $rows, $info, $valid, $pointer);

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
        ;
    }

    #[DataProvider('dataProviderTest')]
    public function testToStatic(string $driver, string $driver_folder, string $syntax): void
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

        $record = $this->createRecord($driver, $driver_folder, $syntax, $rows, $info, $valid, $pointer);

        $static = $record->toStatic();
        $double = $static->toStatic();

        // Info
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
    public function testExtend(string $driver, string $driver_folder, string $syntax): void
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

        $record = $this->createRecord($driver, $driver_folder, $syntax, $rows, $info, $valid, $pointer);

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
        $record->extend(\Dotclear\Tests\Database\RecordExtend::class);

        $this->assertEquals(
            [
                'isEditable' => [
                    \Dotclear\Tests\Database\RecordExtend::class,
                    'isEditable',
                ],
            ],
            $record->extensions()
        );
        $this->assertTrue(
            // @phpstan-ignore method.notFound
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
            // @phpstan-ignore method.notFound
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
            // @phpstan-ignore method.notFound
            $record->isEditable()
        );

        // Extend error
        $record->extend('unknown');

        $this->assertEquals(
            1,
            count($record->extensions())
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Call to undefined method Record::unknown()');

        // @phpstan-ignore method.notFound
        $record->unknown();
    }

    #[DataProvider('dataProviderTest')]
    public function testRows(string $driver, string $driver_folder, string $syntax): void
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

        $record = $this->createRecord($driver, $driver_folder, $syntax, $rows, $info, $valid, $pointer);

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

    /**
     * @return list<array>
     */
    public static function dataProviderTest(): array
    {
        return [
            // driver, driver_foler, syntax
            ['mysqli', 'Mysqli', 'mysql'],
            ['mysqlimb4', 'Mysqlimb4', 'mysql'],
            ['pgsql', 'Pgsql', 'postgresql'],
            ['sqlite', 'PdoSqlite', 'sqlite'],
        ];
    }
}
