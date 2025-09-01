<?php

declare(strict_types=1);

namespace Dotclear\Tests\Database\Statement;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class JoinStatementTest extends TestCase
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
        $sql = new \Dotclear\Database\Statement\JoinStatement($con, $syntax);

        $sql
            ->type('INNER')
            ->from('T')
            ->where($sql->isNull('F'))
            ->cond('OR G = 42')
            ->sql('AND H = 0')
        ;

        $this->assertEquals(
            'INNER JOIN T ON F IS NULL OR G = 42 AND H = 0',
            $sql->statement()
        );

        $sql
            ->left()
        ;

        $this->assertEquals(
            'LEFT JOIN T ON F IS NULL OR G = 42 AND H = 0',
            $sql->statement()
        );

        $sql
            ->right()
        ;

        $this->assertEquals(
            'RIGHT JOIN T ON F IS NULL OR G = 42 AND H = 0',
            $sql->statement()
        );

        $sql
            ->inner()
        ;

        $this->assertEquals(
            'INNER JOIN T ON F IS NULL OR G = 42 AND H = 0',
            $sql->statement()
        );
    }

    #[DataProvider('dataProviderTest')]
    public function testNoFrom(string $driver, string $driver_folder, string $syntax): void
    {
        $con = $this->getConnection($driver, $driver_folder, $syntax);
        $sql = new \Dotclear\Database\Statement\JoinStatement($con, $syntax);

        $sql
            ->type('INNER')
            ->where($sql->isNull('F'))
        ;

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Undefined array key 0');
        $this->expectExceptionMessage('SQL JOIN requires a FROM source');

        $sql->statement();
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
            ['pdosqlite', 'PdoSqlite', 'sqlite'],
            ['pdomysql', 'PdoMysql', 'mysql'],
            ['pdomysqlmb4', 'PdoMysqlmb4', 'mysql'],
            ['pdopgsql', 'PdoPgsql', 'postgresql'],
        ];
    }
}
