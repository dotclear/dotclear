<?php

declare(strict_types=1);

namespace Dotclear\Tests\Database\Statement;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SelectStatementTest extends TestCase
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

    // Generic (including tests of some SqlStatement methods too)

    #[DataProvider('dataProviderTest')]
    public function test(string $driver, string $driver_folder, string $syntax): void
    {
        $con = $this->getConnection($driver, $driver_folder, $syntax);
        $sql = new \Dotclear\Database\Statement\SelectStatement($con, $syntax);
        $sql
            ->from('MyTable')
            ->from(['MyTableOne', 'MyTableTwo'])
            ->from('MyTableThree, MyTableFour')
            ->columns(['MyFieldOne', 'MyFieldTwo'])
            ->fields(['MyFieldThree', 'MyFieldFour'])
            ->columns('MyFieldFive')
            ->field('MyFieldSix')
            ->where('MyField = 42')
            ->where(['MyFieldOne = 17', 'MyFieldTwo <> 19'])
            ->on('MyOtherField = -1')
            ->cond('AND MyNullField IS NULL')
            ->and('MyAndField <> 0')
            ->or($sql->andGroup(['MyFieldOne = 42', 'MyFieldTwo = -1']))
            ->and($sql->orGroup(['MyFieldOne = -2', 'MyFieldTwo = 43']))
            ->group('MyField')
            ->group(['MyFieldOne', 'MyFieldTwo'])
            ->sql('AND MyAlias IS NULL')
            ->sql(['AND MyAliasTwo IS NULL', 'AND MyAliasThree IS NULL'])
            ->from('MyTableFive', false, true)
            ->from(['MyTableSix', 'MyTableSeven'], false, true)
        ;

        $this->assertEquals(
            'SELECT MyFieldOne, MyFieldTwo, MyFieldThree, MyFieldFour, MyFieldFive, MyFieldSix FROM MyTableSix, MyTableSeven, MyTableFive, MyTable, MyTableOne, MyTableTwo, MyTableThree, MyTableFour WHERE MyField = 42 AND MyFieldOne = 17 AND MyFieldTwo <> 19 AND MyOtherField = -1 AND MyNullField IS NULL AND MyAndField <> 0 OR (MyFieldOne = 42 AND MyFieldTwo = -1) AND (MyFieldOne = -2 OR MyFieldTwo = 43) AND MyAlias IS NULL AND MyAliasTwo IS NULL AND MyAliasThree IS NULL GROUP BY MyField, MyFieldOne, MyFieldTwo',
            $sql->statement()
        );

        $this->assertEquals(
            'MyField IN (SELECT * FROM MySecondTable)',
            $sql->inSelect('MyField', (new \Dotclear\Database\Statement\SelectStatement($con, $syntax))->from('MySecondTable'))
        );
    }

    #[DataProvider('dataProviderTest')]
    public function testReset(string $driver, string $driver_folder, string $syntax): void
    {
        $con = $this->getConnection($driver, $driver_folder, $syntax);
        $sql = new \Dotclear\Database\Statement\SelectStatement($con, $syntax);

        $sql
            ->column('F')
            ->column('G', true)
            ->columns(['H', 'I'])
            ->columns(['K', 'L'], true)
            ->from('T')
            ->from('U', true)
            ->where('C = 42')
            ->where('D = 0', true)
            ->cond('AND E = 7')
            ->cond('OR F = -1', true)
            ->sql('OR G IS NULL')
            ->sql('OR H IS NULL', true)
        ;

        $this->assertEquals(
            'SELECT K, L FROM U WHERE D = 0 OR F = -1 OR H IS NULL',
            $sql->statement()
        );
    }

    #[DataProvider('dataProviderTest')]
    public function testIsSame(string $driver, string $driver_folder, string $syntax): void
    {
        $con = $this->getConnection($driver, $driver_folder, $syntax);
        $sql = new \Dotclear\Database\Statement\SelectStatement($con, $syntax);

        $sql
            ->columns(['K', 'L'])
            ->from('U')
            ->where('D = 0')
            ->cond('OR F = -1')
            ->sql('OR (H IS NULL AND I IS NOT NULL)')
        ;

        $this->assertTrue(
            $sql->isSame($sql->statement(), ' SELECT K  , L FROM U WHERE D = 0 OR F = -1 OR  ( H IS NULL AND  I IS NOT NULL )  ')
        );
    }

    #[DataProvider('dataProviderTest')]
    public function testCompare(string $driver, string $driver_folder, string $syntax): void
    {
        $con = $this->getConnection($driver, $driver_folder, $syntax);
        $sql = new \Dotclear\Database\Statement\SelectStatement($con, $syntax);

        $sql
            ->columns(['K', 'L'])
            ->from('U')
            ->where('D = 0')
            ->cond('OR F = -1')
            ->sql('OR (H IS NULL AND I IS NOT NULL)')
        ;

        $this->assertFalse(
            $sql->compare(' SELECT KC  , L FROM U WHERE D = 0 OR F = -1 OR  ( H IS NULL AND  I IS NOT NULL )  ')
        );
    }

    // Specific SelectStatement tests

    #[DataProvider('dataProviderTest')]
    public function testJoin(string $driver, string $driver_folder, string $syntax): void
    {
        $con = $this->getConnection($driver, $driver_folder, $syntax);
        $sql = new \Dotclear\Database\Statement\SelectStatement($con, $syntax);

        $sql
            ->from($sql->as('TableC', 'C'))
            ->join(
                (new \Dotclear\Database\Statement\JoinStatement($con, $syntax))
                    ->from($sql->as('TableP', 'P'))
                    ->on('C.cat_id = P.cat_id')
                    ->and('P.blog_id = ' . $sql->quote('default'))
                    ->statement()
            )
        ;

        if ($syntax !== 'sqlite') {
            $this->assertEquals(
                'SELECT * FROM TableC C JOIN TableP P ON C.cat_id = P.cat_id AND P.blog_id = \'default\'',
                $sql->statement()
            );
        } else {
            $this->assertEquals(
                'SELECT * FROM TableC AS C JOIN TableP AS P ON C.cat_id = P.cat_id AND P.blog_id = \'default\'',
                $sql->statement()
            );
        }

        $sql
            ->join([
                (new \Dotclear\Database\Statement\JoinStatement($con, $syntax))
                    ->from($sql->as('TableP', 'P'))
                    ->on('C.cat_id = P.cat_id')
                    ->and('P.blog_id = ' . $sql->quote('default'))
                    ->statement(),
                (new \Dotclear\Database\Statement\JoinStatement($con, $syntax))
                    ->from($sql->as('TableQ', 'Q'))
                    ->on('C.cat_id = Q.cat_id')
                    ->and('Q.blog_id = ' . $sql->quote('default'))
                    ->statement(),
            ], true)
        ;

        if ($syntax !== 'sqlite') {
            $this->assertEquals(
                'SELECT * FROM TableC C JOIN TableP P ON C.cat_id = P.cat_id AND P.blog_id = \'default\' JOIN TableQ Q ON C.cat_id = Q.cat_id AND Q.blog_id = \'default\'',
                $sql->statement()
            );
        } else {
            $this->assertEquals(
                'SELECT * FROM TableC AS C JOIN TableP AS P ON C.cat_id = P.cat_id AND P.blog_id = \'default\' JOIN TableQ AS Q ON C.cat_id = Q.cat_id AND Q.blog_id = \'default\'',
                $sql->statement()
            );
        }
    }

    #[DataProvider('dataProviderTest')]
    public function testUnion(string $driver, string $driver_folder, string $syntax): void
    {
        $con = $this->getConnection($driver, $driver_folder, $syntax);
        $sql = new \Dotclear\Database\Statement\SelectStatement($con, $syntax);

        $sql
            ->from($sql->as('TableC', 'C'))
            ->union(
                (new \Dotclear\Database\Statement\SelectStatement($con, $syntax))
                    ->from($sql->as('TableU', 'U'))
                    ->where('user_super = 1')
                    ->statement()
            )
        ;

        if ($syntax !== 'sqlite') {
            $this->assertEquals(
                'SELECT * FROM TableC C UNION SELECT * FROM TableU U WHERE user_super = 1',
                $sql->statement()
            );
        } else {
            $this->assertEquals(
                'SELECT * FROM TableC AS C UNION SELECT * FROM TableU AS U WHERE user_super = 1',
                $sql->statement()
            );
        }

        $sql
            ->from($sql->as('TableC', 'C'))
            ->union([
                (new \Dotclear\Database\Statement\SelectStatement($con, $syntax))
                    ->from($sql->as('TableU', 'U'))
                    ->where('user_super = 1')
                    ->statement(),
                (new \Dotclear\Database\Statement\SelectStatement($con, $syntax))
                    ->from($sql->as('TableV', 'V'))
                    ->where('user_admin = 1')
                    ->statement(),
            ], true)
        ;

        if ($syntax !== 'sqlite') {
            $this->assertEquals(
                'SELECT * FROM TableC C, TableC C UNION SELECT * FROM TableU U WHERE user_super = 1 UNION SELECT * FROM TableV V WHERE user_admin = 1',
                $sql->statement()
            );
        } else {
            $this->assertEquals(
                'SELECT * FROM TableC AS C, TableC AS C UNION SELECT * FROM TableU AS U WHERE user_super = 1 UNION SELECT * FROM TableV AS V WHERE user_admin = 1',
                $sql->statement()
            );
        }
    }

    #[DataProvider('dataProviderTest')]
    public function testHaving(string $driver, string $driver_folder, string $syntax): void
    {
        $con = $this->getConnection($driver, $driver_folder, $syntax);
        $sql = new \Dotclear\Database\Statement\SelectStatement($con, $syntax);

        $sql
            ->from('Customers')
            ->fields([
                $sql->count('CustomerID'),
                'Country',
            ])
            ->groupBy('Country')
            ->having($sql->count('CustomerID') . ' > 5')
        ;

        $this->assertEquals(
            'SELECT COUNT(CustomerID), Country FROM Customers GROUP BY Country HAVING COUNT(CustomerID) > 5',
            $sql->statement()
        );

        $sql
            ->having([
                $sql->count('CustomerID') . ' > 17',
                $sql->max('CustomerID') . ' > 4', // I know, it's not coherent but it's only for testing purpose
            ], true)
        ;

        $this->assertEquals(
            'SELECT COUNT(CustomerID), Country FROM Customers GROUP BY Country HAVING COUNT(CustomerID) > 17 AND MAX(CustomerID) > 4',
            $sql->statement()
        );
    }

    #[DataProvider('dataProviderTest')]
    public function testOrder(string $driver, string $driver_folder, string $syntax): void
    {
        $con = $this->getConnection($driver, $driver_folder, $syntax);
        $sql = new \Dotclear\Database\Statement\SelectStatement($con, $syntax);

        $sql
            ->columns(['blog_id', 'user_id'])
            ->from('TableB')
            ->order('blog_id ASC')
        ;

        $this->assertEquals(
            'SELECT blog_id, user_id FROM TableB ORDER BY blog_id ASC',
            $sql->statement()
        );

        $sql
            ->order([
                'blog_id DESC',
                'user_id ASC',
            ], true)
        ;

        $this->assertEquals(
            'SELECT blog_id, user_id FROM TableB ORDER BY blog_id DESC, user_id ASC',
            $sql->statement()
        );
    }

    #[DataProvider('dataProviderTest')]
    public function testGroup(string $driver, string $driver_folder, string $syntax): void
    {
        $con = $this->getConnection($driver, $driver_folder, $syntax);
        $sql = new \Dotclear\Database\Statement\SelectStatement($con, $syntax);

        $sql
            ->columns(['blog_id', 'user_id'])
            ->from('TableB')
            ->groupBy('blog_id')
        ;

        $this->assertEquals(
            'SELECT blog_id, user_id FROM TableB GROUP BY blog_id',
            $sql->statement()
        );

        $sql
            ->group([
                'blog_id',
                'user_id',
            ], true)
        ;

        $this->assertEquals(
            'SELECT blog_id, user_id FROM TableB GROUP BY blog_id, user_id',
            $sql->statement()
        );
    }

    #[DataProvider('dataProviderTest')]
    public function testLimit(string $driver, string $driver_folder, string $syntax): void
    {
        $con = $this->getConnection($driver, $driver_folder, $syntax);
        $sql = new \Dotclear\Database\Statement\SelectStatement($con, $syntax);

        $sql
            ->columns(['blog_id', 'user_id'])
            ->from('TableB')
            ->limit(1)
        ;

        $this->assertEquals(
            'SELECT blog_id, user_id FROM TableB LIMIT 1',
            $sql->statement()
        );

        $sql
            ->limit([4])
        ;

        $this->assertEquals(
            'SELECT blog_id, user_id FROM TableB LIMIT 4',
            $sql->statement()
        );

        $sql
            ->limit([10, 5])
        ;

        $this->assertEquals(
            'SELECT blog_id, user_id FROM TableB LIMIT 5 OFFSET 10',
            $sql->statement()
        );
    }

    #[DataProvider('dataProviderTest')]
    public function testOffset(string $driver, string $driver_folder, string $syntax): void
    {
        $con = $this->getConnection($driver, $driver_folder, $syntax);
        $sql = new \Dotclear\Database\Statement\SelectStatement($con, $syntax);

        $sql
            ->columns(['blog_id', 'user_id'])
            ->from('TableB')
            ->offset(10)
        ;

        $this->assertEquals(
            'SELECT blog_id, user_id FROM TableB OFFSET 10',
            $sql->statement()
        );
    }

    #[DataProvider('dataProviderTest')]
    public function testDistinct(string $driver, string $driver_folder, string $syntax): void
    {
        $con = $this->getConnection($driver, $driver_folder, $syntax);
        $sql = new \Dotclear\Database\Statement\SelectStatement($con, $syntax);

        $sql
            ->columns(['blog_id', 'user_id'])
            ->from('TableB')
            ->distinct(true)
        ;

        $this->assertEquals(
            'SELECT DISTINCT blog_id, user_id FROM TableB',
            $sql->statement()
        );
    }

    #[DataProvider('dataProviderTest')]
    public function testNoFrom(string $driver, string $driver_folder, string $syntax): void
    {
        $con = $this->getConnection($driver, $driver_folder, $syntax);
        $sql = new \Dotclear\Database\Statement\SelectStatement($con, $syntax);

        $sql
            ->columns(['blog_id', 'user_id'])
        ;

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Undefined array key 0');
        $this->expectExceptionMessage('SQL SELECT requires a FROM source');

        $sql->statement();
    }

    #[DataProvider('dataProviderTest')]
    public function testCondAndNoWhere(string $driver, string $driver_folder, string $syntax): void
    {
        $con = $this->getConnection($driver, $driver_folder, $syntax);
        $sql = new \Dotclear\Database\Statement\SelectStatement($con, $syntax);

        $sql
            ->columns(['blog_id', 'user_id'])
            ->from('TableB')
            ->cond('AND blog_id IS NULL')
        ;

        if ($syntax !== 'sqlite') {
            $this->assertEquals(
                'SELECT blog_id, user_id FROM TableB WHERE TRUE AND blog_id IS NULL',
                $sql->statement()
            );
        } else {
            $this->assertEquals(
                'SELECT blog_id, user_id FROM TableB WHERE 1 AND blog_id IS NULL',
                $sql->statement()
            );
        }
    }

    #[DataProvider('dataProviderTest')]
    public function testRun(string $driver, string $driver_folder, string $syntax): void
    {
        $con = $this->getConnection($driver, $driver_folder, $syntax);
        $sql = new \Dotclear\Database\Statement\SelectStatement($con, $syntax);

        $sql
            ->from('MyTable')
            ->where($sql->isNull('MyField'))
        ;

        $this->assertTrue(
            $sql->run() instanceof \Dotclear\Database\MetaRecord
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
