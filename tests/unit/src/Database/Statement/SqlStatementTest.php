<?php

declare(strict_types=1);

namespace Dotclear\Tests\Database\Statement;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SqlStatementTest extends TestCase
{
    private function getConnection(string $driver, string $syntax)
    {
        // Build a mock handler for the driver
        $driverClass = ucfirst($driver);
        $mock        = $this->getMockBuilder("Dotclear\\Database\\Driver\\$driverClass\\Handler")
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
    public function test($driver, $syntax)
    {
        $con = $this->getConnection($driver, $syntax);
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

    #[DataProvider('dataProviderTestEscape')]
    public function testEscape($driver, $syntax, $source, $result)
    {
        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\SqlStatement($con, $syntax);

        $this->assertEquals(
            $result,
            $sql->escape($source)
        );
    }

    public static function dataProviderTestEscape()
    {
        return [
            ['mysqli', 'mysql', 'test', 'test'],
            ['mysqlimb4', 'mysql', 'test', 'test'],
            ['pgsql', 'postgresql', 'test', 'test'],
            ['sqlite', 'sqlite', 'test', 'test'],
            ['mysqli', 'mysql', 't"e\'s`t', 't\"e\\\'s`t'],
            ['mysqlimb4', 'mysql', 't"e\'s`t', 't\"e\\\'s`t'],
            ['pgsql', 'postgresql', 't"e\'s`t', 't\"e\\\'s`t'],
            ['sqlite', 'sqlite', 't"e\'s`t', 't\"e\\\'s`t'],
            ['mysqli', 'mysql', 't%es_t*', 't%es_t*'],
            ['mysqlimb4', 'mysql', 't%es_t*', 't%es_t*'],
            ['pgsql', 'postgresql', 't%es_t*', 't%es_t*'],
            ['sqlite', 'sqlite', 't%es_t*', 't%es_t*'],
        ];
    }

    #[DataProvider('dataProviderTestQuote')]
    public function testQuote($driver, $syntax, $source, $result, $escape)
    {
        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\SqlStatement($con, $syntax);

        $this->assertEquals(
            $result,
            $sql->quote($source, $escape)
        );
    }

    public static function dataProviderTestQuote()
    {
        return [
            ['mysqli', 'mysql', 'test', '\'test\'', true],
            ['mysqlimb4', 'mysql', 'test', '\'test\'', true],
            ['pgsql', 'postgresql', 'test', '\'test\'', true],
            ['sqlite', 'sqlite', 'test', '\'test\'', true],
            ['mysqli', 'mysql', 'test', '\'test\'', false],
            ['mysqlimb4', 'mysql', 'test', '\'test\'', false],
            ['pgsql', 'postgresql', 'test', '\'test\'', false],
            ['sqlite', 'sqlite', 'test', '\'test\'', false],
            ['mysqli', 'mysql', 't"e\'st', '\'t\"e\\\'st\'', true],
            ['mysqlimb4', 'mysql', 't"e\'st', '\'t\"e\\\'st\'', true],
            ['pgsql', 'postgresql', 't"e\'st', '\'t\"e\\\'st\'', true],
            ['sqlite', 'sqlite', 't"e\'st', '\'t\"e\\\'st\'', true],
            ['mysqli', 'mysql', 't"e\'st', '\'t"e\'st\'', false],
            ['mysqlimb4', 'mysql', 't"e\'st', '\'t"e\'st\'', false],
            ['pgsql', 'postgresql', 't"e\'st', '\'t"e\'st\'', false],
            ['sqlite', 'sqlite', 't"e\'st', '\'t"e\'st\'', false],
        ];
    }

    #[DataProvider('dataProviderTestAlias')]
    public function testAlias($driver, $syntax, $name, $alias, $result)
    {
        $con = $this->getConnection($driver, $syntax);
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

    public static function dataProviderTestAlias()
    {
        return [
            ['mysqli', 'mysql', 'MyTable.MyField', 'MyAlias', 'MyTable.MyField MyAlias'],
            ['mysqlimb4', 'mysql', 'MyTable.MyField', 'MyAlias', 'MyTable.MyField MyAlias'],
            ['pgsql', 'postgresql', 'MyTable.MyField', 'MyAlias', 'MyTable.MyField MyAlias'],
            ['sqlite', 'sqlite', 'MyTable.MyField', 'MyAlias', 'MyTable.MyField AS MyAlias'],
        ];
    }

    #[DataProvider('dataProviderTestIn')]
    public function testIn($driver, $syntax, $values, $cast, $result)
    {
        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\SqlStatement($con, $syntax);

        $this->assertEquals(
            $result,
            $sql->in($values, $cast)
        );
    }

    public static function dataProviderTestIn()
    {
        return [
            // Numeric values
            ['mysqli', 'mysql', [1, 2, 3, 4], '', ' IN (1,2,3,4)'],
            ['mysqlimb4', 'mysql', [1, 2, 3, 4], '', ' IN (1,2,3,4)'],
            ['pgsql', 'postgresql', [1, 2, 3, 4], '', ' IN (1,2,3,4)'],
            ['sqlite', 'sqlite', [1, 2, 3, 4], '', ' IN (1,2,3,4)'],
            ['mysqli', 'mysql', [1, 2, 3, 4], 'int', ' IN (1,2,3,4)'],
            ['mysqlimb4', 'mysql', [1, 2, 3, 4], 'int', ' IN (1,2,3,4)'],
            ['pgsql', 'postgresql', [1, 2, 3, 4], 'int', ' IN (1,2,3,4)'],
            ['sqlite', 'sqlite', [1, 2, 3, 4], 'int', ' IN (1,2,3,4)'],
            ['mysqli', 'mysql', [1, 2, 3, 4], 'string', ' IN (\'1\',\'2\',\'3\',\'4\')'],
            ['mysqlimb4', 'mysql', [1, 2, 3, 4], 'string', ' IN (\'1\',\'2\',\'3\',\'4\')'],
            ['pgsql', 'postgresql', [1, 2, 3, 4], 'string', ' IN (\'1\',\'2\',\'3\',\'4\')'],
            ['sqlite', 'sqlite', [1, 2, 3, 4], 'string', ' IN (\'1\',\'2\',\'3\',\'4\')'],
            // String values
            ['mysqli', 'mysql', ['1', '2', '3', '4'], '', ' IN (\'1\',\'2\',\'3\',\'4\')'],
            ['mysqlimb4', 'mysql', ['1', '2', '3', '4'], '', ' IN (\'1\',\'2\',\'3\',\'4\')'],
            ['pgsql', 'postgresql', ['1', '2', '3', '4'], '', ' IN (\'1\',\'2\',\'3\',\'4\')'],
            ['sqlite', 'sqlite', ['1', '2', '3', '4'], '', ' IN (\'1\',\'2\',\'3\',\'4\')'],
            ['mysqli', 'mysql', ['1', '2', '3', '4'], 'int', ' IN (1,2,3,4)'],
            ['mysqlimb4', 'mysql', ['1', '2', '3', '4'], 'int', ' IN (1,2,3,4)'],
            ['pgsql', 'postgresql', ['1', '2', '3', '4'], 'int', ' IN (1,2,3,4)'],
            ['sqlite', 'sqlite', ['1', '2', '3', '4'], 'int', ' IN (1,2,3,4)'],
            ['mysqli', 'mysql', ['1', '2', '3', '4'], 'string', ' IN (\'1\',\'2\',\'3\',\'4\')'],
            ['mysqlimb4', 'mysql', ['1', '2', '3', '4'], 'string', ' IN (\'1\',\'2\',\'3\',\'4\')'],
            ['pgsql', 'postgresql', ['1', '2', '3', '4'], 'string', ' IN (\'1\',\'2\',\'3\',\'4\')'],
            ['sqlite', 'sqlite', ['1', '2', '3', '4'], 'string', ' IN (\'1\',\'2\',\'3\',\'4\')'],
            // Null values
            ['mysqli', 'mysql', null, '', ' IN (NULL)'],
            ['mysqlimb4', 'mysql', null, '', ' IN (NULL)'],
            ['pgsql', 'postgresql', null, '', ' IN (NULL)'],
            ['sqlite', 'sqlite', null, '', ' IN (NULL)'],
            ['mysqli', 'mysql', null, 'int', ' IN (NULL)'],
            ['mysqlimb4', 'mysql', null, 'int', ' IN (NULL)'],
            ['pgsql', 'postgresql', null, 'int', ' IN (NULL)'],
            ['sqlite', 'sqlite', null, 'int', ' IN (NULL)'],
            ['mysqli', 'mysql', null, 'string', ' IN (NULL)'],
            ['mysqlimb4', 'mysql', null, 'string', ' IN (NULL)'],
            ['pgsql', 'postgresql', null, 'string', ' IN (NULL)'],
            ['sqlite', 'sqlite', null, 'string', ' IN (NULL)'],
            // Single int value
            ['mysqli', 'mysql', 0, '', ' IN (0)'],
            ['mysqlimb4', 'mysql', 0, '', ' IN (0)'],
            ['pgsql', 'postgresql', 0, '', ' IN (0)'],
            ['sqlite', 'sqlite', 0, '', ' IN (0)'],
            ['mysqli', 'mysql', 0, 'int', ' IN (0)'],
            ['mysqlimb4', 'mysql', 0, 'int', ' IN (0)'],
            ['pgsql', 'postgresql', 0, 'int', ' IN (0)'],
            ['sqlite', 'sqlite', 0, 'int', ' IN (0)'],
            ['mysqli', 'mysql', 0, 'string', ' IN (\'0\')'],
            ['mysqlimb4', 'mysql', 0, 'string', ' IN (\'0\')'],
            ['pgsql', 'postgresql', 0, 'string', ' IN (\'0\')'],
            ['sqlite', 'sqlite', 0, 'string', ' IN (\'0\')'],
            // Single string value
            ['mysqli', 'mysql', '0', '', ' IN (\'0\')'],
            ['mysqlimb4', 'mysql', '0', '', ' IN (\'0\')'],
            ['pgsql', 'postgresql', '0', '', ' IN (\'0\')'],
            ['sqlite', 'sqlite', '0', '', ' IN (\'0\')'],
            ['mysqli', 'mysql', '0', 'int', ' IN (0)'],
            ['mysqlimb4', 'mysql', '0', 'int', ' IN (0)'],
            ['pgsql', 'postgresql', '0', 'int', ' IN (0)'],
            ['sqlite', 'sqlite', '0', 'int', ' IN (0)'],
            ['mysqli', 'mysql', '0', 'string', ' IN (\'0\')'],
            ['mysqlimb4', 'mysql', '0', 'string', ' IN (\'0\')'],
            ['pgsql', 'postgresql', '0', 'string', ' IN (\'0\')'],
            ['sqlite', 'sqlite', '0', 'string', ' IN (\'0\')'],
        ];
    }

    #[DataProvider('dataProviderTestDateFormat')]
    public function testDateFormat($driver, $syntax, $field, $pattern, $result)
    {
        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\SqlStatement($con, $syntax);

        $this->assertEquals(
            $result,
            $sql->dateFormat($field, $pattern)
        );
    }

    public static function dataProviderTestDateFormat()
    {
        return [
            ['mysqli', 'mysql', 'MyTable.MyField', '%Y/%m/%d %H:%M:%S', 'DATE_FORMAT(MyTable.MyField,\'%Y/%m/%d %H:%i:%S\')'],
            ['mysqlimb4', 'mysql', 'MyTable.MyField', '%Y/%m/%d %H:%M:%S', 'DATE_FORMAT(MyTable.MyField,\'%Y/%m/%d %H:%i:%S\')'],
            ['pgsql', 'postgresql', 'MyTable.MyField', '%Y/%m/%d %H:%M:%S', 'TO_CHAR(MyTable.MyField,\'YYYY/MM/DD HH24:MI:SS\')'],
            ['sqlite', 'sqlite', 'MyTable.MyField', '%Y/%m/%d %H:%M:%S', 'strftime(\'%Y/%m/%d %H:%M:%S\',MyTable.MyField)'],
        ];
    }

    #[DataProvider('dataProviderTestLike')]
    public function testLike($driver, $syntax, $field, $pattern, $result)
    {
        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\SqlStatement($con, $syntax);

        $this->assertEquals(
            $result,
            $sql->like($field, $pattern)
        );
    }

    public static function dataProviderTestLike()
    {
        return [
            ['mysqli', 'mysql', 'MyTable.MyField', 't*s_t', 'MyTable.MyField LIKE \'t*s_t\''],
            ['mysqlimb4', 'mysql', 'MyTable.MyField', 't*s_t', 'MyTable.MyField LIKE \'t*s_t\''],
            ['pgsql', 'postgresql', 'MyTable.MyField', 't*s_t', 'MyTable.MyField LIKE \'t*s_t\''],
            ['sqlite', 'sqlite', 'MyTable.MyField', 't*s_t', 'MyTable.MyField LIKE \'t*s_t\''],
        ];
    }

    #[DataProvider('dataProviderTestRegexp')]
    public function testRegexp($driver, $syntax, $pattern, $result)
    {
        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\SqlStatement($con, $syntax);

        $this->assertEquals(
            $result,
            $sql->regexp($pattern)
        );
    }

    public static function dataProviderTestRegexp()
    {
        return [
            ['mysqli', 'mysql', '([A-Za-z0-9]+)', ' REGEXP \'^\\\\(\\\\[A\\\\-Za\\\\-z0\\\\-9\\\\]\\\\+\\\\)[0-9]+$\''],
            ['mysqlimb4', 'mysql', '([A-Za-z0-9]+)', ' REGEXP \'^\\\\(\\\\[A\\\\-Za\\\\-z0\\\\-9\\\\]\\\\+\\\\)[0-9]+$\''],
            ['pgsql', 'postgresql', '([A-Za-z0-9]+)', ' ~ \'^\\\\(\\\\[A\\\\-Za\\\\-z0\\\\-9\\\\]\\\\+\\\\)[0-9]+$\''],
            ['sqlite', 'sqlite', '([A-Za-z0-9]+)', ' LIKE \'([A-Za-z0-9]+)%\' ESCAPE \'!\''],
        ];
    }

    #[DataProvider('dataProviderTestUnique')]
    public function testUnique($driver, $syntax, $field, $result)
    {
        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\SqlStatement($con, $syntax);

        $this->assertEquals(
            $result,
            $sql->unique($field)
        );
    }

    public static function dataProviderTestUnique()
    {
        return [
            ['mysqli', 'mysql', 'MyTable.MyField', 'DISTINCT MyTable.MyField'],
            ['mysqlimb4', 'mysql', 'MyTable.MyField', 'DISTINCT MyTable.MyField'],
            ['pgsql', 'postgresql', 'MyTable.MyField', 'DISTINCT MyTable.MyField'],
            ['sqlite', 'sqlite', 'MyTable.MyField', 'DISTINCT MyTable.MyField'],
        ];
    }

    #[DataProvider('dataProviderTestCount')]
    public function testCount($driver, $syntax, $field, $alias, $unique, $result)
    {
        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\SqlStatement($con, $syntax);

        $this->assertEquals(
            $result,
            $sql->count($field, $alias, $unique)
        );
    }

    public static function dataProviderTestCount()
    {
        return [
            // With alias and unique
            ['mysqli', 'mysql', 'MyTable.MyField', 'MyAlias', true, 'COUNT(DISTINCT MyTable.MyField) MyAlias'],
            ['mysqlimb4', 'mysql', 'MyTable.MyField', 'MyAlias', true, 'COUNT(DISTINCT MyTable.MyField) MyAlias'],
            ['pgsql', 'postgresql', 'MyTable.MyField', 'MyAlias', true, 'COUNT(DISTINCT MyTable.MyField) MyAlias'],
            ['sqlite', 'sqlite', 'MyTable.MyField', 'MyAlias', true, 'COUNT(DISTINCT MyTable.MyField) AS MyAlias'],
            // With alias
            ['mysqli', 'mysql', 'MyTable.MyField', 'MyAlias', false, 'COUNT(MyTable.MyField) MyAlias'],
            ['mysqlimb4', 'mysql', 'MyTable.MyField', 'MyAlias', false, 'COUNT(MyTable.MyField) MyAlias'],
            ['pgsql', 'postgresql', 'MyTable.MyField', 'MyAlias', false, 'COUNT(MyTable.MyField) MyAlias'],
            ['sqlite', 'sqlite', 'MyTable.MyField', 'MyAlias', false, 'COUNT(MyTable.MyField) AS MyAlias'],
            // With unique
            ['mysqli', 'mysql', 'MyTable.MyField', null, true, 'COUNT(DISTINCT MyTable.MyField)'],
            ['mysqlimb4', 'mysql', 'MyTable.MyField', null, true, 'COUNT(DISTINCT MyTable.MyField)'],
            ['pgsql', 'postgresql', 'MyTable.MyField', null, true, 'COUNT(DISTINCT MyTable.MyField)'],
            ['sqlite', 'sqlite', 'MyTable.MyField', null, true, 'COUNT(DISTINCT MyTable.MyField)'],
            // Nothing
            ['mysqli', 'mysql', 'MyTable.MyField', null, false, 'COUNT(MyTable.MyField)'],
            ['mysqlimb4', 'mysql', 'MyTable.MyField', null, false, 'COUNT(MyTable.MyField)'],
            ['pgsql', 'postgresql', 'MyTable.MyField', null, false, 'COUNT(MyTable.MyField)'],
            ['sqlite', 'sqlite', 'MyTable.MyField', null, false, 'COUNT(MyTable.MyField)'],
        ];
    }

    #[DataProvider('dataProviderTestAvg')]
    public function testAvg($driver, $syntax, $field, $alias, $result)
    {
        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\SqlStatement($con, $syntax);

        $this->assertEquals(
            $result,
            $sql->avg($field, $alias)
        );
    }

    public static function dataProviderTestAvg()
    {
        return [
            // With alias
            ['mysqli', 'mysql', 'MyTable.MyField', 'MyAlias', 'AVG(MyTable.MyField) MyAlias'],
            ['mysqlimb4', 'mysql', 'MyTable.MyField', 'MyAlias', 'AVG(MyTable.MyField) MyAlias'],
            ['pgsql', 'postgresql', 'MyTable.MyField', 'MyAlias', 'AVG(MyTable.MyField) MyAlias'],
            ['sqlite', 'sqlite', 'MyTable.MyField', 'MyAlias', 'AVG(MyTable.MyField) AS MyAlias'],
            // Nothing
            ['mysqli', 'mysql', 'MyTable.MyField', null, 'AVG(MyTable.MyField)'],
            ['mysqlimb4', 'mysql', 'MyTable.MyField', null, 'AVG(MyTable.MyField)'],
            ['pgsql', 'postgresql', 'MyTable.MyField', null, 'AVG(MyTable.MyField)'],
            ['sqlite', 'sqlite', 'MyTable.MyField', null, 'AVG(MyTable.MyField)'],
        ];
    }

    #[DataProvider('dataProviderTestMax')]
    public function testMax($driver, $syntax, $field, $alias, $result)
    {
        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\SqlStatement($con, $syntax);

        $this->assertEquals(
            $result,
            $sql->max($field, $alias)
        );
    }

    public static function dataProviderTestMax()
    {
        return [
            // With alias
            ['mysqli', 'mysql', 'MyTable.MyField', 'MyAlias', 'MAX(MyTable.MyField) MyAlias'],
            ['mysqlimb4', 'mysql', 'MyTable.MyField', 'MyAlias', 'MAX(MyTable.MyField) MyAlias'],
            ['pgsql', 'postgresql', 'MyTable.MyField', 'MyAlias', 'MAX(MyTable.MyField) MyAlias'],
            ['sqlite', 'sqlite', 'MyTable.MyField', 'MyAlias', 'MAX(MyTable.MyField) AS MyAlias'],
            // Nothing
            ['mysqli', 'mysql', 'MyTable.MyField', null, 'MAX(MyTable.MyField)'],
            ['mysqlimb4', 'mysql', 'MyTable.MyField', null, 'MAX(MyTable.MyField)'],
            ['pgsql', 'postgresql', 'MyTable.MyField', null, 'MAX(MyTable.MyField)'],
            ['sqlite', 'sqlite', 'MyTable.MyField', null, 'MAX(MyTable.MyField)'],
        ];
    }

    #[DataProvider('dataProviderTestMin')]
    public function testMin($driver, $syntax, $field, $alias, $result)
    {
        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\SqlStatement($con, $syntax);

        $this->assertEquals(
            $result,
            $sql->min($field, $alias)
        );
    }

    public static function dataProviderTestMin()
    {
        return [
            // With alias
            ['mysqli', 'mysql', 'MyTable.MyField', 'MyAlias', 'MIN(MyTable.MyField) MyAlias'],
            ['mysqlimb4', 'mysql', 'MyTable.MyField', 'MyAlias', 'MIN(MyTable.MyField) MyAlias'],
            ['pgsql', 'postgresql', 'MyTable.MyField', 'MyAlias', 'MIN(MyTable.MyField) MyAlias'],
            ['sqlite', 'sqlite', 'MyTable.MyField', 'MyAlias', 'MIN(MyTable.MyField) AS MyAlias'],
            // Nothing
            ['mysqli', 'mysql', 'MyTable.MyField', null, 'MIN(MyTable.MyField)'],
            ['mysqlimb4', 'mysql', 'MyTable.MyField', null, 'MIN(MyTable.MyField)'],
            ['pgsql', 'postgresql', 'MyTable.MyField', null, 'MIN(MyTable.MyField)'],
            ['sqlite', 'sqlite', 'MyTable.MyField', null, 'MIN(MyTable.MyField)'],
        ];
    }

    #[DataProvider('dataProviderTestSum')]
    public function testSum($driver, $syntax, $field, $alias, $result)
    {
        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\SqlStatement($con, $syntax);

        $this->assertEquals(
            $result,
            $sql->sum($field, $alias)
        );
    }

    public static function dataProviderTestSum()
    {
        return [
            // With alias
            ['mysqli', 'mysql', 'MyTable.MyField', 'MyAlias', 'SUM(MyTable.MyField) MyAlias'],
            ['mysqlimb4', 'mysql', 'MyTable.MyField', 'MyAlias', 'SUM(MyTable.MyField) MyAlias'],
            ['pgsql', 'postgresql', 'MyTable.MyField', 'MyAlias', 'SUM(MyTable.MyField) MyAlias'],
            ['sqlite', 'sqlite', 'MyTable.MyField', 'MyAlias', 'SUM(MyTable.MyField) AS MyAlias'],
            // Nothing
            ['mysqli', 'mysql', 'MyTable.MyField', null, 'SUM(MyTable.MyField)'],
            ['mysqlimb4', 'mysql', 'MyTable.MyField', null, 'SUM(MyTable.MyField)'],
            ['pgsql', 'postgresql', 'MyTable.MyField', null, 'SUM(MyTable.MyField)'],
            ['sqlite', 'sqlite', 'MyTable.MyField', null, 'SUM(MyTable.MyField)'],
        ];
    }

    #[DataProvider('dataProviderTestIsNull')]
    public function testIsNull($driver, $syntax, $field, $result)
    {
        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\SqlStatement($con, $syntax);

        $this->assertEquals(
            $result,
            $sql->isNull($field)
        );
    }

    public static function dataProviderTestIsNull()
    {
        return [
            ['mysqli', 'mysql', 'MyTable.MyField', 'MyTable.MyField IS NULL'],
            ['mysqlimb4', 'mysql', 'MyTable.MyField', 'MyTable.MyField IS NULL'],
            ['pgsql', 'postgresql', 'MyTable.MyField', 'MyTable.MyField IS NULL'],
            ['sqlite', 'sqlite', 'MyTable.MyField', 'MyTable.MyField IS NULL'],
        ];
    }

    #[DataProvider('dataProviderTestIsNotNull')]
    public function testIsNotNull($driver, $syntax, $field, $result)
    {
        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\SqlStatement($con, $syntax);

        $this->assertEquals(
            $result,
            $sql->isNotNull($field)
        );
    }

    public static function dataProviderTestIsNotNull()
    {
        return [
            ['mysqli', 'mysql', 'MyTable.MyField', 'MyTable.MyField IS NOT NULL'],
            ['mysqlimb4', 'mysql', 'MyTable.MyField', 'MyTable.MyField IS NOT NULL'],
            ['pgsql', 'postgresql', 'MyTable.MyField', 'MyTable.MyField IS NOT NULL'],
            ['sqlite', 'sqlite', 'MyTable.MyField', 'MyTable.MyField IS NOT NULL'],
        ];
    }

    #[DataProvider('dataProviderTest')]
    public function testMagic(string $driver, string $syntax)
    {
        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\SqlStatement($con, $syntax);

        $sql->syntax = 'Hello';

        $this->assertEquals(
            'Hello',
            $sql->syntax
        );
        $this->assertTrue(
            isset($sql->syntax)
        );

        unset($sql->syntax);

        $this->assertFalse(
            isset($sql->syntax)
        );
        $this->assertFalse(
            isset($sql->syntaxEngine)
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unknown property syntaxEngine');

        $sql->syntaxEngine = 'Hello';

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unknown property syntaxEngine');

        if ($sql->syntaxEngine === 'Hello')
        ;
    }
}
