<?php

declare(strict_types=1);

namespace Dotclear\Tests\Database\Statement;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UpdateStatementTest extends TestCase
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
    public function test(string $driver, string $syntax): void
    {
        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\UpdateStatement($con, $syntax);

        $sql
            ->ref('client')
            ->reference('client')
            ->fields(['prenom', 'nom', 'ville', 'age'])
            ->values(['Rébecca', 'Armand', 'Saint-Didier-des-Bois', 24])
            ->where('id = 2')
            ->cond('AND ' . $sql->isNull('super'))
            ->sql('OR (group = 0)')
        ;

        $this->assertEquals(
            'UPDATE client SET prenom = \'Rébecca\', nom = \'Armand\', ville = \'Saint-Didier-des-Bois\', age = 24 WHERE id = 2 AND super IS NULL OR (group = 0)',
            $sql->statement()
        );

        $this->assertEquals(
            'WHERE id = 2 AND super IS NULL OR (group = 0)',
            $sql->whereStatement()
        );

        $sql
            ->values('Irma', true)
        ;

        $this->assertEquals(
            'UPDATE client SET prenom = \'Irma\' WHERE id = 2 AND super IS NULL OR (group = 0)',
            $sql->statement()
        );

        $sql
            ->sets('age = 13')
        ;

        $this->assertEquals(
            'UPDATE client SET prenom = \'Irma\', age = 13 WHERE id = 2 AND super IS NULL OR (group = 0)',
            $sql->statement()
        );
    }

    #[DataProvider('dataProviderTest')]
    public function testNoFields(string $driver, string $syntax): void
    {
        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\UpdateStatement($con, $syntax);

        $sql
            ->ref('client')
            ->sets('age = 13')
            ->cond('AND ' . $sql->isNull('super'))
        ;

        if ($syntax !== 'sqlite') {
            $this->assertEquals(
                'UPDATE client SET age = 13 WHERE TRUE AND super IS NULL',
                $sql->statement()
            );
        } else {
            $this->assertEquals(
                'UPDATE client SET age = 13 WHERE 1 AND super IS NULL',
                $sql->statement()
            );
        }
    }

    #[DataProvider('dataProviderTest')]
    public function testNoWhere(string $driver, string $syntax): void
    {
        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\UpdateStatement($con, $syntax);

        $sql
            ->ref('client')
            ->reference('client')
            ->fields(['prenom', 'nom', 'ville', 'age'])
            ->values(['Rébecca', 'Armand', 'Saint-Didier-des-Bois', 24])
            ->cond('AND ' . $sql->isNull('super'))
        ;

        if ($syntax !== 'sqlite') {
            $this->assertEquals(
                'UPDATE client SET prenom = \'Rébecca\', nom = \'Armand\', ville = \'Saint-Didier-des-Bois\', age = 24 WHERE TRUE AND super IS NULL',
                $sql->statement()
            );
        } else {
            $this->assertEquals(
                'UPDATE client SET prenom = \'Rébecca\', nom = \'Armand\', ville = \'Saint-Didier-des-Bois\', age = 24 WHERE 1 AND super IS NULL',
                $sql->statement()
            );
        }

        if ($syntax !== 'sqlite') {
            $this->assertEquals(
                'WHERE TRUE AND super IS NULL',
                $sql->whereStatement()
            );
        } else {
            $this->assertEquals(
                'WHERE 1 AND super IS NULL',
                $sql->whereStatement()
            );
        }
    }

    #[DataProvider('dataProviderTest')]
    public function testNoFrom(string $driver, string $syntax): void
    {
        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\UpdateStatement($con, $syntax);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Undefined array key 0');
        $this->expectExceptionMessage('SQL UPDATE requires a FROM source');

        $sql->statement();
    }

    #[DataProvider('dataProviderTest')]
    public function testRun(string $driver, string $syntax): void
    {
        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\UpdateStatement($con, $syntax);

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
            // driver, syntax
            ['mysqli', 'mysql'],
            ['mysqlimb4', 'mysql'],
            ['pgsql', 'postgresql'],
            ['sqlite', 'sqlite'],
        ];
    }
}
