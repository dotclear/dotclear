<?php

declare(strict_types=1);

namespace Dotclear\Tests\Database\Statement;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SqlStatementTest extends TestCase
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
        $sql = new \Dotclear\Database\Statement\SqlStatement($con, $syntax);

        $this->assertEquals(
            '',
            $sql->statement()
        );

        $this->assertEquals(
            '',
            $sql()
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
            ['pdomysqlmb4', 'PdoMysqlMb4', 'mysql'],
            ['pdopgsql', 'PdoPgsql', 'postgresql'],
        ];
    }

    #[DataProvider('dataProviderTestEscape')]
    public function testEscape(string $driver, string $driver_folder, string $syntax, string $source, string $result): void
    {
        $con = $this->getConnection($driver, $driver_folder, $syntax);
        $sql = new \Dotclear\Database\Statement\SqlStatement($con, $syntax);

        $this->assertEquals(
            $result,
            $sql->escape($source)
        );
    }

    /**
     * @return list<array>
     */
    public static function dataProviderTestEscape(): array
    {
        return [
            ['mysqli', 'Mysqli', 'mysql', 'test', 'test'],
            ['mysqlimb4', 'Mysqlimb4', 'mysql', 'test', 'test'],
            ['pgsql', 'Pgsql', 'postgresql', 'test', 'test'],
            ['pdosqlite', 'PdoSqlite', 'sqlite', 'test', 'test'],
            ['pdomysql', 'PdoMysql', 'mysql', 'test', 'test'],
            ['pdomysqlmb4', 'PdoMysqlMb4', 'mysql', 'test', 'test'],
            ['pdopgsql', 'PdoPgsql', 'postgresql', 'test', 'test'],
            ['mysqli', 'Mysqli', 'mysql', 't"e\'s`t', 't\"e\\\'s`t'],
            ['mysqlimb4', 'Mysqlimb4', 'mysql', 't"e\'s`t', 't\"e\\\'s`t'],
            ['pgsql', 'Pgsql', 'postgresql', 't"e\'s`t', 't\"e\\\'s`t'],
            ['pdosqlite', 'PdoSqlite', 'sqlite', 't"e\'s`t', 't\"e\\\'s`t'],
            ['pdomysql', 'PdoMysql', 'mysql', 't"e\'s`t', 't\"e\\\'s`t'],
            ['pdomysqlmb4', 'PdoMysqlMb4', 'mysql', 't"e\'s`t', 't\"e\\\'s`t'],
            ['pdopgsql', 'PdoPgsql', 'postgresql', 't"e\'s`t', 't\"e\\\'s`t'],
            ['mysqli', 'Mysqli', 'mysql', 't%es_t*', 't%es_t*'],
            ['mysqlimb4', 'Mysqlimb4', 'mysql', 't%es_t*', 't%es_t*'],
            ['pgsql', 'Pgsql', 'postgresql', 't%es_t*', 't%es_t*'],
            ['pdosqlite', 'PdoSqlite', 'sqlite', 't%es_t*', 't%es_t*'],
            ['pdomysql', 'PdoMysql', 'mysql', 't%es_t*', 't%es_t*'],
            ['pdomysqlmb4', 'PdoMysqlMb4', 'mysql', 't%es_t*', 't%es_t*'],
            ['pdopgsql', 'PdoPgsql', 'postgresql', 't%es_t*', 't%es_t*'],
        ];
    }

    #[DataProvider('dataProviderTestQuote')]
    public function testQuote(string $driver, string $driver_folder, string $syntax, string $source, string $result, bool $escape): void
    {
        $con = $this->getConnection($driver, $driver_folder, $syntax);
        $sql = new \Dotclear\Database\Statement\SqlStatement($con, $syntax);

        $this->assertEquals(
            $result,
            $sql->quote($source, $escape)
        );
    }

    /**
     * @return list<array>
     */
    public static function dataProviderTestQuote(): array
    {
        return [
            ['mysqli', 'Mysqli', 'mysql', 'test', '\'test\'', true],
            ['mysqlimb4', 'Mysqlimb4', 'mysql', 'test', '\'test\'', true],
            ['pgsql', 'Pgsql', 'postgresql', 'test', '\'test\'', true],
            ['pdosqlite', 'PdoSqlite', 'sqlite', 'test', '\'test\'', true],
            ['pdomysql', 'PdoMysql', 'mysql', 'test', '\'test\'', true],
            ['pdomysqlmb4', 'PdoMysqlMb4', 'mysql', 'test', '\'test\'', true],
            ['pdopgsql', 'PdoPgsql', 'postgresql', 'test', '\'test\'', true],
            ['mysqli', 'Mysqli', 'mysql', 'test', '\'test\'', false],
            ['mysqlimb4', 'Mysqlimb4', 'mysql', 'test', '\'test\'', false],
            ['pgsql', 'Pgsql', 'postgresql', 'test', '\'test\'', false],
            ['pdosqlite', 'PdoSqlite', 'sqlite', 'test', '\'test\'', false],
            ['pdomysql', 'PdoMysql', 'mysql', 'test', '\'test\'', false],
            ['pdomysqlmb4', 'PdoMysqlMb4', 'mysql', 'test', '\'test\'', false],
            ['pdopgsql', 'PdoPgsql', 'postgresql', 'test', '\'test\'', false],
            ['mysqli', 'Mysqli', 'mysql', 't"e\'st', '\'t\"e\\\'st\'', true],
            ['mysqlimb4', 'Mysqlimb4', 'mysql', 't"e\'st', '\'t\"e\\\'st\'', true],
            ['pgsql', 'Pgsql', 'postgresql', 't"e\'st', '\'t\"e\\\'st\'', true],
            ['pdosqlite', 'PdoSqlite', 'sqlite', 't"e\'st', '\'t\"e\\\'st\'', true],
            ['pdomysql', 'PdoMysql', 'mysql', 't"e\'st', '\'t\"e\\\'st\'', true],
            ['pdomysqlmb4', 'PdoMysqlMb4', 'mysql', 't"e\'st', '\'t\"e\\\'st\'', true],
            ['pdopgsql', 'PdoPgsql', 'postgresql', 't"e\'st', '\'t\"e\\\'st\'', true],
            ['mysqli', 'Mysqli', 'mysql', 't"e\'st', '\'t"e\'st\'', false],
            ['mysqlimb4', 'Mysqlimb4', 'mysql', 't"e\'st', '\'t"e\'st\'', false],
            ['pgsql', 'Pgsql', 'postgresql', 't"e\'st', '\'t"e\'st\'', false],
            ['pdosqlite', 'PdoSqlite', 'sqlite', 't"e\'st', '\'t"e\'st\'', false],
            ['pdomysql', 'PdoMysql', 'mysql', 't"e\'st', '\'t"e\'st\'', false],
            ['pdomysqlmb4', 'PdoMysqlMb4', 'mysql', 't"e\'st', '\'t"e\'st\'', false],
            ['pdopgsql', 'PdoPgsql', 'postgresql', 't"e\'st', '\'t"e\'st\'', false],
        ];
    }

    #[DataProvider('dataProviderTestAlias')]
    public function testAlias(string $driver, string $driver_folder, string $syntax, string $name, string $alias, string $result): void
    {
        $con = $this->getConnection($driver, $driver_folder, $syntax);
        $sql = new \Dotclear\Database\Statement\SqlStatement($con, $syntax);

        $this->assertEquals(
            $result,
            $sql->alias($name, $alias)
        );
        $this->assertEquals(
            $result,
            $sql->as($name, $alias)
        );
    }

    /**
     * @return list<array>
     */
    public static function dataProviderTestAlias(): array
    {
        return [
            ['mysqli', 'Mysqli', 'mysql', 'MyTable.MyField', 'MyAlias', 'MyTable.MyField MyAlias'],
            ['mysqlimb4', 'Mysqlimb4', 'mysql', 'MyTable.MyField', 'MyAlias', 'MyTable.MyField MyAlias'],
            ['pgsql', 'Pgsql', 'postgresql', 'MyTable.MyField', 'MyAlias', 'MyTable.MyField MyAlias'],
            ['pdosqlite', 'PdoSqlite', 'sqlite', 'MyTable.MyField', 'MyAlias', 'MyTable.MyField AS MyAlias'],
            ['pdomysql', 'PdoMysql', 'mysql', 'MyTable.MyField', 'MyAlias', 'MyTable.MyField MyAlias'],
            ['pdomysqlmb4', 'PdoMysqlMb4', 'mysql', 'MyTable.MyField', 'MyAlias', 'MyTable.MyField MyAlias'],
            ['pdopgsql', 'PdoPgsql', 'postgresql', 'MyTable.MyField', 'MyAlias', 'MyTable.MyField MyAlias'],
        ];
    }

    #[DataProvider('dataProviderTestIn')]
    public function testIn(string $driver, string $driver_folder, string $syntax, mixed $values, string $cast, string $result): void
    {
        $con = $this->getConnection($driver, $driver_folder, $syntax);
        $sql = new \Dotclear\Database\Statement\SqlStatement($con, $syntax);

        $this->assertEquals(
            $result,
            $sql->in($values, $cast)
        );
    }

    /**
     * @return list<array>
     */
    public static function dataProviderTestIn(): array
    {
        return [
            // Numeric values
            ['mysqli', 'Mysqli', 'mysql', [1, 2, 3, 4], '', ' IN (1,2,3,4)'],
            ['mysqlimb4', 'Mysqlimb4', 'mysql', [1, 2, 3, 4], '', ' IN (1,2,3,4)'],
            ['pgsql', 'Pgsql', 'postgresql', [1, 2, 3, 4], '', ' IN (1,2,3,4)'],
            ['pdosqlite', 'PdoSqlite', 'sqlite', [1, 2, 3, 4], '', ' IN (1,2,3,4)'],
            ['pdomysql', 'PdoMysql', 'mysql', [1, 2, 3, 4], '', ' IN (1,2,3,4)'],
            ['pdomysqlmb4', 'PdoMysqlMb4', 'mysql', [1, 2, 3, 4], '', ' IN (1,2,3,4)'],
            ['pdopgsql', 'PdoPgsql', 'postgresql', [1, 2, 3, 4], '', ' IN (1,2,3,4)'],

            ['mysqli', 'Mysqli', 'mysql', [1, 2, 3, 4], 'int', ' IN (1,2,3,4)'],
            ['mysqlimb4', 'Mysqlimb4', 'mysql', [1, 2, 3, 4], 'int', ' IN (1,2,3,4)'],
            ['pgsql', 'Pgsql', 'postgresql', [1, 2, 3, 4], 'int', ' IN (1,2,3,4)'],
            ['pdosqlite', 'PdoSqlite', 'sqlite', [1, 2, 3, 4], 'int', ' IN (1,2,3,4)'],
            ['pdomysql', 'PdoMysql', 'mysql', [1, 2, 3, 4], 'int', ' IN (1,2,3,4)'],
            ['pdomysqlmb4', 'PdoMysqlMb4', 'mysql', [1, 2, 3, 4], 'int', ' IN (1,2,3,4)'],
            ['pdopgsql', 'PdoPgsql', 'postgresql', [1, 2, 3, 4], 'int', ' IN (1,2,3,4)'],

            ['mysqli', 'Mysqli', 'mysql', [1, 2, 3, 4], 'string', ' IN (\'1\',\'2\',\'3\',\'4\')'],
            ['mysqlimb4', 'Mysqlimb4', 'mysql', [1, 2, 3, 4], 'string', ' IN (\'1\',\'2\',\'3\',\'4\')'],
            ['pgsql', 'Pgsql', 'postgresql', [1, 2, 3, 4], 'string', ' IN (\'1\',\'2\',\'3\',\'4\')'],
            ['pdosqlite', 'PdoSqlite', 'sqlite', [1, 2, 3, 4], 'string', ' IN (\'1\',\'2\',\'3\',\'4\')'],
            ['pdomysql', 'PdoMysql', 'mysql', [1, 2, 3, 4], 'string', ' IN (\'1\',\'2\',\'3\',\'4\')'],
            ['pdomysqlmb4', 'PdoMysqlMb4', 'mysql', [1, 2, 3, 4], 'string', ' IN (\'1\',\'2\',\'3\',\'4\')'],
            ['pdopgsql', 'PdoPgsql', 'postgresql', [1, 2, 3, 4], 'string', ' IN (\'1\',\'2\',\'3\',\'4\')'],

            // String values
            ['mysqli', 'Mysqli', 'mysql', ['1', '2', '3', '4'], '', ' IN (\'1\',\'2\',\'3\',\'4\')'],
            ['mysqlimb4', 'Mysqlimb4', 'mysql', ['1', '2', '3', '4'], '', ' IN (\'1\',\'2\',\'3\',\'4\')'],
            ['pgsql', 'Pgsql', 'postgresql', ['1', '2', '3', '4'], '', ' IN (\'1\',\'2\',\'3\',\'4\')'],
            ['pdosqlite', 'PdoSqlite', 'sqlite', ['1', '2', '3', '4'], '', ' IN (\'1\',\'2\',\'3\',\'4\')'],
            ['pdomysql', 'PdoMysql', 'mysql', ['1', '2', '3', '4'], '', ' IN (\'1\',\'2\',\'3\',\'4\')'],
            ['pdomysqlmb4', 'PdoMysqlMb4', 'mysql', ['1', '2', '3', '4'], '', ' IN (\'1\',\'2\',\'3\',\'4\')'],
            ['pdopgsql', 'PdoPgsql', 'postgresql', ['1', '2', '3', '4'], '', ' IN (\'1\',\'2\',\'3\',\'4\')'],

            ['mysqli', 'Mysqli', 'mysql', ['1', '2', '3', '4'], 'int', ' IN (1,2,3,4)'],
            ['mysqlimb4', 'Mysqlimb4', 'mysql', ['1', '2', '3', '4'], 'int', ' IN (1,2,3,4)'],
            ['pgsql', 'Pgsql', 'postgresql', ['1', '2', '3', '4'], 'int', ' IN (1,2,3,4)'],
            ['pdosqlite', 'PdoSqlite', 'sqlite', ['1', '2', '3', '4'], 'int', ' IN (1,2,3,4)'],
            ['pdomysql', 'PdoMysql', 'mysql', ['1', '2', '3', '4'], 'int', ' IN (1,2,3,4)'],
            ['pdomysqlmb4', 'PdoMysqlMb4', 'mysql', ['1', '2', '3', '4'], 'int', ' IN (1,2,3,4)'],
            ['pdopgsql', 'PdoPgsql', 'postgresql', ['1', '2', '3', '4'], 'int', ' IN (1,2,3,4)'],

            ['mysqli', 'Mysqli', 'mysql', ['1', '2', '3', '4'], 'string', ' IN (\'1\',\'2\',\'3\',\'4\')'],
            ['mysqlimb4', 'Mysqlimb4', 'mysql', ['1', '2', '3', '4'], 'string', ' IN (\'1\',\'2\',\'3\',\'4\')'],
            ['pgsql', 'Pgsql', 'postgresql', ['1', '2', '3', '4'], 'string', ' IN (\'1\',\'2\',\'3\',\'4\')'],
            ['pdosqlite', 'PdoSqlite', 'sqlite', ['1', '2', '3', '4'], 'string', ' IN (\'1\',\'2\',\'3\',\'4\')'],
            ['pdomysql', 'PdoMysql', 'mysql', ['1', '2', '3', '4'], 'string', ' IN (\'1\',\'2\',\'3\',\'4\')'],
            ['pdomysqlmb4', 'PdoMysqlMb4', 'mysql', ['1', '2', '3', '4'], 'string', ' IN (\'1\',\'2\',\'3\',\'4\')'],
            ['pdopgsql', 'PdoPgsql', 'postgresql', ['1', '2', '3', '4'], 'string', ' IN (\'1\',\'2\',\'3\',\'4\')'],

            // Null values
            ['mysqli', 'Mysqli', 'mysql', null, '', ' IN (NULL)'],
            ['mysqlimb4', 'Mysqlimb4', 'mysql', null, '', ' IN (NULL)'],
            ['pgsql', 'Pgsql', 'postgresql', null, '', ' IN (NULL)'],
            ['pdosqlite', 'PdoSqlite', 'sqlite', null, '', ' IN (NULL)'],
            ['pdomysql', 'PdoMysql', 'mysql', null, '', ' IN (NULL)'],
            ['pdomysqlmb4', 'PdoMysqlMb4', 'mysql', null, '', ' IN (NULL)'],
            ['pdopgsql', 'PdoPgsql', 'postgresql', null, '', ' IN (NULL)'],

            ['mysqli', 'Mysqli', 'mysql', null, 'int', ' IN (NULL)'],
            ['mysqlimb4', 'Mysqlimb4', 'mysql', null, 'int', ' IN (NULL)'],
            ['pgsql', 'Pgsql', 'postgresql', null, 'int', ' IN (NULL)'],
            ['pdosqlite', 'PdoSqlite', 'sqlite', null, 'int', ' IN (NULL)'],
            ['pdomysql', 'PdoMysql', 'mysql', null, 'int', ' IN (NULL)'],
            ['pdomysqlmb4', 'PdoMysqlMb4', 'mysql', null, 'int', ' IN (NULL)'],
            ['pdopgsql', 'PdoPgsql', 'postgresql', null, 'int', ' IN (NULL)'],

            ['mysqli', 'Mysqli', 'mysql', null, 'string', ' IN (NULL)'],
            ['mysqlimb4', 'Mysqlimb4', 'mysql', null, 'string', ' IN (NULL)'],
            ['pgsql', 'Pgsql', 'postgresql', null, 'string', ' IN (NULL)'],
            ['pdosqlite', 'PdoSqlite', 'sqlite', null, 'string', ' IN (NULL)'],
            ['pdomysql', 'PdoMysql', 'mysql', null, 'string', ' IN (NULL)'],
            ['pdomysqlmb4', 'PdoMysqlMb4', 'mysql', null, 'string', ' IN (NULL)'],
            ['pdopgsql', 'PdoPgsql', 'postgresql', null, 'string', ' IN (NULL)'],

            // Single int value
            ['mysqli', 'Mysqli', 'mysql', 0, '', ' IN (0)'],
            ['mysqlimb4', 'Mysqlimb4', 'mysql', 0, '', ' IN (0)'],
            ['pgsql', 'Pgsql', 'postgresql', 0, '', ' IN (0)'],
            ['pdosqlite', 'PdoSqlite', 'sqlite', 0, '', ' IN (0)'],
            ['pdomysql', 'PdoMysql', 'mysql', 0, '', ' IN (0)'],
            ['pdomysqlmb4', 'PdoMysqlMb4', 'mysql', 0, '', ' IN (0)'],
            ['pdopgsql', 'PdoPgsql', 'postgresql', 0, '', ' IN (0)'],

            ['mysqli', 'Mysqli', 'mysql', 0, 'int', ' IN (0)'],
            ['mysqlimb4', 'Mysqlimb4', 'mysql', 0, 'int', ' IN (0)'],
            ['pgsql', 'Pgsql', 'postgresql', 0, 'int', ' IN (0)'],
            ['pdosqlite', 'PdoSqlite', 'sqlite', 0, 'int', ' IN (0)'],
            ['pdomysql', 'PdoMysql', 'mysql', 0, 'int', ' IN (0)'],
            ['pdomysqlmb4', 'PdoMysqlMb4', 'mysql', 0, 'int', ' IN (0)'],
            ['pdopgsql', 'PdoPgsql', 'postgresql', 0, 'int', ' IN (0)'],

            ['mysqli', 'Mysqli', 'mysql', 0, 'string', ' IN (\'0\')'],
            ['mysqlimb4', 'Mysqlimb4', 'mysql', 0, 'string', ' IN (\'0\')'],
            ['pgsql', 'Pgsql', 'postgresql', 0, 'string', ' IN (\'0\')'],
            ['pdosqlite', 'PdoSqlite', 'sqlite', 0, 'string', ' IN (\'0\')'],
            ['pdomysql', 'PdoMysql', 'mysql', 0, 'string', ' IN (\'0\')'],
            ['pdomysqlmb4', 'PdoMysqlMb4', 'mysql', 0, 'string', ' IN (\'0\')'],
            ['pdopgsql', 'PdoPgsql', 'postgresql', 0, 'string', ' IN (\'0\')'],

            // Single string value
            ['mysqli', 'Mysqli', 'mysql', '0', '', ' IN (\'0\')'],
            ['mysqlimb4', 'Mysqlimb4', 'mysql', '0', '', ' IN (\'0\')'],
            ['pgsql', 'Pgsql', 'postgresql', '0', '', ' IN (\'0\')'],
            ['pdosqlite', 'PdoSqlite', 'sqlite', '0', '', ' IN (\'0\')'],
            ['pdomysql', 'PdoMysql', 'mysql', '0', '', ' IN (\'0\')'],
            ['pdomysqlmb4', 'PdoMysqlMb4', 'mysql', '0', '', ' IN (\'0\')'],
            ['pdopgsql', 'PdoPgsql', 'postgresql', '0', '', ' IN (\'0\')'],

            ['mysqli', 'Mysqli', 'mysql', '0', 'int', ' IN (0)'],
            ['mysqlimb4', 'Mysqlimb4', 'mysql', '0', 'int', ' IN (0)'],
            ['pgsql', 'Pgsql', 'postgresql', '0', 'int', ' IN (0)'],
            ['pdosqlite', 'PdoSqlite', 'sqlite', '0', 'int', ' IN (0)'],
            ['pdomysql', 'PdoMysql', 'mysql', '0', 'int', ' IN (0)'],
            ['pdomysqlmb4', 'PdoMysqlMb4', 'mysql', '0', 'int', ' IN (0)'],
            ['pdopgsql', 'PdoPgsql', 'postgresql', '0', 'int', ' IN (0)'],

            ['mysqli', 'Mysqli', 'mysql', '0', 'string', ' IN (\'0\')'],
            ['mysqlimb4', 'Mysqlimb4', 'mysql', '0', 'string', ' IN (\'0\')'],
            ['pgsql', 'Pgsql', 'postgresql', '0', 'string', ' IN (\'0\')'],
            ['pdosqlite', 'PdoSqlite', 'sqlite', '0', 'string', ' IN (\'0\')'],
            ['pdomysql', 'PdoMysql', 'mysql', '0', 'string', ' IN (\'0\')'],
            ['pdomysqlmb4', 'PdoMysqlMb4', 'mysql', '0', 'string', ' IN (\'0\')'],
            ['pdopgsql', 'PdoPgsql', 'postgresql', '0', 'string', ' IN (\'0\')'],
        ];
    }

    #[DataProvider('dataProviderTestDateFormat')]
    public function testDateFormat(string $driver, string $driver_folder, string $syntax, string $field, string $pattern, string $result): void
    {
        $con = $this->getConnection($driver, $driver_folder, $syntax);
        $sql = new \Dotclear\Database\Statement\SqlStatement($con, $syntax);

        $this->assertEquals(
            $result,
            $sql->dateFormat($field, $pattern)
        );
    }

    /**
     * @return list<array>
     */
    public static function dataProviderTestDateFormat(): array
    {
        return [
            ['mysqli', 'Mysqli', 'mysql', 'MyTable.MyField', '%Y/%m/%d %H:%M:%S', 'DATE_FORMAT(MyTable.MyField,\'%Y/%m/%d %H:%i:%S\')'],
            ['mysqlimb4', 'Mysqlimb4', 'mysql', 'MyTable.MyField', '%Y/%m/%d %H:%M:%S', 'DATE_FORMAT(MyTable.MyField,\'%Y/%m/%d %H:%i:%S\')'],
            ['pgsql', 'Pgsql', 'postgresql', 'MyTable.MyField', '%Y/%m/%d %H:%M:%S', 'TO_CHAR(MyTable.MyField,\'YYYY/MM/DD HH24:MI:SS\')'],
            ['pdosqlite', 'PdoSqlite', 'sqlite', 'MyTable.MyField', '%Y/%m/%d %H:%M:%S', 'strftime(\'%Y/%m/%d %H:%M:%S\',MyTable.MyField)'],
            ['pdomysql', 'PdoMysql', 'mysql', 'MyTable.MyField', '%Y/%m/%d %H:%M:%S', 'DATE_FORMAT(MyTable.MyField,\'%Y/%m/%d %H:%i:%S\')'],
            ['pdomysqlmb4', 'PdoMysqlMb4', 'mysql', 'MyTable.MyField', '%Y/%m/%d %H:%M:%S', 'DATE_FORMAT(MyTable.MyField,\'%Y/%m/%d %H:%i:%S\')'],
            ['pdopgsql', 'PdoPgsql', 'postgresql', 'MyTable.MyField', '%Y/%m/%d %H:%M:%S', 'TO_CHAR(MyTable.MyField,\'YYYY/MM/DD HH24:MI:SS\')'],
        ];
    }

    #[DataProvider('dataProviderTestLike')]
    public function testLike(string $driver, string $driver_folder, string $syntax, string $field, string $pattern, string $result): void
    {
        $con = $this->getConnection($driver, $driver_folder, $syntax);
        $sql = new \Dotclear\Database\Statement\SqlStatement($con, $syntax);

        $this->assertEquals(
            $result,
            $sql->like($field, $pattern)
        );
    }

    /**
     * @return list<array>
     */
    public static function dataProviderTestLike(): array
    {
        return [
            ['mysqli', 'Mysqli', 'mysql', 'MyTable.MyField', 't*s_t', 'MyTable.MyField LIKE \'t*s_t\''],
            ['mysqlimb4', 'Mysqlimb4', 'mysql', 'MyTable.MyField', 't*s_t', 'MyTable.MyField LIKE \'t*s_t\''],
            ['pgsql', 'Pgsql', 'postgresql', 'MyTable.MyField', 't*s_t', 'MyTable.MyField LIKE \'t*s_t\''],
            ['pdosqlite', 'PdoSqlite', 'sqlite', 'MyTable.MyField', 't*s_t', 'MyTable.MyField LIKE \'t*s_t\''],
            ['pdomysql', 'PdoMysql', 'mysql', 'MyTable.MyField', 't*s_t', 'MyTable.MyField LIKE \'t*s_t\''],
            ['pdomysqlmb4', 'PdoMysqlMb4', 'mysql', 'MyTable.MyField', 't*s_t', 'MyTable.MyField LIKE \'t*s_t\''],
            ['pdopgsql', 'PdoPgsql', 'postgresql', 'MyTable.MyField', 't*s_t', 'MyTable.MyField LIKE \'t*s_t\''],
        ];
    }

    #[DataProvider('dataProviderTestRegexp')]
    public function testRegexp(string $driver, string $driver_folder, string $syntax, string $pattern, string $result): void
    {
        $con = $this->getConnection($driver, $driver_folder, $syntax);
        $sql = new \Dotclear\Database\Statement\SqlStatement($con, $syntax);

        $this->assertEquals(
            $result,
            $sql->regexp($pattern)
        );
    }

    /**
     * @return list<array>
     */
    public static function dataProviderTestRegexp(): array
    {
        return [
            ['mysqli', 'Mysqli', 'mysql', '([A-Za-z0-9]+)', ' REGEXP \'^\\\\(\\\\[A\\\\-Za\\\\-z0\\\\-9\\\\]\\\\+\\\\)[0-9]+$\''],
            ['mysqlimb4', 'Mysqlimb4', 'mysql', '([A-Za-z0-9]+)', ' REGEXP \'^\\\\(\\\\[A\\\\-Za\\\\-z0\\\\-9\\\\]\\\\+\\\\)[0-9]+$\''],
            ['pgsql', 'Pgsql', 'postgresql', '([A-Za-z0-9]+)', ' ~ \'^\\\\(\\\\[A\\\\-Za\\\\-z0\\\\-9\\\\]\\\\+\\\\)[0-9]+$\''],
            ['pdosqlite', 'PdoSqlite', 'sqlite', '([A-Za-z0-9]+)', ' LIKE \'([A-Za-z0-9]+)%\' ESCAPE \'!\''],
            ['pdomysql', 'PdoMysql', 'mysql', '([A-Za-z0-9]+)', ' REGEXP \'^\\\\(\\\\[A\\\\-Za\\\\-z0\\\\-9\\\\]\\\\+\\\\)[0-9]+$\''],
            ['pdomysqlmb4', 'PdoMysqlMb4', 'mysql', '([A-Za-z0-9]+)', ' REGEXP \'^\\\\(\\\\[A\\\\-Za\\\\-z0\\\\-9\\\\]\\\\+\\\\)[0-9]+$\''],
            ['pdopgsql', 'PdoPgsql', 'postgresql', '([A-Za-z0-9]+)', ' ~ \'^\\\\(\\\\[A\\\\-Za\\\\-z0\\\\-9\\\\]\\\\+\\\\)[0-9]+$\''],
        ];
    }

    #[DataProvider('dataProviderTestUnique')]
    public function testUnique(string $driver, string $driver_folder, string $syntax, string $field, string $result): void
    {
        $con = $this->getConnection($driver, $driver_folder, $syntax);
        $sql = new \Dotclear\Database\Statement\SqlStatement($con, $syntax);

        $this->assertEquals(
            $result,
            $sql->unique($field)
        );
    }

    /**
     * @return list<array>
     */
    public static function dataProviderTestUnique(): array
    {
        return [
            ['mysqli', 'Mysqli', 'mysql', 'MyTable.MyField', 'DISTINCT MyTable.MyField'],
            ['mysqlimb4', 'Mysqlimb4', 'mysql', 'MyTable.MyField', 'DISTINCT MyTable.MyField'],
            ['pgsql', 'Pgsql', 'postgresql', 'MyTable.MyField', 'DISTINCT MyTable.MyField'],
            ['pdosqlite', 'PdoSqlite', 'sqlite', 'MyTable.MyField', 'DISTINCT MyTable.MyField'],
            ['pdomysql', 'PdoMysql', 'mysql', 'MyTable.MyField', 'DISTINCT MyTable.MyField'],
            ['pdomysqlmb4', 'PdoMysqlMb4', 'mysql', 'MyTable.MyField', 'DISTINCT MyTable.MyField'],
            ['pdopgsql', 'PdoPgsql', 'postgresql', 'MyTable.MyField', 'DISTINCT MyTable.MyField'],
        ];
    }

    #[DataProvider('dataProviderTestCount')]
    public function testCount(string $driver, string $driver_folder, string $syntax, string $field, ?string $alias, bool $unique, string $result): void
    {
        $con = $this->getConnection($driver, $driver_folder, $syntax);
        $sql = new \Dotclear\Database\Statement\SqlStatement($con, $syntax);

        $this->assertEquals(
            $result,
            $sql->count($field, $alias, $unique)
        );
    }

    /**
     * @return list<array>
     */
    public static function dataProviderTestCount(): array
    {
        return [
            // With alias and unique
            ['mysqli', 'Mysqli', 'mysql', 'MyTable.MyField', 'MyAlias', true, 'COUNT(DISTINCT MyTable.MyField) MyAlias'],
            ['mysqlimb4', 'Mysqlimb4', 'mysql', 'MyTable.MyField', 'MyAlias', true, 'COUNT(DISTINCT MyTable.MyField) MyAlias'],
            ['pgsql', 'Pgsql', 'postgresql', 'MyTable.MyField', 'MyAlias', true, 'COUNT(DISTINCT MyTable.MyField) MyAlias'],
            ['pdosqlite', 'PdoSqlite', 'sqlite', 'MyTable.MyField', 'MyAlias', true, 'COUNT(DISTINCT MyTable.MyField) AS MyAlias'],
            ['pdomysql', 'PdoMysql', 'mysql', 'MyTable.MyField', 'MyAlias', true, 'COUNT(DISTINCT MyTable.MyField) MyAlias'],
            ['pdomysqlmb4', 'PdoMysqlMb4', 'mysql', 'MyTable.MyField', 'MyAlias', true, 'COUNT(DISTINCT MyTable.MyField) MyAlias'],
            ['pdopgsql', 'PdoPgsql', 'postgresql', 'MyTable.MyField', 'MyAlias', true, 'COUNT(DISTINCT MyTable.MyField) MyAlias'],

            // With alias
            ['mysqli', 'Mysqli', 'mysql', 'MyTable.MyField', 'MyAlias', false, 'COUNT(MyTable.MyField) MyAlias'],
            ['mysqlimb4', 'Mysqlimb4', 'mysql', 'MyTable.MyField', 'MyAlias', false, 'COUNT(MyTable.MyField) MyAlias'],
            ['pgsql', 'Pgsql', 'postgresql', 'MyTable.MyField', 'MyAlias', false, 'COUNT(MyTable.MyField) MyAlias'],
            ['pdosqlite', 'PdoSqlite', 'sqlite', 'MyTable.MyField', 'MyAlias', false, 'COUNT(MyTable.MyField) AS MyAlias'],
            ['pdomysql', 'PdoMysql', 'mysql', 'MyTable.MyField', 'MyAlias', false, 'COUNT(MyTable.MyField) MyAlias'],
            ['pdomysqlmb4', 'PdoMysqlMb4', 'mysql', 'MyTable.MyField', 'MyAlias', false, 'COUNT(MyTable.MyField) MyAlias'],
            ['pdopgsql', 'PdoPgsql', 'postgresql', 'MyTable.MyField', 'MyAlias', false, 'COUNT(MyTable.MyField) MyAlias'],

            // With unique
            ['mysqli', 'Mysqli', 'mysql', 'MyTable.MyField', null, true, 'COUNT(DISTINCT MyTable.MyField)'],
            ['mysqlimb4', 'Mysqlimb4', 'mysql', 'MyTable.MyField', null, true, 'COUNT(DISTINCT MyTable.MyField)'],
            ['pgsql', 'Pgsql', 'postgresql', 'MyTable.MyField', null, true, 'COUNT(DISTINCT MyTable.MyField)'],
            ['pdosqlite', 'PdoSqlite', 'sqlite', 'MyTable.MyField', null, true, 'COUNT(DISTINCT MyTable.MyField)'],
            ['pdomysql', 'PdoMysql', 'mysql', 'MyTable.MyField', null, true, 'COUNT(DISTINCT MyTable.MyField)'],
            ['pdomysqlmb4', 'PdoMysqlMb4', 'mysql', 'MyTable.MyField', null, true, 'COUNT(DISTINCT MyTable.MyField)'],
            ['pdopgsql', 'PdoPgsql', 'postgresql', 'MyTable.MyField', null, true, 'COUNT(DISTINCT MyTable.MyField)'],

            // Nothing
            ['mysqli', 'Mysqli', 'mysql', 'MyTable.MyField', null, false, 'COUNT(MyTable.MyField)'],
            ['mysqlimb4', 'Mysqlimb4', 'mysql', 'MyTable.MyField', null, false, 'COUNT(MyTable.MyField)'],
            ['pgsql', 'Pgsql', 'postgresql', 'MyTable.MyField', null, false, 'COUNT(MyTable.MyField)'],
            ['pdosqlite', 'PdoSqlite', 'sqlite', 'MyTable.MyField', null, false, 'COUNT(MyTable.MyField)'],
            ['pdomysql', 'PdoMysql', 'mysql', 'MyTable.MyField', null, false, 'COUNT(MyTable.MyField)'],
            ['pdomysqlmb4', 'PdoMysqlMb4', 'mysql', 'MyTable.MyField', null, false, 'COUNT(MyTable.MyField)'],
            ['pdopgsql', 'PdoPgsql', 'postgresql', 'MyTable.MyField', null, false, 'COUNT(MyTable.MyField)'],
        ];
    }

    #[DataProvider('dataProviderTestAvg')]
    public function testAvg(string $driver, string $driver_folder, string $syntax, string $field, ?string $alias, string $result): void
    {
        $con = $this->getConnection($driver, $driver_folder, $syntax);
        $sql = new \Dotclear\Database\Statement\SqlStatement($con, $syntax);

        $this->assertEquals(
            $result,
            $sql->avg($field, $alias)
        );
    }

    /**
     * @return list<array>
     */
    public static function dataProviderTestAvg(): array
    {
        return [
            // With alias
            ['mysqli', 'Mysqli', 'mysql', 'MyTable.MyField', 'MyAlias', 'AVG(MyTable.MyField) MyAlias'],
            ['mysqlimb4', 'Mysqlimb4', 'mysql', 'MyTable.MyField', 'MyAlias', 'AVG(MyTable.MyField) MyAlias'],
            ['pgsql', 'Pgsql', 'postgresql', 'MyTable.MyField', 'MyAlias', 'AVG(MyTable.MyField) MyAlias'],
            ['pdosqlite', 'PdoSqlite', 'sqlite', 'MyTable.MyField', 'MyAlias', 'AVG(MyTable.MyField) AS MyAlias'],
            ['pdomysql', 'PdoMysql', 'mysql', 'MyTable.MyField', 'MyAlias', 'AVG(MyTable.MyField) MyAlias'],
            ['pdomysqlmb4', 'PdoMysqlMb4', 'mysql', 'MyTable.MyField', 'MyAlias', 'AVG(MyTable.MyField) MyAlias'],
            ['pdopgsql', 'PdoPgsql', 'postgresql', 'MyTable.MyField', 'MyAlias', 'AVG(MyTable.MyField) MyAlias'],

            // Nothing
            ['mysqli', 'Mysqli', 'mysql', 'MyTable.MyField', null, 'AVG(MyTable.MyField)'],
            ['mysqlimb4', 'Mysqlimb4', 'mysql', 'MyTable.MyField', null, 'AVG(MyTable.MyField)'],
            ['pgsql', 'Pgsql', 'postgresql', 'MyTable.MyField', null, 'AVG(MyTable.MyField)'],
            ['pdosqlite', 'PdoSqlite', 'sqlite', 'MyTable.MyField', null, 'AVG(MyTable.MyField)'],
            ['pdomysql', 'PdoMysql', 'mysql', 'MyTable.MyField', null, 'AVG(MyTable.MyField)'],
            ['pdomysqlmb4', 'PdoMysqlMb4', 'mysql', 'MyTable.MyField', null, 'AVG(MyTable.MyField)'],
            ['pdopgsql', 'PdoPgsql', 'postgresql', 'MyTable.MyField', null, 'AVG(MyTable.MyField)'],
        ];
    }

    #[DataProvider('dataProviderTestMax')]
    public function testMax(string $driver, string $driver_folder, string $syntax, string $field, ?string $alias, string $result): void
    {
        $con = $this->getConnection($driver, $driver_folder, $syntax);
        $sql = new \Dotclear\Database\Statement\SqlStatement($con, $syntax);

        $this->assertEquals(
            $result,
            $sql->max($field, $alias)
        );
    }

    /**
     * @return list<array>
     */
    public static function dataProviderTestMax(): array
    {
        return [
            // With alias
            ['mysqli', 'Mysqli', 'mysql', 'MyTable.MyField', 'MyAlias', 'MAX(MyTable.MyField) MyAlias'],
            ['mysqlimb4', 'Mysqlimb4', 'mysql', 'MyTable.MyField', 'MyAlias', 'MAX(MyTable.MyField) MyAlias'],
            ['pgsql', 'Pgsql', 'postgresql', 'MyTable.MyField', 'MyAlias', 'MAX(MyTable.MyField) MyAlias'],
            ['pdosqlite', 'PdoSqlite', 'sqlite', 'MyTable.MyField', 'MyAlias', 'MAX(MyTable.MyField) AS MyAlias'],
            ['pdomysql', 'PdoMysql', 'mysql', 'MyTable.MyField', 'MyAlias', 'MAX(MyTable.MyField) MyAlias'],
            ['pdomysqlmb4', 'PdoMysqlMb4', 'mysql', 'MyTable.MyField', 'MyAlias', 'MAX(MyTable.MyField) MyAlias'],
            ['pdopgsql', 'PdoPgsql', 'postgresql', 'MyTable.MyField', 'MyAlias', 'MAX(MyTable.MyField) MyAlias'],

            // Nothing
            ['mysqli', 'Mysqli', 'mysql', 'MyTable.MyField', null, 'MAX(MyTable.MyField)'],
            ['mysqlimb4', 'Mysqlimb4', 'mysql', 'MyTable.MyField', null, 'MAX(MyTable.MyField)'],
            ['pgsql', 'Pgsql', 'postgresql', 'MyTable.MyField', null, 'MAX(MyTable.MyField)'],
            ['pdosqlite', 'PdoSqlite', 'sqlite', 'MyTable.MyField', null, 'MAX(MyTable.MyField)'],
            ['pdomysql', 'PdoMysql', 'mysql', 'MyTable.MyField', null, 'MAX(MyTable.MyField)'],
            ['pdomysqlmb4', 'PdoMysqlMb4', 'mysql', 'MyTable.MyField', null, 'MAX(MyTable.MyField)'],
            ['pdopgsql', 'PdoPgsql', 'postgresql', 'MyTable.MyField', null, 'MAX(MyTable.MyField)'],
        ];
    }

    #[DataProvider('dataProviderTestMin')]
    public function testMin(string $driver, string $driver_folder, string $syntax, string $field, ?string $alias, string $result): void
    {
        $con = $this->getConnection($driver, $driver_folder, $syntax);
        $sql = new \Dotclear\Database\Statement\SqlStatement($con, $syntax);

        $this->assertEquals(
            $result,
            $sql->min($field, $alias)
        );
    }

    /**
     * @return list<array>
     */
    public static function dataProviderTestMin(): array
    {
        return [
            // With alias
            ['mysqli', 'Mysqli', 'mysql', 'MyTable.MyField', 'MyAlias', 'MIN(MyTable.MyField) MyAlias'],
            ['mysqlimb4', 'Mysqlimb4', 'mysql', 'MyTable.MyField', 'MyAlias', 'MIN(MyTable.MyField) MyAlias'],
            ['pgsql', 'Pgsql', 'postgresql', 'MyTable.MyField', 'MyAlias', 'MIN(MyTable.MyField) MyAlias'],
            ['pdosqlite', 'PdoSqlite', 'sqlite', 'MyTable.MyField', 'MyAlias', 'MIN(MyTable.MyField) AS MyAlias'],
            ['pdomysql', 'PdoMysql', 'mysql', 'MyTable.MyField', 'MyAlias', 'MIN(MyTable.MyField) MyAlias'],
            ['pdomysqlmb4', 'PdoMysqlMb4', 'mysql', 'MyTable.MyField', 'MyAlias', 'MIN(MyTable.MyField) MyAlias'],
            ['pdopgsql', 'PdoPgsql', 'postgresql', 'MyTable.MyField', 'MyAlias', 'MIN(MyTable.MyField) MyAlias'],

            // Nothing
            ['mysqli', 'Mysqli', 'mysql', 'MyTable.MyField', null, 'MIN(MyTable.MyField)'],
            ['mysqlimb4', 'Mysqlimb4', 'mysql', 'MyTable.MyField', null, 'MIN(MyTable.MyField)'],
            ['pgsql', 'Pgsql', 'postgresql', 'MyTable.MyField', null, 'MIN(MyTable.MyField)'],
            ['pdosqlite', 'PdoSqlite', 'sqlite', 'MyTable.MyField', null, 'MIN(MyTable.MyField)'],
            ['pdomysql', 'PdoMysql', 'mysql', 'MyTable.MyField', null, 'MIN(MyTable.MyField)'],
            ['pdomysqlmb4', 'PdoMysqlMb4', 'mysql', 'MyTable.MyField', null, 'MIN(MyTable.MyField)'],
            ['pdopgsql', 'PdoPgsql', 'postgresql', 'MyTable.MyField', null, 'MIN(MyTable.MyField)'],
        ];
    }

    #[DataProvider('dataProviderTestSum')]
    public function testSum(string $driver, string $driver_folder, string $syntax, string $field, ?string $alias, string $result): void
    {
        $con = $this->getConnection($driver, $driver_folder, $syntax);
        $sql = new \Dotclear\Database\Statement\SqlStatement($con, $syntax);

        $this->assertEquals(
            $result,
            $sql->sum($field, $alias)
        );
    }

    /**
     * @return list<array>
     */
    public static function dataProviderTestSum(): array
    {
        return [
            // With alias
            ['mysqli', 'Mysqli', 'mysql', 'MyTable.MyField', 'MyAlias', 'SUM(MyTable.MyField) MyAlias'],
            ['mysqlimb4', 'Mysqlimb4', 'mysql', 'MyTable.MyField', 'MyAlias', 'SUM(MyTable.MyField) MyAlias'],
            ['pgsql', 'Pgsql', 'postgresql', 'MyTable.MyField', 'MyAlias', 'SUM(MyTable.MyField) MyAlias'],
            ['pdosqlite', 'PdoSqlite', 'sqlite', 'MyTable.MyField', 'MyAlias', 'SUM(MyTable.MyField) AS MyAlias'],
            ['pdomysql', 'PdoMysql', 'mysql', 'MyTable.MyField', 'MyAlias', 'SUM(MyTable.MyField) MyAlias'],
            ['pdomysqlmb4', 'PdoMysqlMb4', 'mysql', 'MyTable.MyField', 'MyAlias', 'SUM(MyTable.MyField) MyAlias'],
            ['pdopgsql', 'PdoPgsql', 'postgresql', 'MyTable.MyField', 'MyAlias', 'SUM(MyTable.MyField) MyAlias'],

            // Nothing
            ['mysqli', 'Mysqli', 'mysql', 'MyTable.MyField', null, 'SUM(MyTable.MyField)'],
            ['mysqlimb4', 'Mysqlimb4', 'mysql', 'MyTable.MyField', null, 'SUM(MyTable.MyField)'],
            ['pgsql', 'Pgsql', 'postgresql', 'MyTable.MyField', null, 'SUM(MyTable.MyField)'],
            ['pdosqlite', 'PdoSqlite', 'sqlite', 'MyTable.MyField', null, 'SUM(MyTable.MyField)'],
            ['pdomysql', 'PdoMysql', 'mysql', 'MyTable.MyField', null, 'SUM(MyTable.MyField)'],
            ['pdomysqlmb4', 'PdoMysqlMb4', 'mysql', 'MyTable.MyField', null, 'SUM(MyTable.MyField)'],
            ['pdopgsql', 'PdoPgsql', 'postgresql', 'MyTable.MyField', null, 'SUM(MyTable.MyField)'],
        ];
    }

    #[DataProvider('dataProviderTestIsNull')]
    public function testIsNull(string $driver, string $driver_folder, string $syntax, string $field, string $result): void
    {
        $con = $this->getConnection($driver, $driver_folder, $syntax);
        $sql = new \Dotclear\Database\Statement\SqlStatement($con, $syntax);

        $this->assertEquals(
            $result,
            $sql->isNull($field)
        );
    }

    /**
     * @return list<array>
     */
    public static function dataProviderTestIsNull(): array
    {
        return [
            ['mysqli', 'Mysqli', 'mysql', 'MyTable.MyField', 'MyTable.MyField IS NULL'],
            ['mysqlimb4', 'Mysqlimb4', 'mysql', 'MyTable.MyField', 'MyTable.MyField IS NULL'],
            ['pgsql', 'Pgsql', 'postgresql', 'MyTable.MyField', 'MyTable.MyField IS NULL'],
            ['pdosqlite', 'PdoSqlite', 'sqlite', 'MyTable.MyField', 'MyTable.MyField IS NULL'],
            ['pdomysql', 'PdoMysql', 'mysql', 'MyTable.MyField', 'MyTable.MyField IS NULL'],
            ['pdomysqlmb4', 'PdoMysqlMb4', 'mysql', 'MyTable.MyField', 'MyTable.MyField IS NULL'],
            ['pdopgsql', 'PdoPgsql', 'postgresql', 'MyTable.MyField', 'MyTable.MyField IS NULL'],
        ];
    }

    #[DataProvider('dataProviderTestIsNotNull')]
    public function testIsNotNull(string $driver, string $driver_folder, string $syntax, string $field, string $result): void
    {
        $con = $this->getConnection($driver, $driver_folder, $syntax);
        $sql = new \Dotclear\Database\Statement\SqlStatement($con, $syntax);

        $this->assertEquals(
            $result,
            $sql->isNotNull($field)
        );
    }

    /**
     * @return list<array>
     */
    public static function dataProviderTestIsNotNull(): array
    {
        return [
            ['mysqli', 'Mysqli', 'mysql', 'MyTable.MyField', 'MyTable.MyField IS NOT NULL'],
            ['mysqlimb4', 'Mysqlimb4', 'mysql', 'MyTable.MyField', 'MyTable.MyField IS NOT NULL'],
            ['pgsql', 'Pgsql', 'postgresql', 'MyTable.MyField', 'MyTable.MyField IS NOT NULL'],
            ['pdosqlite', 'PdoSqlite', 'sqlite', 'MyTable.MyField', 'MyTable.MyField IS NOT NULL'],
            ['pdomysql', 'PdoMysql', 'mysql', 'MyTable.MyField', 'MyTable.MyField IS NOT NULL'],
            ['pdomysqlmb4', 'PdoMysqlMb4', 'mysql', 'MyTable.MyField', 'MyTable.MyField IS NOT NULL'],
            ['pdopgsql', 'PdoPgsql', 'postgresql', 'MyTable.MyField', 'MyTable.MyField IS NOT NULL'],
        ];
    }

    #[DataProvider('dataProviderTest')]
    public function testMagic(string $driver, string $driver_folder, string $syntax): void
    {
        $con = $this->getConnection($driver, $driver_folder, $syntax);
        $sql = new \Dotclear\Database\Statement\SqlStatement($con, $syntax);

        // @phpstan-ignore property.protected
        $sql->syntax = 'Hello';

        $this->assertEquals(
            'Hello',
            // @phpstan-ignore property.protected
            $sql->syntax
        );
        // @phpstan-ignore method.alreadyNarrowedType
        $this->assertTrue(
            // @phpstan-ignore isset.property, property.protected
            isset($sql->syntax)
        );

        // @phpstan-ignore property.protected
        unset($sql->syntax);

        $this->assertFalse(
            // @phpstan-ignore property.protected
            isset($sql->syntax)
        );
    }

    #[DataProvider('dataProviderTest')]
    public function testMagicSetError(string $driver, string $driver_folder, string $syntax): void
    {
        $con = $this->getConnection($driver, $driver_folder, $syntax);
        $sql = new \Dotclear\Database\Statement\SqlStatement($con, $syntax);

        $this->assertFalse(
            // @phpstan-ignore property.notFound
            isset($sql->syntaxEngine)
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unknown property syntaxEngine');

        // @phpstan-ignore property.notFound
        $sql->syntaxEngine = 'Hello';
    }

    #[DataProvider('dataProviderTest')]
    public function testMagicGetError(string $driver, string $driver_folder, string $syntax): void
    {
        $con = $this->getConnection($driver, $driver_folder, $syntax);
        $sql = new \Dotclear\Database\Statement\SqlStatement($con, $syntax);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unknown property syntaxEngine');

        // @phpstan-ignore property.notFound
        if ($sql->syntaxEngine === 'Hello')
        ;
    }

    #[DataProvider('dataProviderTest')]
    public function testAnd(string $driver, string $driver_folder, string $syntax): void
    {
        $con = $this->getConnection($driver, $driver_folder, $syntax);
        $sql = new \Dotclear\Database\Statement\SqlStatement($con, $syntax);

        $this->assertEquals(
            '',
            $sql->and([])->andGroup('')
        );
        $this->assertEquals(
            '',
            $sql->and('')->andGroup([])
        );
        $this->assertEquals(
            '',
            $sql->and(null)->andGroup([''])
        );
    }

    #[DataProvider('dataProviderTest')]
    public function testOr(string $driver, string $driver_folder, string $syntax): void
    {
        $con = $this->getConnection($driver, $driver_folder, $syntax);
        $sql = new \Dotclear\Database\Statement\SqlStatement($con, $syntax);

        $this->assertEquals(
            '',
            $sql->or([])->orGroup('')
        );
        $this->assertEquals(
            '',
            $sql->or('')->orGroup([])
        );
        $this->assertEquals(
            '',
            $sql->or(null)->orGroup([''])
        );
    }
}
