<?php

/**
 * Unit tests
 *
 * @package Dotclear
 *
 * @copyright Olivier Meunier & Association Dotclear
 * @copyright GPL-2.0-only
 */

// This statement may broke class mocking system:
// declare(strict_types=1);

namespace tests\unit\Dotclear\Database\Statement;

require_once implode(DIRECTORY_SEPARATOR, [__DIR__, '..', '..', '..', 'bootstrap.php']);

use atoum;
use atoum\atoum\mock\controller;

/*
 * @tags SqlStatement
 */
class SqlStatement extends atoum
{
    private function getConnection($driver, $syntax)
    {
        $controller              = new controller();
        $controller->__construct = function () {};

        $class_name = sprintf('\\mock\\Dotclear\\Database\\Driver\\%s\\Handler', ucfirst($driver));
        $con        = new $class_name($controller, $driver);

        $this->calling($con)->driver    = $driver;
        $this->calling($con)->syntax    = $syntax;
        $this->calling($con)->escapeStr = fn ($str) => addslashes((string) $str);

        return $con;
    }

    public function test($driver, $syntax)
    {
        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\SqlStatement($con, $syntax);

        $this
            //->dump($driver . ' - ' . $syntax . ' - ' . $source . ' : ' . $result)
            ->string($sql->statement())
            ->isEqualTo('')
        ;

        $this
            ->string($sql())
            ->isEqualTo('')
        ;
    }

    protected function testDataProvider()
    {
        return [
            ['mysqli', 'mysql'],
            ['mysqlimb4', 'mysql'],
            ['pgsql', 'postgresql'],
            ['sqlite', 'sqlite'],
        ];
    }

    public function testEscape($driver, $syntax, $source, $result)
    {
        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\SqlStatement($con, $syntax);

        $this
            //->dump($driver . ' - ' . $syntax . ' - ' . $source . ' : ' . $result)
            ->string($sql->escape($source))
            ->isEqualTo($result)
        ;
    }

    protected function testEscapeDataProvider()
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

    public function testQuote($driver, $syntax, $source, $result, $escape)
    {
        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\SqlStatement($con, $syntax);

        $this
            //->dump($driver . ' - ' . $syntax . ' - ' . $source . ' : ' . $result . ' (' . ($escape ? 'true' : 'false') . ')')
            ->string($sql->quote($source, $escape))
            ->isEqualTo($result)
        ;
    }

    protected function testQuoteDataProvider()
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

    public function testAlias($driver, $syntax, $name, $alias, $result)
    {
        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\SqlStatement($con, $syntax);

        $this
            //->dump($driver . ' - ' . $syntax . ' - ' . $name . ' - ' . $alias . ' : ' . $result)
            ->string($sql->alias($name, $alias))
            ->isEqualTo($result)
            ->string($sql->as($name, $alias))
            ->isEqualTo($result)
        ;
    }

    protected function testAliasDataProvider()
    {
        return [
            ['mysqli', 'mysql', 'MyTable.MyField', 'MyAlias', 'MyTable.MyField MyAlias'],
            ['mysqlimb4', 'mysql', 'MyTable.MyField', 'MyAlias', 'MyTable.MyField MyAlias'],
            ['pgsql', 'postgresql', 'MyTable.MyField', 'MyAlias', 'MyTable.MyField MyAlias'],
            ['sqlite', 'sqlite', 'MyTable.MyField', 'MyAlias', 'MyTable.MyField AS MyAlias'],
        ];
    }

    public function testIn($driver, $syntax, $values, $cast, $result)
    {
        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\SqlStatement($con, $syntax);

        $this
            //->dump($driver . ' - ' . $syntax . ' - ' . implode(',', $values ? (is_array($values) ? $values : [$values]) : []) . ' - ' . $cast . ' : ' . $result)
            ->string($sql->in($values, $cast))
            ->isEqualTo($result)
        ;
    }

    protected function testInDataProvider()
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

    public function testDateFormat($driver, $syntax, $field, $pattern, $result)
    {
        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\SqlStatement($con, $syntax);

        $this
            //->dump($driver . ' - ' . $syntax . ' - ' . $field . ' - ' . $pattern . ' : ' . $result)
            ->string($sql->dateFormat($field, $pattern))
            ->isEqualTo($result)
        ;
    }

    protected function testDateFormatDataProvider()
    {
        return [
            ['mysqli', 'mysql', 'MyTable.MyField', '%Y/%m/%d %H:%M:%S', 'DATE_FORMAT(MyTable.MyField,\'%Y/%m/%d %H:%i:%S\')'],
            ['mysqlimb4', 'mysql', 'MyTable.MyField', '%Y/%m/%d %H:%M:%S', 'DATE_FORMAT(MyTable.MyField,\'%Y/%m/%d %H:%i:%S\')'],
            ['pgsql', 'postgresql', 'MyTable.MyField', '%Y/%m/%d %H:%M:%S', 'TO_CHAR(MyTable.MyField,\'YYYY/MM/DD HH24:MI:SS\')'],
            ['sqlite', 'sqlite', 'MyTable.MyField', '%Y/%m/%d %H:%M:%S', 'strftime(\'%Y/%m/%d %H:%M:%S\',MyTable.MyField)'],
        ];
    }

    public function testLike($driver, $syntax, $field, $pattern, $result)
    {
        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\SqlStatement($con, $syntax);

        $this
            //->dump($driver . ' - ' . $syntax . ' - ' . $field . ' - ' . $pattern . ' : ' . $result)
            ->string($sql->like($field, $pattern))
            ->isEqualTo($result)
        ;
    }

    protected function testLikeDataProvider()
    {
        return [
            ['mysqli', 'mysql', 'MyTable.MyField', 't*s_t', 'MyTable.MyField LIKE \'t*s_t\''],
            ['mysqlimb4', 'mysql', 'MyTable.MyField', 't*s_t', 'MyTable.MyField LIKE \'t*s_t\''],
            ['pgsql', 'postgresql', 'MyTable.MyField', 't*s_t', 'MyTable.MyField LIKE \'t*s_t\''],
            ['sqlite', 'sqlite', 'MyTable.MyField', 't*s_t', 'MyTable.MyField LIKE \'t*s_t\''],
        ];
    }

    public function testRegexp($driver, $syntax, $pattern, $result)
    {
        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\SqlStatement($con, $syntax);

        $this
            //->dump($driver . ' - ' . $syntax . ' - ' . $pattern . ' : ' . $result)
            ->string($sql->regexp($pattern))
            ->isEqualTo($result)
        ;
    }

    protected function testRegexpDataProvider()
    {
        return [
            ['mysqli', 'mysql', '([A-Za-z0-9]+)', ' REGEXP \'^\\\\(\\\\[A\\\\-Za\\\\-z0\\\\-9\\\\]\\\\+\\\\)[0-9]+$\''],
            ['mysqlimb4', 'mysql', '([A-Za-z0-9]+)', ' REGEXP \'^\\\\(\\\\[A\\\\-Za\\\\-z0\\\\-9\\\\]\\\\+\\\\)[0-9]+$\''],
            ['pgsql', 'postgresql', '([A-Za-z0-9]+)', ' ~ \'^\\\\(\\\\[A\\\\-Za\\\\-z0\\\\-9\\\\]\\\\+\\\\)[0-9]+$\''],
            ['sqlite', 'sqlite', '([A-Za-z0-9]+)', ' LIKE \'([A-Za-z0-9]+)%\' ESCAPE \'!\''],
        ];
    }

    public function testUnique($driver, $syntax, $field, $result)
    {
        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\SqlStatement($con, $syntax);

        $this
            //->dump($driver . ' - ' . $syntax . ' - ' . $pattern . ' : ' . $result)
            ->string($sql->unique($field))
            ->isEqualTo($result)
        ;
    }

    protected function testUniqueDataProvider()
    {
        return [
            ['mysqli', 'mysql', 'MyTable.MyField', 'DISTINCT MyTable.MyField'],
            ['mysqlimb4', 'mysql', 'MyTable.MyField', 'DISTINCT MyTable.MyField'],
            ['pgsql', 'postgresql', 'MyTable.MyField', 'DISTINCT MyTable.MyField'],
            ['sqlite', 'sqlite', 'MyTable.MyField', 'DISTINCT MyTable.MyField'],
        ];
    }

    public function testCount($driver, $syntax, $field, $alias, $unique, $result)
    {
        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\SqlStatement($con, $syntax);

        $this
            //->dump($driver . ' - ' . $syntax . ' - ' . $pattern . ' : ' . $result)
            ->string($sql->count($field, $alias, $unique))
            ->isEqualTo($result)
        ;
    }

    protected function testCountDataProvider()
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

    public function testAvg($driver, $syntax, $field, $alias, $result)
    {
        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\SqlStatement($con, $syntax);

        $this
            ->string($sql->avg($field, $alias))
            ->isEqualTo($result)
        ;
    }

    protected function testAvgDataProvider()
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

    public function testMax($driver, $syntax, $field, $alias, $result)
    {
        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\SqlStatement($con, $syntax);

        $this
            ->string($sql->max($field, $alias))
            ->isEqualTo($result)
        ;
    }

    protected function testMaxDataProvider()
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

    public function testMin($driver, $syntax, $field, $alias, $result)
    {
        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\SqlStatement($con, $syntax);

        $this
            ->string($sql->min($field, $alias))
            ->isEqualTo($result)
        ;
    }

    protected function testMinDataProvider()
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

    public function testSum($driver, $syntax, $field, $alias, $result)
    {
        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\SqlStatement($con, $syntax);

        $this
            ->string($sql->sum($field, $alias))
            ->isEqualTo($result)
        ;
    }

    protected function testSumDataProvider()
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

    public function testIsNull($driver, $syntax, $field, $result)
    {
        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\SqlStatement($con, $syntax);

        $this
            ->string($sql->isNull($field))
            ->isEqualTo($result)
        ;
    }

    protected function testIsNullDataProvider()
    {
        return [
            ['mysqli', 'mysql', 'MyTable.MyField', 'MyTable.MyField IS NULL'],
            ['mysqlimb4', 'mysql', 'MyTable.MyField', 'MyTable.MyField IS NULL'],
            ['pgsql', 'postgresql', 'MyTable.MyField', 'MyTable.MyField IS NULL'],
            ['sqlite', 'sqlite', 'MyTable.MyField', 'MyTable.MyField IS NULL'],
        ];
    }

    public function testIsNotNull($driver, $syntax, $field, $result)
    {
        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\SqlStatement($con, $syntax);

        $this
            ->string($sql->isNotNull($field))
            ->isEqualTo($result)
        ;
    }

    protected function testIsNotNullDataProvider()
    {
        return [
            ['mysqli', 'mysql', 'MyTable.MyField', 'MyTable.MyField IS NOT NULL'],
            ['mysqlimb4', 'mysql', 'MyTable.MyField', 'MyTable.MyField IS NOT NULL'],
            ['pgsql', 'postgresql', 'MyTable.MyField', 'MyTable.MyField IS NOT NULL'],
            ['sqlite', 'sqlite', 'MyTable.MyField', 'MyTable.MyField IS NOT NULL'],
        ];
    }

    public function testMagic()
    {
        $driver = 'mysqli';
        $syntax = 'mysql';

        $con = $this->getConnection($driver, $syntax);
        $sql = new \Dotclear\Database\Statement\SqlStatement($con, $syntax);

        $sql->syntax = 'Hello';

        $this
            ->string($sql->syntax)
            ->isEqualTo('Hello')
            ->boolean(isset($sql->syntax))
            ->isTrue()
        ;

        unset($sql->syntax);

        $this
            ->boolean(isset($sql->syntax))
            ->isFalse()
            ->boolean(isset($sql->syntaxEngine))
            ->isFalse()
        ;

        $this
            ->when(
                function () use ($sql) {
                    $sql->syntaxEngine = 'Hello';
                }
            )
            ->error('Unknown property syntaxEngine', E_USER_WARNING)->exists()
            ->when(
                function () use ($sql) {
                    if ($sql->syntaxEngine === 'Hello')
                    ;
                }
            )
            ->error('Unknown property syntaxEngine', E_USER_WARNING)->exists()
        ;
    }
}
