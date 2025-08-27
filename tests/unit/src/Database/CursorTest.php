<?php

declare(strict_types=1);

namespace Dotclear\Tests\Database;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CursorTest extends TestCase
{
    private function getConnection(string $driver, string $syntax): \Dotclear\Database\AbstractHandler
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

    #[DataProvider('dataProviderTest')]
    public function test(string $driver, string $syntax)
    {
        $con    = $this->getConnection($driver, $syntax);
        $cursor = new \Dotclear\Database\Cursor($con, 'dc_table');

        $this->assertFalse(
            $cursor->isField('Name')
        );

        $cursor->setField('Name', 'Dotclear');

        $this->assertEquals(
            'Dotclear',
            $cursor->Name
        );

        $cursor->Town = 'Paris';

        $this->assertEquals(
            'Paris',
            $cursor->Town
        );

        $cursor->Age = 42;

        $this->assertEquals(
            42,
            $cursor->Age
        );

        $cursor->unsetField('Town');

        $this->assertFalse(
            $cursor->isField('Town')
        );

        $this->assertEquals(
            'INSERT INTO dc_table (Name,Age) VALUES (\'Dotclear\',42)',
            $this->normalizeSQL($cursor->getInsert())
        );

        $this->assertTrue(
            $cursor->insert()
        );

        $this->assertEquals(
            'UPDATE dc_table SET Name = \'Dotclear\',Age = 42 WHERE Name = \'Dotclear\'',
            $this->normalizeSQL($cursor->getUpdate(' WHERE Name = \'Dotclear\''))
        );

        $this->assertTrue(
            $cursor->update(' WHERE Name = \'Dotclear\'')
        );

        $cursor->Data = ['AVG(Age)'];
        $cursor->Void = null;

        $this->assertEquals(
            'INSERT INTO dc_table (Name,Age,Data,Void) VALUES (\'Dotclear\',42,\'AVG(Age)\',NULL)',
            $this->normalizeSQL($cursor->getInsert())
        );

        $cursor->setTable('');
        $cursor->clean();

        $this->assertFalse(
            $cursor->isField('Name')
        );
        $this->assertNull(
            $cursor->Unknown
        );
    }

    #[DataProvider('dataProviderTest')]
    public function testInsertError(string $driver, string $syntax)
    {
        $con    = $this->getConnection($driver, $syntax);
        $cursor = new \Dotclear\Database\Cursor($con, '');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No table name.');

        $cursor->insert();
    }

    #[DataProvider('dataProviderTest')]
    public function testUpdateError(string $driver, string $syntax)
    {
        $con    = $this->getConnection($driver, $syntax);
        $cursor = new \Dotclear\Database\Cursor($con, '');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No table name.');

        $cursor->update('');
    }

    public static function dataProviderTest()
    {
        return [
            // driver, syntax
            ['mysqli', 'mysql'],
            ['mysqlimb4', 'mysql'],
            ['pgsql', 'postgresql'],
            ['sqlite', 'sqlite'],
        ];
    }

    protected function normalizeSQL(string $str, bool $comma = true): string
    {
        if ($comma) {
            $str = str_replace(', ', ',', $str);
        }

        return trim(str_replace(["\n", "\r"], '', $str));
    }
}
