<?php

declare(strict_types=1);

namespace Dotclear\Tests\Database\Statement;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class InsertStatementTest extends TestCase
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
        $sql = new \Dotclear\Database\Statement\InsertStatement($con, $syntax);

        $sql
            ->into('MyTable')
            ->fields(['prenom', 'nom', 'ville', 'age'])
            ->lines([
                ['\'Rébecca\', \'Armand\', \'Saint-Didier-des-Bois\', 24'],
                ['\'Aimée\', \'Hebert\', \'Marigny-le-Châtel\', 36'],
            ])
            ->lines('\'Marielle\', \'Ribeiro\', \'Maillères\', 27')
            ->line('\'Hilaire\', \'Savary\', \'Conie-Molitard\', 58')
        ;

        $this->assertEquals(
            'INSERT INTO MyTable (prenom, nom, ville, age) VALUES (\'Rébecca\', \'Armand\', \'Saint-Didier-des-Bois\', 24), (\'Aimée\', \'Hebert\', \'Marigny-le-Châtel\', 36), (\'Marielle\', \'Ribeiro\', \'Maillères\', 27), (\'Hilaire\', \'Savary\', \'Conie-Molitard\', 58)',
            $sql->statement()
        );

        $sql
            ->line('\'Hilaire\', \'Savary\', \'Conie-Molitard\', 58', true)
        ;

        $this->assertEquals(
            'INSERT INTO MyTable (prenom, nom, ville, age) VALUES (\'Hilaire\', \'Savary\', \'Conie-Molitard\', 58)',
            $sql->statement()
        );

        $sql
            ->values([
                '\'Marielle\', NULL, \'Maillères\', 27',
                ['Hilaire', null, 'Conie-Molitard', 58],
            ], true)
        ;

        $this->assertEquals(
            'INSERT INTO MyTable (prenom, nom, ville, age) VALUES (\'Marielle\', NULL, \'Maillères\', 27), (\'Hilaire\', NULL, \'Conie-Molitard\', 58)',
            $sql->statement()
        );
    }

    #[DataProvider('dataProviderTest')]
    public function testNoFrom(string $driver, string $driver_folder, string $syntax): void
    {
        $con = $this->getConnection($driver, $driver_folder, $syntax);
        $sql = new \Dotclear\Database\Statement\InsertStatement($con, $syntax);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Undefined array key 0');
        $this->expectExceptionMessage('SQL INSERT requires an INTO source');

        $sql->statement();
    }

    #[DataProvider('dataProviderTest')]
    public function testRun(string $driver, string $driver_folder, string $syntax): void
    {
        $con = $this->getConnection($driver, $driver_folder, $syntax);
        $sql = new \Dotclear\Database\Statement\InsertStatement($con, $syntax);

        $sql
            ->from('MyTable')
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
            ['pdosqlite', 'PdoSqlite', 'sqlite'],
            ['pdomysql', 'PdoMysql', 'mysql'],
            ['pdomysqlmb4', 'PdoMysqlmb4', 'mysql'],
            ['pdopgsql', 'PdoPgsql', 'postgresql'],
        ];
    }
}
