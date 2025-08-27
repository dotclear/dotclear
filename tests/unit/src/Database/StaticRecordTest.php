<?php

declare(strict_types=1);

namespace Dotclear\Tests\Database;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class StaticRecordTest extends TestCase
{
    private function getConnection(string $driver, string $syntax)
    {
        // Build a mock handler for the driver
        $driverClass = ucfirst($driver);
        $mock        = $this->getMockBuilder("Dotclear\\Schema\\Database\\$driverClass\\Handler")
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
            'info' => null,
            'cols' => 0,
            'rows' => 0,
        ];

        $mock->method('link')->willReturn($mock);
        $mock->method('select')->willReturn(
            $driver !== 'sqlite' ?
            new \Dotclear\Database\Record([], $info) :
            new \Dotclear\Database\StaticRecord([], $info)
        );
        $mock->method('openCursor')->willReturn(new \Dotclear\Database\Cursor($mock, 'dc_table'));
        $mock->method('changes')->willReturn(1);
        $mock->method('escapeStr')->willReturnCallback(fn ($str) => addslashes((string) $str));
        $mock->method('driver')->willReturn($driver);
        $mock->method('syntax')->willReturn($syntax);

        return $mock;
    }

    private function createRecord($driver, $syntax, &$rows, &$info, &$valid, &$pointer, bool $from_array = false): \Dotclear\Database\StaticRecord
    {
        $con = $this->getConnection($driver, $syntax);

        $info['con'] = $con;

        $con->method('db_result_seek')->willReturnCallback(function ($res, $row) use (&$valid, &$pointer) {
            $valid   = ($row >= 0 && $row < 2);
            $pointer = $valid ? $row : 0;

            return $valid;
        });

        $con->method('db_fetch_assoc')->willReturnCallback(function ($res) use (&$valid, &$pointer, $rows) {
            $ret = $valid ? $rows[$pointer] : false;
            $pointer++;
            $valid   = ($pointer >= 0 && $pointer < 2);
            $pointer = $valid ? $pointer : 0;

            return $ret;
        });

        $record = $from_array ?
            \Dotclear\Database\StaticRecord::newFromArray($rows) :
            new \Dotclear\Database\StaticRecord($rows, $info);

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
    public function test($driver, $syntax): void
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

        // From array

        $record = $this->createRecord($driver, $syntax, $rows, $info, $valid, $pointer, true);

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

        $rows   = null;
        $record = $this->createRecord($driver, $syntax, $rows, $info, $valid, $pointer, true);

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
    }

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
