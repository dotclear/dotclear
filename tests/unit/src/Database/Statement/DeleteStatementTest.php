<?php

declare(strict_types=1);

namespace Dotclear\Tests\Database\Statement;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DeleteStatementTest extends TestCase
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
                'escapeStr',
                'driver',
                'syntax',
                'execute',
            ])
            ->getMock();

        // Common return values
        $mock->method('link')->willReturn($mock);
        $mock->method('escapeStr')->willReturnCallback(fn ($str) => addslashes((string) $str));
        $mock->method('driver')->willReturn($driver);
        $mock->method('syntax')->willReturn($syntax);
        $mock->method('execute')->willReturn(true);

        return $mock;
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
        $con = $this->getConnection($driver, $driver_folder, $syntax);
        $sql = new \Dotclear\Database\Statement\DeleteStatement($con, $syntax);

        $sql
            ->from('MyTable')
            ->where($sql->isNull('MyField'))
            ->cond('AND MyID > 0')
            ->sql('OR (MyCounter = 0)')
        ;

        $this->assertEquals(
            'DELETE FROM MyTable WHERE MyField IS NULL AND MyID > 0 OR (MyCounter = 0)',
            $sql->statement()
        );
    }

    #[DataProvider('dataProviderTest')]
    public function testNoWhere(string $driver, string $driver_folder, string $syntax): void
    {
        $con = $this->getConnection($driver, $driver_folder, $syntax);
        $sql = new \Dotclear\Database\Statement\DeleteStatement($con, $syntax);

        $sql
            ->from('MyTable')
            ->cond('AND MyID > 0')
            ->sql('OR (MyCounter = 0)')
        ;

        if ($syntax !== 'sqlite') {
            $this->assertEquals(
                'DELETE FROM MyTable WHERE TRUE AND MyID > 0 OR (MyCounter = 0)',
                $sql->statement()
            );
        } else {
            $this->assertEquals(
                'DELETE FROM MyTable WHERE 1 AND MyID > 0 OR (MyCounter = 0)',
                $sql->statement()
            );
        }
    }

    #[DataProvider('dataProviderTest')]
    public function testNoFrom(string $driver, string $driver_folder, string $syntax): void
    {
        $con = $this->getConnection($driver, $driver_folder, $syntax);
        $sql = new \Dotclear\Database\Statement\DeleteStatement($con, $syntax);

        $sql
            ->where($sql->isNull('MyField'))
        ;

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Undefined array key 0');
        $this->expectExceptionMessage('SQL DELETE requires a FROM source');

        $sql->statement();
    }

    #[DataProvider('dataProviderTest')]
    public function testRun(string $driver, string $driver_folder, string $syntax): void
    {
        $con = $this->getConnection($driver, $driver_folder, $syntax);
        $sql = new \Dotclear\Database\Statement\DeleteStatement($con, $syntax);

        $sql
            ->from('MyTable')
            ->where($sql->isNull('MyField'))
        ;

        $this->assertTrue(
            $sql->run()
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
